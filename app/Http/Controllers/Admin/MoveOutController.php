<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Notifications\ActivityNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MoveOutController extends Controller
{
    public function create(Tenant $tenant)
    {
        abort_if($tenant->status === 'moved-out', 404);

        $tenant->load(['room', 'bills.payments']);

        return view('admin.tenants.move-out', [
            'tenant' => $tenant,
            'unpaidBalance' => $tenant->bills->sum(fn ($bill) => $bill->balance),
        ]);
    }

    public function store(Request $request, Tenant $tenant)
    {
        abort_if($tenant->status === 'moved-out', 404);

        $deposit = (float) $tenant->deposit_amount;
        $data = $request->validate([
            'move_out_date' => ['required', 'date', 'after_or_equal:'.$tenant->move_in_date?->toDateString()],
            'deposit_deductions' => ['nullable', 'numeric', 'min:0', 'max:'.$deposit],
            'move_out_notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'deposit_deductions.max' => 'Deductions cannot be more than the deposit of PHP '.number_format($deposit, 2).'.',
        ]);

        $deductions = (float) ($data['deposit_deductions'] ?? 0);
        $refund = max($deposit - $deductions, 0);

        $depositStatus = match (true) {
            $deposit <= 0 => 'none',
            $refund <= 0 => 'forfeited',
            $deductions > 0 => 'partially-refunded',
            default => 'refunded',
        };

        DB::transaction(function () use ($tenant, $data, $deductions, $refund, $depositStatus) {
            $room = $tenant->room;

            $tenant->update([
                'status' => 'moved-out',
                'move_out_date' => $data['move_out_date'],
                'deposit_deductions' => $deductions,
                'deposit_refund' => $refund,
                'deposit_status' => $depositStatus,
                'move_out_notes' => $data['move_out_notes'] ?? null,
            ]);

            $room?->syncOccupancyStatus();
        });

        $tenant->user?->notify(new ActivityNotification([
            'title' => 'Move-out recorded',
            'message' => 'Your move-out on '.$tenant->move_out_date->format('M j, Y').' was recorded. Deposit refund: PHP '.number_format($refund, 2).'.',
            'url' => route('tenant.dashboard'),
            'kind' => 'move-out',
        ]));

        return redirect()->route('admin.tenants.show', $tenant)->with('success', 'Move-out recorded. Refund PHP '.number_format($refund, 2).' to the tenant.');
    }
}
