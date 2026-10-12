<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class TenantPaymentController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->isApprovedTenant()) {
            return view('tenant.payments.index', ['tenant' => null, 'payments' => collect(), 'approvalPending' => true]);
        }

        $tenant = $request->user()->tenant;

        if (! $tenant) {
            return view('tenant.payments.index', ['tenant' => null, 'payments' => collect(), 'approvalPending' => false]);
        }

        $payments = Payment::with(['bill.room'])
            ->where('tenant_id', $tenant->id)
            ->latest('payment_date')
            ->paginate(10);

        return view('tenant.payments.index', compact('tenant', 'payments'));
    }
}
