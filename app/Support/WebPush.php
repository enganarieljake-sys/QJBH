<?php

namespace App\Support;

use App\Models\PushSubscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush as Sender;
use Throwable;

class WebPush
{
    public function configured(): bool
    {
        return filled(config('services.webpush.public_key'))
            && filled(config('services.webpush.private_key'))
            && filled(config('services.webpush.subject'));
    }

    /**
     * Send one payload to every subscribed device owned by the supplied users.
     * Failures never undo the in-app notification or announcement transaction.
     *
     * @return array{sent:int, failed:int, removed:int}
     */
    public function sendToUsers(Collection $users, array $payload): array
    {
        if (! $this->configured() || $users->isEmpty()) {
            return ['sent' => 0, 'failed' => 0, 'removed' => 0];
        }

        return $this->sendToSubscriptions(
            PushSubscription::whereIn('user_id', $users->pluck('id')->all())->get(),
            $payload,
        );
    }

    /** @return array{sent:int, failed:int, removed:int} */
    public function sendToSubscriptions(Collection $subscriptions, array $payload): array
    {
        $result = ['sent' => 0, 'failed' => 0, 'removed' => 0];
        if (! $this->configured()) {
            return $result;
        }

        $subscriptions = $subscriptions->keyBy('endpoint');
        if ($subscriptions->isEmpty()) {
            return $result;
        }

        try {
            $sender = new Sender([
                'VAPID' => [
                    'subject' => config('services.webpush.subject'),
                    'publicKey' => config('services.webpush.public_key'),
                    'privateKey' => config('services.webpush.private_key'),
                ],
            ], ['TTL' => 86400, 'urgency' => 'normal', 'batchSize' => 100], 12);

            $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            foreach ($subscriptions as $item) {
                $sender->queueNotification(new Subscription(
                    $item->endpoint,
                    $item->public_key,
                    $item->auth_token,
                    $item->content_encoding,
                ), $json);
            }

            foreach ($sender->flush() as $report) {
                $item = $subscriptions->get($report->getEndpoint());
                if (! $item) {
                    continue;
                }
                if ($report->isSuccess()) {
                    $item->forceFill(['last_success_at' => now(), 'failure_count' => 0])->save();
                    $result['sent']++;
                } elseif ($report->isSubscriptionExpired()) {
                    $item->delete();
                    $result['removed']++;
                } else {
                    $item->increment('failure_count');
                    $result['failed']++;
                    Log::warning('Web Push delivery failed.', ['subscription' => $item->id, 'reason' => $report->getReason()]);
                }
            }
        } catch (Throwable $e) {
            $result['failed'] += $subscriptions->count();
            Log::error('Web Push batch failed.', ['error' => $e->getMessage()]);
        }

        return $result;
    }
}
