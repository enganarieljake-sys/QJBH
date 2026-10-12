<?php

namespace App\Notifications;

use App\Models\Bill;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email copy of a bill reminder. The in-app copy uses ActivityNotification
 * so it still lands even when the mail server is down.
 */
class BillReminderMail extends Notification
{
    use Queueable;

    public function __construct(private readonly Bill $bill, private readonly bool $overdue)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $month = $this->bill->billing_month?->format('F Y');
        $balance = 'PHP '.number_format($this->bill->balance, 2);
        $due = $this->bill->due_date?->format('F j, Y');

        return (new MailMessage)
            ->subject($this->overdue ? "Overdue bill for {$month}" : "Your bill for {$month} is due soon")
            ->greeting('Hi '.$notifiable->firstName().'!')
            ->line($this->overdue
                ? "Your bill for {$month} was due on {$due} and still has a balance of {$balance}."
                : "Friendly reminder: your bill for {$month} is due on {$due}. Remaining balance: {$balance}.")
            ->action('Pay & send receipt', route('tenant.receipts.create', ['bill_id' => $this->bill->id]))
            ->line('If you already paid, please ignore this message or send your receipt in the app.')
            ->salutation('Thank you, Queen Jackie Boarding House');
    }
}
