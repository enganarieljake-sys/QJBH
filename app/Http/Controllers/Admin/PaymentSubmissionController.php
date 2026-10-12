<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentSubmission;
use App\Notifications\ActivityNotification;
use App\Support\Search;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentSubmissionController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->status, PaymentSubmission::STATUSES, true) ? $request->status : 'pending';

        $submissions = PaymentSubmission::with(['tenant', 'bill', 'channel'])
            ->where('status', $status)
            ->tap(fn ($query) => Search::apply($query, $request->input('q'), ['reference_number', 'tenant.full_name']))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $counts = PaymentSubmission::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.payment-submissions.index', compact('submissions', 'status', 'counts'));
    }

    public function show(PaymentSubmission $paymentSubmission)
    {
        $paymentSubmission->load(['tenant.room', 'bill.payments', 'channel', 'payment', 'reviewer']);

        return view('admin.payment-submissions.show', ['submission' => $paymentSubmission]);
    }

    public function approve(Request $request, PaymentSubmission $paymentSubmission)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $submission = DB::transaction(function () use ($paymentSubmission, $data, $request) {
            $submission = PaymentSubmission::whereKey($paymentSubmission->id)->lockForUpdate()->firstOrFail();

            if (! $submission->isPending()) {
                throw ValidationException::withMessages(['amount' => 'This receipt was already reviewed.']);
            }

            $bill = Bill::whereKey($submission->bill_id)->lockForUpdate()->firstOrFail();

            if ((float) $data['amount'] > $bill->balance) {
                throw ValidationException::withMessages([
                    'amount' => 'Amount cannot be greater than the remaining balance (PHP '.number_format($bill->balance, 2).').',
                ]);
            }

            $payment = Payment::create([
                'bill_id' => $bill->id,
                'tenant_id' => $submission->tenant_id,
                'payment_date' => $submission->payment_date,
                'amount' => $data['amount'],
                'payment_method' => $submission->channel?->name ?? 'GCash',
                'reference_number' => $submission->reference_number,
                'remarks' => 'Approved from tenant receipt #'.$submission->id,
            ]);

            $bill->updatePaymentStatus();

            $submission->forceFill([
                'status' => 'approved',
                'payment_id' => $payment->id,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ])->save();

            return $submission;
        });

        $submission->load('tenant.user');
        $submission->tenant?->user?->notify(new ActivityNotification([
            'title' => 'Payment confirmed',
            'message' => 'Your payment of PHP '.number_format((float) $data['amount'], 2).' was confirmed. Tap to view your official receipt.',
            'url' => route('payments.official-receipt', $submission->payment_id),
            'kind' => 'payment',
        ]));

        return redirect()->route('admin.payment-submissions.show', $submission)->with('success', 'Receipt approved and payment recorded.');
    }

    public function reject(Request $request, PaymentSubmission $paymentSubmission)
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        abort_unless($paymentSubmission->isPending(), 422, 'This receipt was already reviewed.');

        $paymentSubmission->forceFill([
            'status' => 'rejected',
            'rejection_reason' => $data['rejection_reason'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();

        $paymentSubmission->load('tenant.user');
        $paymentSubmission->tenant?->user?->notify(new ActivityNotification([
            'title' => 'Receipt not accepted',
            'message' => 'Your receipt was rejected: '.$data['rejection_reason'],
            'url' => route('tenant.receipts.index'),
            'kind' => 'payment',
        ]));

        return redirect()->route('admin.payment-submissions.show', $paymentSubmission)->with('warning', 'Receipt rejected. The tenant has been notified.');
    }
}
