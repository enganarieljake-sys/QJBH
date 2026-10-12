<?php

namespace App\Notifications;

use App\Models\SecurityCode;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SecurityCodeMail extends Notification
{
    use Queueable;

    /**
     * @param  string  $requestedBy  Name of the admin who asked for the code; the
     *                               email may go to a different address than theirs.
     */
    public function __construct(
        private readonly string $code,
        private readonly string $action,
        private readonly string $requestedBy,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your verification code: {$this->code}")
            ->greeting('Hi,')
            ->line("{$this->requestedBy} asked to {$this->action}. Use this code to confirm:")
            ->line("**{$this->code}**")
            ->line('The code expires in '.SecurityCode::LIFETIME_MINUTES.' minutes and works only once.')
            ->line('If you did not expect this, do not share the code and change the admin password right away.')
            ->salutation('Queen Jackie Boarding House');
    }
}
