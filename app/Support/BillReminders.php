<?php

namespace App\Support;

use App\Models\Bill;
use App\Models\Setting;
use App\Notifications\ActivityNotification;
use App\Notifications\BillReminderMail;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Daily job: marks late bills overdue and reminds tenants once before the
 * due date and once when the bill becomes overdue.
 */
class BillReminders
{
    /**
     * @return array{due_soon: int, overdue: int}
     */
    public static function run(): array
    {
        Bill::where('status', 'pending')
            ->whereDate('due_date', '<', today())
            ->with('payments')
            ->each(fn (Bill $bill) => $bill->updatePaymentStatus());

        $daysBefore = max((int) Setting::get('reminder_days_before_due'), 0);

        $dueSoon = Bill::with(['payments', 'tenant.user'])
            ->where('status', 'pending')
            ->whereNull('due_reminder_sent_at')
            ->whereDate('due_date', '>=', today())
            ->whereDate('due_date', '<=', today()->addDays($daysBefore))
            ->get();

        foreach ($dueSoon as $bill) {
            self::remind($bill, false);
            $bill->forceFill(['due_reminder_sent_at' => now()])->save();
        }

        $overdue = Bill::with(['payments', 'tenant.user'])
            ->where('status', 'overdue')
            ->whereNull('overdue_notice_sent_at')
            ->get();

        foreach ($overdue as $bill) {
            self::remind($bill, true);
            $bill->forceFill(['overdue_notice_sent_at' => now()])->save();
        }

        return ['due_soon' => $dueSoon->count(), 'overdue' => $overdue->count()];
    }

    private static function remind(Bill $bill, bool $overdue): void
    {
        $user = $bill->tenant?->user;

        if (! $user || $bill->balance <= 0) {
            return;
        }

        $month = $bill->billing_month?->format('F Y');

        $user->notify(new ActivityNotification([
            'title' => $overdue ? 'Bill overdue' : 'Bill due soon',
            'message' => $overdue
                ? "Your {$month} bill is overdue. Balance: PHP ".number_format($bill->balance, 2).'.'
                : "Your {$month} bill is due on ".$bill->due_date?->format('M j').'. Balance: PHP '.number_format($bill->balance, 2).'.',
            'url' => route('tenant.bills.show', $bill),
            'kind' => 'bill',
        ]));

        try {
            $user->notify(new BillReminderMail($bill, $overdue));
        } catch (Throwable $e) {
            Log::warning('Bill reminder email failed.', ['bill_id' => $bill->id, 'error' => $e->getMessage()]);
        }
    }
}
