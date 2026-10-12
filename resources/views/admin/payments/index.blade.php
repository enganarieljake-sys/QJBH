@extends('layouts.app')

@section('title', 'Billing & Payment Tracking')
@section('page-title', 'Billing & Payment Tracking')
@section('page-actions')
    <a href="{{ route('admin.bills.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">View Bills</a>
    @include('admin.payment-submissions._links')
    <a href="{{ route('admin.payments.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Record Payment</a>
@endsection

@section('content')
    <h2 class="mb-4 text-lg font-semibold text-slate-950">Payment Records</h2>
    @include('partials.search-bar', ['placeholder' => 'Search tenant, room, reference number, or method…'])

    <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Tenant</th>
                    <th class="px-4 py-3">Bill</th>
                    <th class="px-4 py-3">Amount</th>
                    <th class="px-4 py-3">Method</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($payments as $payment)
                    <tr>
                        <td class="px-4 py-3">{{ $payment->payment_date?->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">{{ $payment->tenant?->full_name }}</td>
                        <td class="px-4 py-3">{{ $payment->bill?->billing_month?->format('F Y') }}</td>
                        <td class="px-4 py-3">PHP {{ number_format($payment->amount, 2) }}</td>
                        <td class="px-4 py-3">{{ $payment->payment_method }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.payments.show', $payment) }}" class="font-medium text-slate-800 hover:underline">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No payments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $payments->links() }}</div>
@endsection
