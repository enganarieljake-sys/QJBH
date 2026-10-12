<?php

namespace App\Notifications;

use App\Models\MaintenanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emails a maintenance worker (panday) the repair ticket forwarded to them,
 * so they see it even without opening the app.
 */
class RepairTicketMail extends Notification
{
    use Queueable;

    public function __construct(private readonly MaintenanceRequest $ticket)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ticket = $this->ticket;

        return (new MailMessage)
            ->subject('New repair ticket: '.$ticket->ticketLabel())
            ->greeting('Hi '.$notifiable->firstName().',')
            ->line('You have a new repair from '.($ticket->assigner?->firstName() ?? 'the admin').'.')
            ->line('Room: '.($ticket->room?->room_number ?? '—'))
            ->line('Issue: '.$ticket->description)
            ->line('Priority: '.ucfirst($ticket->priority).($ticket->target_date ? ' · Target: '.$ticket->target_date->format('M j, Y') : ''))
            ->line('Instructions: '.$ticket->assignment_notes)
            ->action('Open the ticket', route('admin.maintenance-requests.show', $ticket))
            ->salutation('Queen Jackie Boarding House');
    }
}
