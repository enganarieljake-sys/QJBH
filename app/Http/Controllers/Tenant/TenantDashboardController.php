<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Bill;
use App\Models\MaintenanceRequest;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use App\Support\DashboardPeople;

class TenantDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $now = Carbon::now('Asia/Manila');
        $hour = $now->hour;
        $dashboardHeader = [
            'greeting' => match (true) {
                $hour < 12 => 'Good morning',
                $hour < 18 => 'Good afternoon',
                default => 'Good evening',
            },
            'date' => $now->format('l, F j, Y'),
        ];

        if (! $request->user()->isApprovedTenant()) {
            return view('tenant.dashboard', ['tenant' => null, 'approvalPending' => true, 'dashboardHeader' => $dashboardHeader]);
        }

        $tenant = $request->user()->tenant()->with('room')->first();

        if (! $tenant) {
            return view('tenant.dashboard', ['tenant' => null, 'approvalPending' => false, 'dashboardHeader' => $dashboardHeader]);
        }

        Bill::where('tenant_id', $tenant->id)
            ->where('due_date', '<', today())
            ->where('status', '!=', 'paid')
            ->get()
            ->each
            ->updatePaymentStatus();

        $stats = [
            'pending_bills' => $tenant->bills()->whereIn('status', ['pending', 'overdue'])->count(),
            'total_balance' => $tenant->bills()->with('payments')->get()->sum('balance'),
            'total_paid' => $tenant->payments()->sum('amount'),
            'open_requests' => $tenant->maintenanceRequests()->whereIn('status', ['submitted', 'in-progress', 'awaiting-verification'])->count(),
        ];

        $recentBills = $tenant->bills()->with('payments')->latest('billing_month')->limit(5)->get();
        $recentRequests = MaintenanceRequest::with('room')
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->limit(5)
            ->get();

        $latestAnnouncement = Announcement::forBoard()->first();
        $dashboardPeople = DashboardPeople::for($request->user());

        return view('tenant.dashboard', compact('tenant', 'stats', 'recentBills', 'recentRequests', 'dashboardHeader', 'latestAnnouncement', 'dashboardPeople'));
    }
}
