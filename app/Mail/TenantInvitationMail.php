<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public User $user,
        public string $setupUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Queen Jackie Boarding House - your tenant account is ready',
        );
    }

    public function content(): Content
    {
        $room = $this->tenant->room;

        return new Content(
            view: 'emails.tenant-invitation',
            text: 'emails.tenant-invitation-text',
            with: [
                'firstName' => $this->user->firstName(),
                'details' => [
                    'Tenant name' => $this->tenant->full_name,
                    'Login email' => $this->user->email,
                    'Room' => $room ? 'Room '.$room->room_number : 'To be assigned',
                    'Room type' => $room ? ucfirst($room->type).' (good for '.$room->capacity.')' : 'To be assigned',
                    'Floor' => $room?->floor ? 'Floor '.$room->floor : 'To be assigned',
                    'Monthly rent' => $room ? 'PHP '.number_format((float) $room->monthly_rate, 2) : 'To be confirmed',
                    'Start date' => $this->tenant->move_in_date?->format('F j, Y') ?? 'To be confirmed',
                    'End date' => $this->tenant->lease_end_date?->format('F j, Y') ?? 'Open-ended',
                ],
                'expiresAt' => now('Asia/Manila')->addMinutes(config('auth.passwords.invitations.expire'))->format('F j, Y g:i A'),
            ],
        );
    }
}
