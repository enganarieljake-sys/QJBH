<?php

namespace App\Support;

use App\Models\Bill;
use App\Models\Expense;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Room;
use Illuminate\Support\Carbon;

/**
 * Numbers for the owner's Analytics page, shaped for its charts.
 */
class Analytics
{
    private const MONTHS = 6;

    public static function build(): array
    {
        $thisMonth = now()->startOfMonth();
        $lastMonth = $thisMonth->copy()->subMonth();
        $incomeNow = self::income($thisMonth);
        $incomeBefore = self::income($lastMonth);

        return [
            'summary' => [
                'this_month' => ['label' => $thisMonth->format('F'), 'income' => $incomeNow, 'expenses' => self::expenses($thisMonth)],
                'last_month' => ['label' => $lastMonth->format('F'), 'income' => $incomeBefore, 'expenses' => self::expenses($lastMonth)],
                'change' => $incomeBefore > 0 ? round(($incomeNow - $incomeBefore) / $incomeBefore * 100) : null,
            ],
            'trend' => self::trend($thisMonth),
            'rooms' => self::rooms(),
            'repairs' => self::repairs(),
            'bills' => self::bills($thisMonth),
            'methods' => self::methods($thisMonth),
        ];
    }

    /**
     * Income and expenses for each of the last six months.
     */
    private static function trend(Carbon $thisMonth): array
    {
        $months = collect(range(self::MONTHS - 1, 0))->map(fn (int $back) => $thisMonth->copy()->subMonths($back));

        return [
            'labels' => $months->map(fn (Carbon $month) => $month->format('M'))->all(),
            'income' => $months->map(fn (Carbon $month) => self::income($month))->all(),
            'expenses' => $months->map(fn (Carbon $month) => self::expenses($month))->all(),
        ];
    }

    private static function rooms(): array
    {
        $rooms = Room::withCount('activeTenants')->get();
        $maintenance = $rooms->where('status', 'under maintenance');
        $open = $rooms->where('status', '!=', 'under maintenance');

        return [
            'Full' => $open->filter(fn (Room $room) => $room->active_tenants_count >= $room->capacity)->count(),
            'Has space' => $open->filter(fn (Room $room) => $room->active_tenants_count < $room->capacity)->count(),
            'Under maintenance' => $maintenance->count(),
        ];
    }

    private static function repairs(): array
    {
        $open = MaintenanceRequest::whereIn('status', ['submitted', 'in-progress', 'awaiting-verification']);

        return [
            'Not forwarded' => (clone $open)->where('status', 'submitted')->whereNull('assigned_to')->count(),
            'With panday' => (clone $open)->where('status', 'submitted')->whereNotNull('assigned_to')->count(),
            'In progress' => (clone $open)->where('status', 'in-progress')->count(),
            'Awaiting verification' => (clone $open)->where('status', 'awaiting-verification')->count(),
            'Fixed (last 30 days)' => MaintenanceRequest::where('status', 'resolved')->where('updated_at', '>=', now()->subDays(30))->count(),
        ];
    }

    private static function bills(Carbon $month): array
    {
        $bills = Bill::whereBetween('billing_month', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]);

        return [
            'Paid' => (clone $bills)->where('status', 'paid')->count(),
            'Unpaid' => (clone $bills)->where('status', 'pending')->count(),
            'Overdue' => (clone $bills)->where('status', 'overdue')->count(),
        ];
    }

    private static function methods(Carbon $month): array
    {
        return Payment::whereBetween('payment_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get(['payment_method', 'amount'])
            ->groupBy(fn (Payment $payment) => $payment->payment_method === 'bank transfer' ? 'Bank' : ($payment->payment_method === 'cash' ? 'Cash' : $payment->payment_method))
            ->map(fn ($payments) => round((float) $payments->sum('amount'), 2))
            ->all();
    }

    private static function income(Carbon $month): float
    {
        return round((float) Payment::whereBetween('payment_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->sum('amount'), 2);
    }

    private static function expenses(Carbon $month): float
    {
        return round((float) Expense::whereBetween('expense_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->sum('amount'), 2);
    }
}
