<?php

namespace App\Support\Assistant;

use App\Models\Setting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Talks to OpenRouter using free models only. Tries the configured model,
 * then each fallback, then free models discovered from OpenRouter's catalog.
 * Models that are gone are skipped for a day so we don't waste time on them.
 */
class OpenRouter
{
    private const SKIP_KEY = 'assistant_skip_models';

    private const DISCOVERED_KEY = 'assistant_discovered_models';

    /** When OpenRouter said the free daily limit is used up: blocked until this timestamp. */
    public const LIMIT_KEY = 'assistant_limit_until';

    public static function limitReached(): bool
    {
        return (int) Setting::get(self::LIMIT_KEY) > now()->timestamp;
    }

    /**
     * Only free models are allowed, so the bot can never run up a bill even if
     * a paid model is put in the env by mistake.
     */
    public static function isFree(string $model): bool
    {
        return str_ends_with($model, ':free')
            || str_starts_with($model, 'stealth/')
            || $model === 'openrouter/free';
    }

    public static function configured(): bool
    {
        return filled(config('services.openrouter.key'));
    }

    /**
     * @return list<string>
     */
    public static function candidates(): array
    {
        $models = array_merge(
            [(string) config('services.openrouter.model')],
            explode(',', (string) config('services.openrouter.fallbacks')),
            self::discovered(),
        );

        $skip = self::skipped();

        return array_values(array_unique(array_filter(
            array_map('trim', $models),
            fn (string $model) => $model !== '' && self::isFree($model) && ! isset($skip[$model]),
        )));
    }

    /**
     * Sends one chat completion, falling back through the free models.
     *
     * @return array{message: array, model: string}
     *
     * @throws AssistantUnavailable
     */
    public static function chat(array $messages, array $tools): array
    {
        if (! self::configured()) {
            throw new AssistantUnavailable('QJ Bot is not set up yet (missing OPENROUTER_API_KEY).');
        }

        $tried = [];

        foreach ([false, true] as $afterDiscovery) {
            if ($afterDiscovery) {
                // Every known model failed: look for new free models and try those.
                self::discover();
            }

            foreach (array_diff(self::candidates(), $tried) as $model) {
                if (! Deadline::allows()) {
                    break 2;
                }

                $tried[] = $model;
                $result = self::attempt($model, $messages, $tools);

                if ($result) {
                    return $result;
                }
            }
        }

        throw new AssistantUnavailable('All free AI models are busy or unavailable right now.');
    }

    /**
     * Refreshes the list of free models that support tools from OpenRouter's
     * catalog. Listing models is free and doesn't count toward the daily limit.
     */
    public static function discover(bool $force = false): array
    {
        $saved = json_decode((string) Setting::get(self::DISCOVERED_KEY), true) ?: [];

        if (! $force && isset($saved['fetched_at']) && $saved['fetched_at'] > now()->subDay()->timestamp) {
            return $saved['models'] ?? [];
        }

        try {
            $response = Http::withToken((string) config('services.openrouter.key'))
                ->timeout(10)
                ->get(config('services.openrouter.url').'/models');

            $models = collect($response->json('data', []))
                ->filter(fn ($model) => self::isFree((string) ($model['id'] ?? ''))
                    && in_array('tools', $model['supported_parameters'] ?? [], true)
                    && (float) ($model['pricing']['prompt'] ?? 1) === 0.0
                    && (float) ($model['pricing']['completion'] ?? 1) === 0.0)
                ->pluck('id')
                ->values()
                ->all();

            if ($response->successful()) {
                Setting::put(self::DISCOVERED_KEY, json_encode(['fetched_at' => now()->timestamp, 'models' => $models]));
            }

            return $models;
        } catch (Throwable $e) {
            Log::warning('Could not list OpenRouter models.', ['error' => $e->getMessage()]);

            return $saved['models'] ?? [];
        }
    }

    /**
     * @return array{message: array, model: string}|null
     *
     * @throws DailyLimitReached
     */
    private static function attempt(string $model, array $messages, array $tools): ?array
    {
        try {
            $response = Http::withToken((string) config('services.openrouter.key'))
                ->withHeaders(['X-Title' => 'QJBH App', 'HTTP-Referer' => (string) config('app.url')])
                ->timeout(Deadline::timeout())
                ->post(config('services.openrouter.url').'/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'tools' => $tools,
                    'max_tokens' => 900,
                    'temperature' => 0.2,
                ]);
        } catch (Throwable $e) {
            Log::info("QJ Bot: {$model} did not respond.", ['error' => $e->getMessage()]);

            return null;
        }

        // OpenRouter sometimes answers 200 with an error inside, e.g. "overloaded".
        $message = $response->json('choices.0.message');

        if ($response->successful() && is_array($message)) {
            return ['message' => $message, 'model' => (string) ($response->json('model') ?: $model)];
        }

        self::handleFailure($model, $response);

        return null;
    }

    /**
     * @throws DailyLimitReached
     */
    private static function handleFailure(string $model, Response $response): void
    {
        $status = (int) ($response->json('error.code') ?: $response->status());
        $error = mb_strtolower((string) $response->json('error.message').' '.$response->body());

        Log::info("QJ Bot: {$model} failed.", ['status' => $status, 'error' => mb_substr($error, 0, 300)]);

        // The account-wide free limit: no other model will work either.
        if ($status === 429 && str_contains($error, 'free-models-per-day')) {
            // OpenRouter counts every request (failed ones too), so trust it over
            // our own count and stop asking until its limit resets.
            Setting::put(self::LIMIT_KEY, (string) now('UTC')->addDay()->startOfDay()->timestamp);

            throw new DailyLimitReached;
        }

        // The model is gone or not allowed for us: skip it for a day. Other 400s
        // could be our own request's fault, so they don't skip anything.
        $modelGone = in_array($status, [403, 404], true)
            || ($status === 400 && str_contains($error, 'not a valid model'));

        if ($modelGone) {
            $skip = self::skipped();
            $skip[$model] = now()->addDay()->timestamp;
            Setting::put(self::SKIP_KEY, json_encode($skip));
        }
    }

    /**
     * @return array<string, int>
     */
    private static function skipped(): array
    {
        $skip = json_decode((string) Setting::get(self::SKIP_KEY), true) ?: [];

        return array_filter($skip, fn ($until) => $until > now()->timestamp);
    }

    /**
     * @return list<string>
     */
    private static function discovered(): array
    {
        return (json_decode((string) Setting::get(self::DISCOVERED_KEY), true) ?: [])['models'] ?? [];
    }
}
