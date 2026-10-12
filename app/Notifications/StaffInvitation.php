<?php

namespace App\Notifications;

use App\Support\StaffAccess;
use App\Support\InternalUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emails a new caretaker or panday the link where they choose their password.
 */
class StaffInvitation extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = InternalUrl::absolute(route('invitation.show', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ], absolute: false));
        $role = StaffAccess::LABELS[$notifiable->role] ?? 'Staff';

        return (new MailMessage)
            ->subject('Your Queen Jackie Boarding House staff account is ready')
            ->greeting("Hi {$notifiable->firstName()}!")
            ->line("You were added as {$role} at Queen Jackie Boarding House.")
            ->line("Login email: {$notifiable->email}")
            ->action('Set your password', $url)
            ->line('This link expires on '.now('Asia/Manila')->addMinutes(config('auth.passwords.invitations.expire'))->format('F j, Y g:i A').'. Ask the owner for a new one if it runs out.');
    }
}
