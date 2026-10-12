@extends('layouts.app')

@section('title', 'Payment Details')
@section('page-title', 'Payment Details')
@section('page-actions')
    <a href="{{ route('admin.payments.index') }}" aria-label="Back to payments table" title="Back to payments table" class="flex h-10 w-10 items-center justify-center rounded-md bg-slate-900 text-base font-black leading-none text-white shadow-sm hover:bg-slate-700"><svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg></a>
    <a href="{{ route('payments.official-receipt', $payment) }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Official Receipt</a>
@endsection

@section('content')
    <section class="max-w-3xl rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <dl class="grid gap-4 text-sm md:grid-cols-2">
            <div><dt class="text-slate-500">Tenant</dt><dd class="font-medium">{{ $payment->tenant?->full_name }}</dd></div>
            <div><dt class="text-slate-500">Bill</dt><dd class="font-medium">{{ $payment->bill?->billing_month?->format('F Y') }}</dd></div>
            <div><dt class="text-slate-500">Payment Date</dt><dd class="font-medium">{{ $payment->payment_date?->format('Y-m-d') }}</dd></div>
            <div><dt class="text-slate-500">Amount</dt><dd class="font-medium">PHP {{ number_format($payment->amount, 2) }}</dd></div>
            <div><dt class="text-slate-500">Method</dt><dd class="font-medium">{{ $payment->payment_method }}</dd></div>
            <div><dt class="text-slate-500">Reference</dt><dd class="font-medium">{{ $payment->reference_number ?: 'None' }}</dd></div>
            <div class="md:col-span-2"><dt class="text-slate-500">Remarks</dt><dd class="font-medium">{{ $payment->remarks ?: 'None' }}</dd></div>
        </dl>
    </section>
@endsection
