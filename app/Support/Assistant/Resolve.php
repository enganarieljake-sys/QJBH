<?php

namespace App\Support\Assistant;

use App\Models\Announcement;
use App\Models\Bill;
use App\Models\Concern;
use App\Models\MaintenanceRequest;
use App\Models\PaymentSubmission;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Visitor;
use App\Support\Search;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Turns what the admin said ("Jake", "Room 4", "October") into records,
 * refusing to guess when it's missing or ambiguous.
 */
class Resolve
{
    public static function tenant(?string $name, bool $activeOnly = false): Tenant
    {
        $name = trim((string) $name);

        if ($name === '') {
            throw new ActionProblem('Kinsa nga tenant? Wala nahatag ang pangalan.');
        }

        $matches = Search::apply(Tenant::with('room'), $name, ['full_name'])
            ->when($activeOnly, fn ($query) => $query->where('status', 'active'))
            ->limit(10)
            ->get();

        // Prefer the one active tenant when an old, moved-out record shares the name.
        if ($matches->count() > 1 && $matches->where('status', 'active')->count() === 1) {
            return $matches->firstWhere('status', 'active');
        }

        return self::one($matches, "tenant nga ginganlan og \"{$name}\"", fn (Tenant $tenant) => $tenant->full_name);
    }

    public static function room(?string $number): Room
    {
        $number = trim(preg_replace('/^(room|kwarto)\s*/i', '', (string) $number));
        $room = Room::where('room_number', $number)->first();

        if (! $room) {
            $rooms = Room::orderByNumber()->pluck('room_number')->implode(', ');

            throw new ActionProblem("Walay Room {$number}. Ang naa: {$rooms}.");
        }

        return $room;
    }

    /**
     * The bill a payment or change is for: the given month, or else the oldest
     * one still unpaid.
     */
    public static function bill(Tenant $tenant, ?string $month = null, bool $unpaidOnly = true): Bill
    {
        $query = Bill::with('payments')->where('tenant_id', $tenant->id);

        if ($month) {
            $date = self::month($month);
            $bill = $query->whereBetween('billing_month', [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()])->first();

            if (! $bill) {
                throw new ActionProblem("Walay bill si {$tenant->full_name} para sa {$date->format('F Y')}.");
            }

            return $bill;
        }

        $bill = $query->when($unpaidOnly, fn ($query) => $query->where('status', '!=', 'paid'))->orderBy('billing_month')->first();

        if (! $bill) {
            throw new ActionProblem("Walay bill si {$tenant->full_name} nga wala pa nabayri.");
        }

        return $bill;
    }

    public static function applicant(?string $nameOrEmail): User
    {
        $matches = Search::apply(User::where('role', 'tenant')->whereNull('tenant_approved_at'), $nameOrEmail, ['name', 'email'])->limit(10)->get();

        return self::one($matches, "applicant nga \"{$nameOrEmail}\" nga naghulat og approval", fn (User $user) => "{$user->name} ({$user->email})");
    }

    public static function pendingReceipt(Tenant $tenant): PaymentSubmission
    {
        $submission = PaymentSubmission::where('tenant_id', $tenant->id)->where('status', 'pending')->oldest()->first();

        if (! $submission) {
            throw new ActionProblem("Walay receipt si {$tenant->full_name} nga naghulat og check.");
        }

        return $submission;
    }

    public static function concern(?string $tenantName, ?string $subject): Concern
    {
        $query = Concern::with('tenant')->latest();

        if (filled($tenantName)) {
            $query->where('tenant_id', self::tenant($tenantName)->id);
        }

        if (filled($subject)) {
            Search::apply($query, $subject, ['subject', 'message']);
        }

        $open = (clone $query)->whereIn('status', ['open', 'in-review'])->limit(10)->get();
        $matches = $open->isNotEmpty() ? $open : $query->limit(10)->get();

        return self::one($matches, 'concern', fn (Concern $concern) => "\"{$concern->subject}\" ni {$concern->tenant?->full_name}");
    }

    public static function repair(?string $roomNumber, ?string $tenantName): MaintenanceRequest
    {
        $query = MaintenanceRequest::with(['room', 'tenant'])->whereIn('status', ['submitted', 'in-progress', 'awaiting-verification'])->latest();

        if (filled($roomNumber)) {
            $query->where('room_id', self::room($roomNumber)->id);
        }

        if (filled($tenantName)) {
            $query->where('tenant_id', self::tenant($tenantName)->id);
        }

        return self::one($query->limit(10)->get(), 'repair nga wala pa nahuman', fn (MaintenanceRequest $repair) => "Room {$repair->room?->room_number}: ".str($repair->description)->limit(40));
    }

    public static function visitorInside(?string $name): Visitor
    {
        $matches = Search::apply(Visitor::with('tenant')->whereDate('visit_date', today())->whereNull('time_out'), $name, ['visitor_name'])->limit(10)->get();

        return self::one($matches, "bisita nga \"{$name}\" nga naa pa sa sulod karon", fn (Visitor $visitor) => "{$visitor->visitor_name} (bisita ni {$visitor->tenant?->full_name})");
    }

    public static function announcement(?string $title): Announcement
    {
        $matches = Search::apply(Announcement::query()->latest(), $title, ['title'])->limit(10)->get();

        return self::one($matches, "announcement nga \"{$title}\"", fn (Announcement $announcement) => "\"{$announcement->title}\"");
    }

    public static function month(?string $month): Carbon
    {
        if (blank($month)) {
            return now()->startOfMonth();
        }

        try {
            return preg_match('/^\d{4}-\d{2}$/', $month)
                ? Carbon::createFromFormat('!Y-m', $month)
                : Carbon::parse($month)->startOfMonth();
        } catch (Throwable) {
            throw new ActionProblem("Wala nako masabti ang bulan nga \"{$month}\". Gamita ang YYYY-MM, pananglitan 2026-10.");
        }
    }

    public static function date(?string $date, ?Carbon $default = null): string
    {
        if (blank($date)) {
            return ($default ?? today())->toDateString();
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (Throwable) {
            throw new ActionProblem("Wala nako masabti ang petsa nga \"{$date}\". Gamita ang YYYY-MM-DD.");
        }
    }

    public static function amount(mixed $amount, string $what = 'amount'): float
    {
        $value = is_string($amount) ? (float) preg_replace('/[^\d.]/', '', $amount) : (float) $amount;

        if ($value <= 0) {
            throw new ActionProblem("Pila ang {$what}? Wala nahatag o sayop ang kantidad.");
        }

        return round($value, 2);
    }

    /**
     * @template T
     *
     * @param  \Illuminate\Support\Collection<int, T>  $matches
     * @return T
     */
    private static function one($matches, string $what, callable $label)
    {
        if ($matches->isEmpty()) {
            throw new ActionProblem("Wala koy nakit-an nga {$what}.");
        }

        if ($matches->count() > 1) {
            throw new ActionProblem('Daghan ang parehas: '.$matches->map($label)->implode('; ').'. Asa man niini?');
        }

        return $matches->first();
    }
}
