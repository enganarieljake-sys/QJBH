<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use Illuminate\Http\Request;

class TenantBillController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->isApprovedTenant()) {
            return view('tenant.bills.index', ['tenant' => null, 'bills' => collect(), 'approvalPending' => true]);
        }

        $tenant = $request->user()->tenant;

        if (! $tenant) {
            return view('tenant.bills.index', ['tenant' => null, 'bills' => collect(), 'approvalPending' => false]);
        }

        $bills = Bill::with(['room', 'payments'])
            ->where('tenant_id', $tenant->id)
            ->latest('billing_month')
            ->paginate(10);

        $bills->getCollection()->each->updatePaymentStatus();

        return view('tenant.bills.index', compact('tenant', 'bills'));
    }

    public function show(Request $request, Bill $bill)
    {
        $tenant = $request->user()->tenant;

        abort_if(! $request->user()->isApprovedTenant() || ! $tenant || (int) $bill->tenant_id !== (int) $tenant->id, 403);

        $bill->load(['room', 'payments' => fn ($query) => $query->latest('payment_date')]);
        $bill->updatePaymentStatus();

        return view('tenant.bills.show', compact('tenant', 'bill'));
    }
}
