<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

class DashboardPeople
{
    /** @return Collection<int, User> */
    public static function for(User $viewer): Collection
    {
        $query = User::withPresence()->whereKeyNot($viewer->getKey());

        if ($viewer->isTenant()) {
            $query->whereIn('role', User::STAFF_ROLES);
        } else {
            $query->where(fn ($users) => $users
                ->where('role', '!=', 'tenant')
                ->orWhereNotNull('tenant_approved_at'));
        }

        $rank = match ($viewer->role) {
            'admin' => ['tenant' => 0, 'caretaker' => 1, 'maintenance' => 2, 'admin' => 3],
            'caretaker' => ['admin' => 0, 'tenant' => 1, 'caretaker' => 2, 'maintenance' => 3],
            'tenant' => ['admin' => 0, 'caretaker' => 1, 'maintenance' => 2],
            default => ['admin' => 0, 'caretaker' => 1, 'tenant' => 2, 'maintenance' => 3],
        };

        return $query->get()
            ->sort(function (User $left, User $right) use ($rank) {
                $roleOrder = ($rank[$left->role] ?? 9) <=> ($rank[$right->role] ?? 9);
                if ($roleOrder !== 0) {
                    return $roleOrder;
                }

                $onlineOrder = ($right->isOnline() ? 1 : 0) <=> ($left->isOnline() ? 1 : 0);
                if ($onlineOrder !== 0) {
                    return $onlineOrder;
                }

                $lastSeenOrder = ($right->lastSeenAt()?->timestamp ?? 0) <=> ($left->lastSeenAt()?->timestamp ?? 0);

                return $lastSeenOrder !== 0 ? $lastSeenOrder : strcasecmp($left->name, $right->name);
            })
            ->values();
    }
}
