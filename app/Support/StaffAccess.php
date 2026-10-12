<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Which admin-area pages each staff role may open. The owner (admin) can do
 * everything; a caretaker runs the day-to-day; a maintenance worker (panday)
 * only handles repairs. Used by the route middleware, the menu, and buttons.
 */
class StaffAccess
{
    /** Route-name patterns per role ("*" matches anything). */
    private const ALLOWED = [
        'caretaker' => [
            'admin.dashboard',
            'admin.rooms.index', 'admin.rooms.show',
            'admin.tenants.index', 'admin.tenants.show', 'admin.tenants.create', 'admin.tenants.store',
            'admin.tenants.edit', 'admin.tenants.update', 'admin.tenants.invite',
            'admin.bills.index', 'admin.bills.show', 'admin.bills.send-reminders',
            'admin.payments.*',
            'admin.payment-submissions.*',
            'admin.utilities.*',
            'admin.expenses.index', 'admin.expenses.create', 'admin.expenses.store',
            'admin.announcements.index', 'admin.announcements.create', 'admin.announcements.store',
            'admin.announcements.edit', 'admin.announcements.update',
            'admin.maintenance-requests.index', 'admin.maintenance-requests.show', 'admin.maintenance-requests.create',
            'admin.maintenance-requests.store', 'admin.maintenance-requests.edit', 'admin.maintenance-requests.update',
            'admin.maintenance-requests.assign', 'admin.maintenance-requests.progress',
            'admin.maintenance-requests.tools.photo',
            'admin.maintenance-history.index', 'admin.maintenance-history.show',
            'admin.checklists.index', 'admin.checklists.submit', 'admin.checklists.missed-reason',
            'admin.concerns.index', 'admin.concerns.show', 'admin.concerns.update',
            'admin.visitors.*',
        ],
        // A panday works their ticket inbox: see forwarded repairs and report progress.
        'maintenance' => [
            'admin.maintenance-requests.index', 'admin.maintenance-requests.show', 'admin.maintenance-requests.progress',
            'admin.maintenance-requests.tools.upload', 'admin.maintenance-requests.tools.return', 'admin.maintenance-requests.tools.photo',
            'admin.maintenance-history.index', 'admin.maintenance-history.show', 'admin.maintenance-history.trigger-admin',
        ],
    ];

    public const LABELS = [
        'admin' => 'Owner (Admin)',
        'caretaker' => 'Caretaker',
        'maintenance' => 'Maintenance (Panday)',
        'tenant' => 'Tenant',
    ];

    public static function allows(?User $user, ?string $routeName): bool
    {
        if (! $user || ! $routeName) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return collect(self::ALLOWED[$user->role] ?? [])->contains(fn (string $pattern) => Str::is($pattern, $routeName));
    }

    /**
     * Where a staff member lands after signing in.
     */
    public static function home(User $user): string
    {
        return match ($user->role) {
            'maintenance' => route('admin.maintenance-requests.index'),
            'tenant' => route('tenant.dashboard'),
            default => route('admin.dashboard'),
        };
    }
}
