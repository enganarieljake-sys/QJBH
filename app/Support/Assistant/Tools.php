<?php

namespace App\Support\Assistant;

use App\Models\Bill;
use App\Models\Concern;
use App\Models\Expense;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\PaymentSubmission;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\Visitor;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Read-only lookups QJ Bot can call. The AI never writes SQL or changes
 * data; it only picks one of these and phrases the result. Contact numbers
 * and emails are left out on purpose.
 */
class Tools
{
    /**
     * Tool definitions in the OpenAI / OpenRouter format.
     */
    public static function definitions(): array
    {
        $month = ['type' => 'string', 'description' => 'Month as YYYY-MM. Leave out for the current month.'];

        return [
            self::define('overview', 'Summary of the boarding house right now: rooms, occupancy, tenants, money collected this month, unpaid total, pending receipts, open repairs and concerns.'),
            self::define('find_tenant', 'Look up tenants by (part of) their name: room, unpaid bills with balances, and latest payments. Use for "did X pay?" or "how much does X owe?".', [
                'name' => ['type' => 'string', 'description' => 'Full or partial tenant name, e.g. "Jake".'],
            ], ['name']),
            self::define('room_status', 'One room by number: capacity, status, its tenants, and whether each has paid this month.', [
                'room_number' => ['type' => 'string', 'description' => 'Room number, e.g. "4".'],
            ], ['room_number']),
            self::define('available_rooms', 'Rooms that still have free slots for new tenants, with monthly rates.'),
            self::define('unpaid_bills', 'Bills that are not fully paid, with tenant, room, balance, due date, and whether overdue. Optionally for one billing month.', [
                'month' => $month,
            ]),
            self::define('recent_payments', 'Payments received in the last N days.', [
                'days' => ['type' => 'integer', 'description' => 'How many days back, 1 to 90. Default 7.'],
            ]),
            self::define('pending_receipts', 'Payment receipts uploaded by tenants that the admin has not reviewed yet.'),
            self::define('open_issues', 'Repair requests and tenant concerns that are not resolved yet.'),
            self::define('expenses', 'Expenses for a month by category, plus income and net for that month.', [
                'month' => $month,
            ]),
            self::define('visitors_today', 'Visitors logged today and whether they are still inside.'),
        ];
    }

    public static function names(): array
    {
        return array_map(fn (array $tool) => $tool['function']['name'], self::definitions());
    }

    public static function run(string $name, array $args = []): array
    {
        try {
            return match ($name) {
                'overview' => self::overview(),
                'find_tenant' => self::findTenant((string) ($args['name'] ?? '')),
                'room_status' => self::roomStatus((string) ($args['room_number'] ?? '')),
                'available_rooms' => self::availableRooms(),
                'unpaid_bills' => self::unpaidBills($args['month'] ?? null),
                'recent_payments' => self::recentPayments((int) ($args['days'] ?? 7)),
                'pending_receipts' => self::pendingReceipts(),
                'open_issues' => self::openIssues(),
                'expenses' => self::expenses($args['month'] ?? null),
                'visitors_today' => self::visitorsToday(),
                default => ['error' => "Unknown tool {$name}."],
            };
        } catch (Throwable $e) {
            report($e);

            return ['error' => 'The lookup failed. Tell the admin to try again or check the page directly.'];
        }
    }

    public static function overview(): array
    {
        $unpaid = Bill::with('payments')->where('status', '!=', 'paid')->get();

        return [
            'today' => today()->format('F j, Y'),
            'rooms_total' => Room::count(),
            'rooms_occupied' => Room::where('status', 'occupied')->count(),
            'rooms_with_free_slots' => Room::vacant()->count(),
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'collected_this_month' => self::peso(Payment::whereBetween('payment_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount')),
            'unpaid_bills' => $unpaid->count(),
            'unpaid_total' => self::peso($unpaid->sum(fn (Bill $bill) => $bill->balance)),
            'overdue_bills' => $unpaid->where('status', 'overdue')->count(),
            'receipts_waiting_review' => PaymentSubmission::where('status', 'pending')->count(),
            'open_repairs' => MaintenanceRequest::whereIn('status', ['submitted', 'in-progress', 'awaiting-verification'])->count(),
            'open_concerns' => Concern::whereIn('status', ['open', 'in-review'])->count(),
        ];
    }

