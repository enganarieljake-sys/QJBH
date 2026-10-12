@extends('layouts.app')

@section('title', 'My Payments')
@section('page-title', 'My Payments')
@section('page-actions')
    @if ($tenant)
        @include('partials.qr-scanner')
        <a href="{{ route('tenant.receipts.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">My Receipts</a>
        <a href="{{ route('tenant.receipts.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Pay &amp; Send Receipt</a>
    @endif
@endsection

@section('content')
    @if (! $tenant)
        <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200 text-sm text-slate-600">
            {{ ($approvalPending ?? false) ? 'Your account is still waiting for admin approval.' : 'Your account is not linked to a tenant record yet.' }}
        </div>
    @else
        <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Bill</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Method</th>
                        <th class="px-4 py-3">Reference</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="px-4 py-3">{{ $payment->payment_date?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">{{ $payment->bill?->billing_month?->format('F Y') }}</td>
                            <td class="px-4 py-3">PHP {{ number_format($payment->amount, 2) }}</td>
                            <td class="px-4 py-3">{{ $payment->payment_method }}</td>
                            <td class="px-4 py-3">{{ $payment->reference_number ?: 'None' }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('payments.official-receipt', $payment) }}" class="font-medium text-slate-800 hover:underline">Official receipt</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $payments->links() }}</div>
    @endif
@endsection
