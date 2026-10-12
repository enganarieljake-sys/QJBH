@extends('layouts.app')

@section('title', 'Bill Details')
@section('page-title', 'Bill Details')
@section('page-actions')
    <a href="{{ route('tenant.bills.index') }}" aria-label="Back to bills table" title="Back to bills table" class="flex h-10 w-10 items-center justify-center rounded-md bg-slate-900 text-base font-black leading-none text-white shadow-sm hover:bg-slate-700"><svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg></a>
    @if ($bill->balance > 0)
        <a href="{{ route('tenant.receipts.create', ['bill_id' => $bill->id]) }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Pay this bill</a>
    @endif
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <dl class="grid gap-4 text-sm">
                <div><dt class="text-slate-500">Billing Month</dt><dd class="font-medium">{{ $bill->billing_month?->format('F Y') }}</dd></div>
                <div><dt class="text-slate-500">Room</dt><dd class="font-medium">{{ $bill->room?->room_number }}</dd></div>
                <div><dt class="text-slate-500">Due Date</dt><dd class="font-medium">{{ $bill->due_date?->format('Y-m-d') }}</dd></div>
                <div><dt class="text-slate-500">Status</dt><dd class="font-medium capitalize">{{ $bill->status }}</dd></div>
                <div><dt class="text-slate-500">Paid</dt><dd class="font-medium">PHP {{ number_format($bill->paid_amount, 2) }}</dd></div>
                <div><dt class="text-slate-500">Balance</dt><dd class="font-medium">PHP {{ number_format($bill->balance, 2) }}</dd></div>
            </dl>
        </section>

        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold text-slate-950">Charges</h2>
            <dl class="mt-4 grid gap-3 text-sm">
                <div class="flex justify-between"><dt>Room rate</dt><dd>PHP {{ number_format($bill->room_rate, 2) }}</dd></div>
                <div class="flex justify-between"><dt>Electricity</dt><dd>PHP {{ number_format($bill->electricity_amount, 2) }}</dd></div>
                <div class="flex justify-between"><dt>Water</dt><dd>PHP {{ number_format($bill->water_amount, 2) }}</dd></div>
                <div class="flex justify-between"><dt>Other charges</dt><dd>PHP {{ number_format($bill->other_charges, 2) }}</dd></div>
                <div class="flex justify-between border-t border-slate-200 pt-3 font-semibold"><dt>Total</dt><dd>PHP {{ number_format($bill->total_amount, 2) }}</dd></div>
            </dl>

            <h2 class="mt-8 font-semibold text-slate-950">Payments</h2>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse ($bill->payments as $payment)
                    <div class="py-3 text-sm">
                        <p class="font-medium">PHP {{ number_format($payment->amount, 2) }}</p>
                        <p class="text-slate-500">{{ $payment->payment_date?->format('Y-m-d') }} - {{ $payment->payment_method }}</p>
                    </div>
                @empty
                    <p class="py-3 text-sm text-slate-500">No payments yet.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