    public static function findTenant(string $name): array
    {
        $name = trim($name);

        if ($name === '') {
            return ['error' => 'No name given. Ask the admin which tenant.'];
        }

        $matches = Tenant::with(['room', 'bills.payments', 'payments' => fn ($query) => $query->latest('payment_date')])
            ->whereRaw('LOWER(full_name) LIKE ?', ['%'.mb_strtolower($name).'%'])
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->limit(6)
            ->get();

        if ($matches->isEmpty()) {
            return [
                'found' => 0,
                'note' => "No tenant has a name containing \"{$name}\".",
                'tenant_names' => Tenant::where('status', 'active')->orderBy('full_name')->limit(40)->pluck('full_name'),
            ];
        }

        return [
            'found' => $matches->count(),
            'note' => $matches->count() > 1 ? 'Several tenants match. Answer for each, or ask which one the admin means.' : null,
            'tenants' => $matches->map(fn (Tenant $tenant) => [
                'name' => $tenant->full_name,
                'status' => $tenant->status,
                'room' => $tenant->room?->room_number,
                'unpaid_bills' => $tenant->bills
                    ->where('status', '!=', 'paid')
                    ->sortBy('billing_month')
                    ->map(fn (Bill $bill) => self::billSummary($bill))
                    ->values(),
                'total_balance' => self::peso($tenant->bills->where('status', '!=', 'paid')->sum(fn (Bill $bill) => $bill->balance)),
                'latest_payments' => $tenant->payments->take(3)->map(fn (Payment $payment) => [
                    'date' => $payment->payment_date?->format('M j, Y'),
                    'amount' => self::peso($payment->amount),
                    'method' => $payment->payment_method,
                    'for_bill_month' => $payment->bill?->billing_month?->format('F Y'),
                ])->values(),
            ])->values(),
        ];
    }

    public static function roomStatus(string $roomNumber): array
    {
        $roomNumber = trim(preg_replace('/^room\s*/i', '', $roomNumber));
        $room = Room::with(['activeTenants.bills.payments'])->where('room_number', $roomNumber)->first();

        if (! $room) {
            return ['found' => false, 'note' => "There is no room {$roomNumber}.", 'existing_rooms' => Room::orderByNumber()->pluck('room_number')];
        }

        $month = now()->startOfMonth();

        return [
            'found' => true,
            'room' => $room->room_number,
            'type' => $room->type,
            'capacity' => $room->capacity,
            'status' => $room->status,
            'monthly_rate' => self::peso($room->monthly_rate),
            'tenants' => $room->activeTenants->map(function (Tenant $tenant) use ($month) {
                $bill = $tenant->bills->first(fn (Bill $bill) => $bill->billing_month?->isSameMonth($month));

                return [
                    'name' => $tenant->full_name,
                    'this_month' => $bill ? self::billSummary($bill) : 'No bill for '.$month->format('F Y').' yet.',
                    'total_balance' => self::peso($tenant->bills->where('status', '!=', 'paid')->sum(fn (Bill $bill) => $bill->balance)),
                ];
            })->values(),
        ];
    }

    public static function availableRooms(): array
    {
        $rooms = Room::vacant();

        return [
            'count' => $rooms->count(),
            'rooms' => $rooms->map(fn (Room $room) => [
                'room' => $room->room_number,
                'type' => $room->type,
                'free_slots' => $room->slots_left,
                'capacity' => $room->capacity,
                'monthly_rate' => self::peso($room->monthly_rate),
            ])->values(),
        ];
    }

    public static function unpaidBills(?string $month = null): array
    {
        $monthDate = self::month($month);
        $bills = Bill::with(['tenant', 'room', 'payments'])
            ->where('status', '!=', 'paid')
            ->when($month && $monthDate, fn ($query) => $query->whereBetween('billing_month', [$monthDate->copy()->startOfMonth(), $monthDate->copy()->endOfMonth()]))
            ->orderBy('due_date')
            ->get();

        return [
            'month' => $month && $monthDate ? $monthDate->format('F Y') : 'all months',
            'unpaid_bill_count' => $bills->count(),
            'tenants_with_balance' => $bills->pluck('tenant_id')->unique()->count(),
            'total_balance' => self::peso($bills->sum(fn (Bill $bill) => $bill->balance)),
            'bills' => $bills->take(40)->map(fn (Bill $bill) => ['tenant' => $bill->tenant?->full_name, 'room' => $bill->room?->room_number] + self::billSummary($bill))->values(),
        ];
    }

