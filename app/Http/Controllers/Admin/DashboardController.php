<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Concern;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\PaymentSubmission;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Support\DashboardPeople;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        Bill::where('due_date', '<', today())
            ->where('status', '!=', 'paid')
            ->get()
            ->each
            ->updatePaymentStatus();

        $totalRooms = Room::count();
        $occupiedRooms = Room::where('status', 'occupied')->count();

        $stats = [
            'total_rooms' => $totalRooms,
            'available_rooms' => Room::where('status', 'available')->count(),
            'occupied_rooms' => $occupiedRooms,
            'maintenance_rooms' => Room::where('status', 'under maintenance')->count(),
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'occupancy_rate' => $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0,
            'monthly_revenue' => Payment::whereBetween('payment_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->sum('amount'),
            'overdue_payments' => Bill::where('status', 'overdue')->count(),
            'pending_maintenance' => MaintenanceRequest::whereIn('status', ['submitted', 'in-progress', 'awaiting-verification'])->count(),
            'open_concerns' => Concern::whereIn('status', ['open', 'in-review'])->count(),
            'pending_tenant_approvals' => User::where('role', 'tenant')->whereNull('tenant_approved_at')->count(),
        ];

        $recentBills = Bill::with(['tenant', 'room', 'payments'])
            ->latest()
            ->limit(5)
            ->get();

        $recentRequests = MaintenanceRequest::with(['tenant', 'room'])
            ->latest()
            ->limit(5)
            ->get();

        $stats['outstanding_balance'] = Bill::with('payments')
            ->where('status', '!=', 'paid')
            ->get()
            ->sum(fn (Bill $bill) => $bill->balance);
        $stats['pending_receipts'] = PaymentSubmission::where('status', 'pending')->count();

        $pendingReceipts = PaymentSubmission::with(['tenant', 'bill'])
            ->where('status', 'pending')
            ->latest()
            ->limit(4)
            ->get();

        $now = Carbon::now('Asia/Manila');
        $dashboardHeader = [
            'greeting' => match (true) {
                $now->hour < 12 => 'Good morning',
                $now->hour < 18 => 'Good afternoon',
                default => 'Good evening',
            },
            'date' => $now->format('l, F j, Y'),
            'month' => $now->format('F'),
        ];
        $dashboardPeople = DashboardPeople::for($request->user());

        return view('admin.dashboard', compact('stats', 'recentBills', 'recentRequests', 'pendingReceipts', 'dashboardHeader', 'dashboardPeople'));
    }
}
