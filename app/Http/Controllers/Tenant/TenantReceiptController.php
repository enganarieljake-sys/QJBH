<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\PaymentChannel;
use App\Models\PaymentSubmission;
use App\Models\User;
use App\Notifications\ActivityNotification;
use App\Support\PaymentFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class TenantReceiptController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $this->tenant($request);

        $submissions = $tenant
            ? PaymentSubmission::with(['bill', 'channel'])->where('tenant_id', $tenant->id)->latest()->paginate(10)
            : collect();

        return view('tenant.receipts.index', compact('tenant', 'submissions'));
    }

    public function create(Request $request)
    {
        $tenant = $this->tenant($request);
        abort_unless($tenant, 403, 'Your tenant account is not active yet.');

        $bills = Bill::with('payments')
            ->where('tenant_id', $tenant->id)
            ->where('status', '!=', 'paid')
            ->orderBy('due_date')
            ->get();

        $selectedBill = $bills->firstWhere('id', (int) $request->query('bill_id')) ?? $bills->first();

        return view('tenant.receipts.create', [
            'tenant' => $tenant,
            'bills' => $bills,
            'selectedBill' => $selectedBill,
            'channels' => PaymentChannel::active()->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $tenant = $this->tenant($request);
        abort_unless($tenant, 403, 'Your tenant account is not active yet.');

        $data = $request->validate([
            'bill_id' => ['required', Rule::exists('bills', 'id')->where('tenant_id', $tenant->id)],
            'payment_channel_id' => ['nullable', Rule::exists('payment_channels', 'id')->where('is_active', true)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'receipt' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'receipt.required' => 'Please attach a screenshot of your payment receipt.',
            'receipt.max' => 'The receipt image must be 5 MB or smaller.',
        ]);

        $bill = Bill::with('payments')->findOrFail($data['bill_id']);

        if ((float) $data['amount'] > $bill->balance) {
            return back()->withInput()->withErrors([
                'amount' => 'Amount cannot be greater than your remaining balance (PHP '.number_format($bill->balance, 2).').',
            ]);
        }

        ['disk' => $data['receipt_disk'], 'path' => $data['receipt_path']] = PaymentFiles::store($request->file('receipt'), 'payment-receipts');
        unset($data['receipt']);

        $submission = PaymentSubmission::create($data + ['tenant_id' => $tenant->id]);

        Notification::send(User::staff(['admin', 'caretaker']), new ActivityNotification([
            'title' => 'New payment receipt',
            'message' => $tenant->full_name.' sent a receipt for PHP '.number_format((float) $submission->amount, 2).'.',
            'url' => route('admin.payment-submissions.show', $submission),
            'kind' => 'payment',
        ]));

        return redirect()->route('tenant.receipts.index')->with('success', 'Receipt sent! The admin will confirm your payment soon.');
    }

    private function tenant(Request $request)
    {
        return $request->user()->isApprovedTenant() ? $request->user()->tenant : null;
    }
}
