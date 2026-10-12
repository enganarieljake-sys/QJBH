@extends('layouts.app')

@section('title', 'Review Receipt')
@section('page-title', 'Review Receipt')
@section('page-actions')
    <a href="{{ route('admin.payment-submissions.index', ['status' => $submission->status]) }}" aria-label="Back to receipts" title="Back to receipts" class="flex h-10 w-10 items-center justify-center rounded-md bg-slate-900 text-base font-black leading-none text-white shadow-sm hover:bg-slate-700"><svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg></a>
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1fr_1fr]">
        <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold text-slate-950">Receipt screenshot</h2>
            @if ($submission->hasReceiptImage())
                <a href="{{ route('receipts.image', $submission) }}" target="_blank" rel="noopener" class="mt-4 block">
                    <img src="{{ route('receipts.image', $submission) }}" alt="Payment receipt from {{ $submission->tenant?->full_name }}" class="receipt-image mx-auto max-h-[70vh] w-auto rounded-md">
                </a>
                <p class="mt-2 text-center text-xs text-slate-500">Tap the image to open it full size.</p>
            @else
                <p class="mt-4 rounded-md border border-dashed border-slate-300 p-6 text-center text-sm text-slate-600">
                    The image was deleted on {{ $submission->receipt_purged_at->format('M j, Y') }} to save storage. The payment details are kept.
                </p>
            @endif
        </section>

        <div class="space-y-6">
            <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-semibold text-slate-950">Details</h2>
                    <span class="rounded-md border px-2 py-1 text-xs font-semibold capitalize {{ ['pending' => 'border-amber-200 bg-amber-50 text-amber-800', 'approved' => 'border-emerald-200 bg-emerald-50 text-emerald-800', 'rejected' => 'border-rose-200 bg-rose-50 text-rose-800'][$submission->status] }}">{{ $submission->status }}</span>
                </div>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-slate-500">Tenant</dt><dd class="font-medium">{{ $submission->tenant?->full_name }} · Room {{ $submission->tenant?->room?->room_number }}</dd></div>
                    <div><dt class="text-slate-500">Bill</dt><dd class="font-medium"><a href="{{ route('admin.bills.show', $submission->bill) }}" class="hover:underline">{{ $submission->bill?->billing_month?->format('F Y') }}</a></dd></div>
                    <div><dt class="text-slate-500">Amount claimed</dt><dd class="font-medium">PHP {{ number_format($submission->amount, 2) }}</dd></div>
                    <div><dt class="text-slate-500">Bill balance now</dt><dd class="font-medium">PHP {{ number_format($submission->bill->balance, 2) }}</dd></div>
                    <div><dt class="text-slate-500">Paid via</dt><dd class="font-medium">{{ $submission->channel?->name ?? 'Not specified' }}</dd></div>
                    <div><dt class="text-slate-500">Reference no.</dt><dd class="font-medium">{{ $submission->reference_number ?: 'None' }}</dd></div>
                    <div><dt class="text-slate-500">Payment date</dt><dd class="font-medium">{{ $submission->payment_date?->format('Y-m-d') }}</dd></div>
                    <div><dt class="text-slate-500">Sent</dt><dd class="font-medium">{{ $submission->created_at->format('Y-m-d g:i A') }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-slate-500">Tenant note</dt><dd class="font-medium">{{ $submission->notes ?: 'None' }}</dd></div>
                    @if (! $submission->isPending())
                        <div><dt class="text-slate-500">Reviewed by</dt><dd class="font-medium">{{ $submission->reviewer?->name ?? 'Admin' }} · {{ $submission->reviewed_at?->format('Y-m-d g:i A') }}</dd></div>
                        @if ($submission->payment)
                            <div><dt class="text-slate-500">Payment record</dt><dd class="font-medium"><a href="{{ route('admin.payments.show', $submission->payment) }}" class="hover:underline">View payment</a></dd></div>
                        @endif
                        @if ($submission->rejection_reason)
                            <div class="sm:col-span-2"><dt class="text-slate-500">Rejection reason</dt><dd class="font-medium">{{ $submission->rejection_reason }}</dd></div>
                        @endif
                    @endif
                </dl>
            </section>

            @if ($submission->isPending())
                <form method="POST" action="{{ route('admin.payment-submissions.approve', $submission) }}" class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    @csrf
                    @method('PATCH')
                    <h2 class="font-semibold text-slate-950">Approve</h2>
                    <p class="mt-1 text-sm text-slate-600">Confirm the amount you actually received. This creates a payment record and updates the bill.</p>
                    <label for="amount" class="mt-4 block text-sm font-medium text-slate-700">Amount received (PHP)</label>
                    <input id="amount" type="number" name="amount" step="0.01" min="0.01" max="{{ $submission->bill->balance }}" value="{{ old('amount', min((float) $submission->amount, $submission->bill->balance)) }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <button class="mt-4 w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 sm:w-auto">Approve &amp; Record Payment</button>
                </form>

                <form method="POST" action="{{ route('admin.payment-submissions.reject', $submission) }}" class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    @csrf
                    @method('PATCH')
                    <h2 class="font-semibold text-slate-950">Reject</h2>
                    <label for="rejection_reason" class="mt-2 block text-sm font-medium text-slate-700">Reason (the tenant will see this)</label>
                    <textarea id="rejection_reason" name="rejection_reason" rows="2" maxlength="500" required placeholder="Payment not found in GCash history. Please check the reference number." class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('rejection_reason') }}</textarea>
                    <button class="mt-4 w-full rounded-md border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 sm:w-auto">Reject Receipt</button>
                </form>
            @endif
        </div>
    </div>
@endsection
