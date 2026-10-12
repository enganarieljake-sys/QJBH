@extends('layouts.app')

@section('title', 'My Bills')
@section('page-title', 'My Bills')
@section('page-actions')
    @if ($tenant)
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
                        <th class="px-4 py-3">Month</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Paid</th>
                        <th class="px-4 py-3">Balance</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($bills as $bill)
                        <tr>
                            <td class="px-4 py-3">{{ $bill->billing_month?->format('F Y') }}</td>
                            <td class="px-4 py-3">PHP {{ number_format($bill->total_amount, 2) }}</td>
                            <td class="px-4 py-3">PHP {{ number_format($bill->paid_amount, 2) }}</td>
                            <td class="px-4 py-3">PHP {{ number_format($bill->balance, 2) }}</td>
                            <td class="px-4 py-3 capitalize">{{ $bill->status }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('tenant.bills.show', $bill) }}" class="font-medium text-slate-800 hover:underline">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No bills yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $bills->links() }}</div>
    @endif
@endsection
