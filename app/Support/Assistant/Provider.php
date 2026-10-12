<?php

namespace App\Support\Assistant;

use App\Models\Setting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * An OpenAI-compatible AI provider from config('services.ai_providers'),
 * e.g. NVIDIA or Groq. Models that hit a limit are skipped until the provider
 * says they reset, and the next model is tried. chat() returns null when none
 * can answer, so the caller can move on to the next provider.
 */
class Provider
{
    /**
     * @return list<string> Provider names in the order to try them.
     */
    public static function names(): array
    {
        return array_keys(config('services.ai_providers', []));
    }

    public static function configured(string $name): bool
    {
        return filled(config("services.ai_providers.{$name}.key"));
    }

    /**
     * @return list<string>
     */
    public static function models(string $name): array
    {
        $skip = self::skipped($name);

        return array_values(array_filter(
            array_map('trim', explode(',', (string) config("services.ai_providers.{$name}.models"))),
            fn (string $model) => $model !== '' && ! isset($skip[$model]),
        ));
    }

    /**
     * @return array{message: array, model: string}|null
     */
    public static function chat(string $name, array $messages, array $tools): ?array
    {
        if (! self::configured($name)) {
            return null;
        }

        $config = config("services.ai_providers.{$name}");

        foreach (self::models($name) as $model) {
            // Free endpoints sometimes fail with a one-off 500, so try each model twice.
            for ($try = 1; $try <= 2; $try++) {
                if (! Deadline::allows()) {
                    return null;
                }

                try {
                    $response = Http::withToken((string) $config['key'])
                        ->timeout(Deadline::timeout())
                        ->post($config['url'].'/chat/completions', array_merge([
                            'model' => $model,
                            'messages' => $messages,
                            'tools' => $tools,
                            'max_tokens' => 900,
                            'temperature' => 0.2,
                        ], $config['extra'] ?? []));
                } catch (Throwable $e) {
                    Log::info("QJ Bot: {$name} {$model} did not respond.", ['error' => $e->getMessage()]);

                    continue 2;
                }

                if ($response->status() < 500) {
                    break;
                }
            }

            $message = $response->json('choices.0.message');

            if ($response->successful() && is_array($message)) {
                self::rememberRemaining($name, $response);

                return ['message' => $message, 'model' => "{$name}:{$model}"];
            }

            if (self::handleFailure($name, $model, $response)) {
                return null;
            }
        }

        return null;
    }

    /**
     * Requests left today, or null when the provider only limits per minute.
     */
    public static function remaining(string $name): ?int
    {
        if (! self::configured($name) || self::models($name) === []) {
            return 0;
        }

        $limit = config("services.ai_providers.{$name}.daily_limit");

        if ($limit === null) {
            return null;
        }

        $saved = json_decode((string) Setting::get(self::key($name, 'remaining')), true) ?: [];

        // Daily windows roll over, so an old number says nothing about today.
        if (! isset($saved['at']) || $saved['at'] < now()->subDay()->timestamp) {
            return (int) $limit;
        }

        return (int) $saved['remaining'];
    }

    private static function rememberRemaining(string $name, Response $response): void
    {
        $remaining = $response->header('x-ratelimit-remaining-requests');

        if ($remaining !== '' && is_numeric($remaining)) {
            Setting::put(self::key($name, 'remaining'), json_encode(['remaining' => (int) $remaining, 'at' => now()->timestamp]));
        }
    }

    /**
     * Skips a model that is rate-limited or gone. Returns true when the whole
     * provider is unusable (bad key), so its other models aren't tried.
     */
    private static function handleFailure(string $name, string $model, Response $response): bool
    {
        $status = $response->status();
        Log::info("QJ Bot: {$name} {$model} failed.", ['status' => $status, 'error' => mb_substr($response->body(), 0, 300)]);

        if ($status === 401 || $status === 403) {
            return true;
        }

        $seconds = match (true) {
            $status === 429 => self::retryAfter($response),
            $status === 404, $status === 400 && str_contains($response->body(), 'model_decommissioned') => 86400,
            default => 0,
        };

        if ($seconds > 0) {
            $skip = self::skipped($name);
            $skip[$model] = now()->addSeconds($seconds)->timestamp;
            Setting::put(self::key($name, 'skip'), json_encode($skip));
        }

        return false;
    }

    /**
     * Seconds until the provider lets this model answer again (at least a minute).
     */
    private static function retryAfter(Response $response): int
    {
        $header = $response->header('retry-after');

        return max(is_numeric($header) ? (int) ceil((float) $header) : 3600, 60);
    }

    /**
     * @return array<string, int>
     */
    private static function skipped(string $name): array
    {
        $skip = json_decode((string) Setting::get(self::key($name, 'skip')), true) ?: [];

        return array_filter($skip, fn ($until) => $until > now()->timestamp);
    }

    private static function key(string $name, string $what): string
    {
        return "assistant_{$name}_{$what}";
    }
}
