<?php

namespace App\Notifications;

use App\Mail\TenantInvitationMail;
use App\Models\Tenant;
use App\Support\InternalUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TenantInvitation extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Tenant $tenant,
        private readonly string $token,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): TenantInvitationMail
    {
        $url = InternalUrl::absolute(route('invitation.show', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ], absolute: false));

        return (new TenantInvitationMail($this->tenant->loadMissing('room'), $notifiable, $url))
            ->to($notifiable->email);
    }
}
