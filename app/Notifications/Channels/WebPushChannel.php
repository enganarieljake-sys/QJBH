<?php

namespace App\Notifications\Channels;

use App\Support\WebPush;
use Illuminate\Notifications\Notification;

class WebPushChannel
{
    public function __construct(private readonly WebPush $webPush)
    {
    }

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWebPush')) {
            return;
        }

        $this->webPush->sendToUsers(collect([$notifiable]), $notification->toWebPush($notifiable));
    }
}
