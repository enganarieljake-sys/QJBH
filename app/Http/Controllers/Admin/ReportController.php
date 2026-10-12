<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Expense;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $roomSummary = [
            'total' => Room::count(),
            'available' => Room::where('status', 'available')->count(),
            'occupied' => Room::where('status', 'occupied')->count(),
            'under_maintenance' => Room::where('status', 'under maintenance')->count(),
        ];

        $monthlyIncome = Payment::whereBetween('payment_date', [$start, $end])->sum('amount');
        $monthlyExpenses = (float) Expense::whereBetween('expense_date', [$start, $end])->sum('amount');

        $billingSummary = [
            'pending' => Bill::where('status', 'pending')->count(),
            'paid' => Bill::where('status', 'paid')->count(),
            'overdue' => Bill::where('status', 'overdue')->count(),
            'total_billed' => Bill::whereBetween('billing_month', [$start, $end])->sum('total_amount'),
        ];

        $maintenanceSummary = collect([
            'submitted' => MaintenanceRequest::where('status', 'submitted')->count(),
            'in-progress' => MaintenanceRequest::where('status', 'in-progress')->count(),
            'awaiting-verification' => MaintenanceRequest::where('status', 'awaiting-verification')->count(),
            'resolved' => MaintenanceRequest::where('status', 'resolved')->count(),
        ]);

        $tenants = Tenant::with('room')->orderBy('full_name')->paginate(10)->withQueryString();
        $bills = Bill::with(['tenant', 'room', 'payments'])->latest('billing_month')->paginate(10, ['*'], 'bills_page')->withQueryString();

        return view('admin.reports.index', compact(
            'month',
            'roomSummary',
            'monthlyIncome',
            'monthlyExpenses',
            'billingSummary',
            'maintenanceSummary',
            'tenants',
            'bills'
        ));
    }

    public function exportTenants(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Full Name', 'Email', 'Contact Number', 'Emergency Contact', 'Room', 'Move-in Date', 'Status']);

            Tenant::with('room')->orderBy('full_name')->chunk(100, function ($tenants) use ($handle) {
                foreach ($tenants as $tenant) {
                    fputcsv($handle, [
                        $tenant->full_name,
                        $tenant->email,
                        $tenant->contact_number,
                        $tenant->emergency_contact,
                        $tenant->room?->room_number ?? 'Unassigned',
                        optional($tenant->move_in_date)->format('Y-m-d'),
                        $tenant->status,
                    ]);
                }
            });

            fclose($handle);
        }, 'tenant-report.csv', ['Content-Type' => 'text/csv']);
    }

    public function exportBilling(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Tenant', 'Room', 'Billing Month', 'Room Rate', 'Electricity', 'Water', 'Other Charges', 'Total', 'Paid', 'Balance', 'Due Date', 'Status']);

            Bill::with(['tenant', 'room', 'payments'])->latest('billing_month')->chunk(100, function ($bills) use ($handle) {
                foreach ($bills as $bill) {
                    fputcsv($handle, [
                        $bill->tenant?->full_name,
                        $bill->room?->room_number,
                        optional($bill->billing_month)->format('F Y'),
                        $bill->room_rate,
                        $bill->electricity_amount,
                        $bill->water_amount,
                        $bill->other_charges,
                        $bill->total_amount,
                        $bill->paid_amount,
                        $bill->balance,
                        optional($bill->due_date)->format('Y-m-d'),
                        $bill->status,
                    ]);
                }
            });

            fclose($handle);
        }, 'billing-report.csv', ['Content-Type' => 'text/csv']);
    }
}