    public static function recentPayments(int $days = 7): array
    {
        $days = min(max($days, 1), 90);
        $payments = Payment::with(['tenant', 'bill'])
            ->whereDate('payment_date', '>=', today()->subDays($days - 1))
            ->latest('payment_date')
            ->get();

        return [
            'days' => $days,
            'count' => $payments->count(),
            'total' => self::peso($payments->sum('amount')),
            'payments' => $payments->take(40)->map(fn (Payment $payment) => [
                'tenant' => $payment->tenant?->full_name,
                'date' => $payment->payment_date?->format('M j, Y'),
                'amount' => self::peso($payment->amount),
                'method' => $payment->payment_method,
                'for_bill_month' => $payment->bill?->billing_month?->format('F Y'),
            ])->values(),
        ];
    }

    public static function pendingReceipts(): array
    {
        $submissions = PaymentSubmission::with('tenant')->where('status', 'pending')->oldest()->get();

        return [
            'count' => $submissions->count(),
            'receipts' => $submissions->map(fn (PaymentSubmission $submission) => [
                'tenant' => $submission->tenant?->full_name,
                'amount' => self::peso($submission->amount),
                'uploaded' => $submission->created_at?->format('M j, Y g:i A'),
            ])->values(),
        ];
    }

    public static function openIssues(): array
    {
        $repairs = MaintenanceRequest::with(['tenant', 'room'])->whereIn('status', ['submitted', 'in-progress', 'awaiting-verification'])->oldest()->get();
        $concerns = Concern::with('tenant')->whereIn('status', ['open', 'in-review'])->oldest()->get();

        return [
            'repairs' => $repairs->map(fn (MaintenanceRequest $repair) => [
                'room' => $repair->room?->room_number,
                'tenant' => $repair->tenant?->full_name,
                'issue' => str($repair->description)->limit(120)->toString(),
                'priority' => $repair->priority,
                'status' => $repair->status,
                'since' => $repair->created_at?->format('M j, Y'),
            ])->values(),
            'concerns' => $concerns->map(fn (Concern $concern) => [
                'tenant' => $concern->tenant?->full_name,
                'subject' => $concern->subject,
                'category' => $concern->categoryLabel(),
                'status' => $concern->status,
                'since' => $concern->created_at?->format('M j, Y'),
            ])->values(),
        ];
    }

    public static function expenses(?string $month = null): array
    {
        $monthDate = self::month($month) ?? now();
        $range = [$monthDate->copy()->startOfMonth(), $monthDate->copy()->endOfMonth()];
        $expenses = Expense::whereBetween('expense_date', $range)->get();
        $income = (float) Payment::whereBetween('payment_date', $range)->sum('amount');
        $spent = (float) $expenses->sum('amount');

        return [
            'month' => $monthDate->format('F Y'),
            'income' => self::peso($income),
            'expenses' => self::peso($spent),
            'net' => self::peso($income - $spent),
            'by_category' => $expenses->groupBy(fn (Expense $expense) => $expense->categoryLabel())
                ->map(fn ($group) => self::peso($group->sum('amount'))),
        ];
    }

    public static function visitorsToday(): array
    {
        $visitors = Visitor::with('tenant')->whereDate('visit_date', today())->orderBy('time_in')->get();

        return [
            'count' => $visitors->count(),
            'visitors' => $visitors->map(fn (Visitor $visitor) => [
                'visitor' => $visitor->visitor_name,
                'visiting' => $visitor->tenant?->full_name,
                'time_in' => Visitor::formatTime($visitor->time_in),
                'time_out' => $visitor->time_out ? Visitor::formatTime($visitor->time_out) : null,
                'still_inside' => $visitor->isInside(),
            ])->values(),
        ];
    }

    public static function peso(float|int|string|null $amount): string
    {
        return '₱'.number_format((float) $amount, 2);
    }

    private static function billSummary(Bill $bill): array
    {
        return [
            'bill_month' => $bill->billing_month?->format('F Y'),
            'total' => self::peso($bill->total_amount),
            'paid' => self::peso($bill->paid_amount),
            'balance' => self::peso($bill->balance),
            'due' => $bill->due_date?->format('M j, Y'),
            'status' => $bill->status,
        ];
    }

    private static function month(?string $month): ?Carbon
    {
        if (! $month || ! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m', $month);
        } catch (Throwable) {
            return null;
        }
    }

    private static function define(string $name, string $description, array $properties = [], array $required = []): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => $description,
                'parameters' => ['type' => 'object', 'properties' => (object) $properties, 'required' => $required],
            ],
        ];
    }
}
