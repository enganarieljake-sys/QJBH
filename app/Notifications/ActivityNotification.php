<?php

namespace App\Notifications;

use App\Notifications\Channels\WebPushChannel;
use App\Support\InternalUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ActivityNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly array $details)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $details = $this->details;

        if (array_key_exists('url', $details)) {
            $details['url'] = InternalUrl::path($details['url']);
        }

        return $details;
    }

    public function toWebPush(object $notifiable): array
    {
        $details = $this->toArray($notifiable);

        return [
            'title' => $details['title'] ?? 'QJBH update',
            'body' => $details['message'] ?? 'You have a new update.',
            'url' => $details['url'] ?? '/notifications',
            'tag' => 'qjbh-'.($details['kind'] ?? 'activity').'-'.substr(sha1((string) ($details['url'] ?? '')), 0, 12),
            'icon' => '/icons/icon-192.png',
            'badge' => '/icons/icon-192.png',
        ];
    }
}
