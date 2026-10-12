@extends('layouts.app')

@section('title', 'Billing & Payment Tracking')
@section('page-title', 'Billing & Payment Tracking')
@section('page-actions')
    <a href="{{ route('admin.payments.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">View Payments</a>
    @include('admin.payment-submissions._links')
    <form method="POST" action="{{ route('admin.bills.send-reminders') }}" onsubmit="return confirm('Send reminders now to tenants with bills due soon or overdue? Each bill is only reminded once.')">
        @csrf
        <button class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Send Reminders</button>
    </form>
    @staffcan('admin.bills.create')
    <a href="{{ route('admin.bills.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Create Bill</a>
    @endstaffcan
@endsection

@section('content')
    <h2 class="mb-4 text-lg font-semibold text-slate-950">Billing Records</h2>

    @staffcan('admin.bills.generate')
    <form method="POST" action="{{ route('admin.bills.generate') }}" class="mb-5 grid gap-3 rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200 md:grid-cols-5">
        @csrf
        <input name="billing_month" type="month" value="{{ now()->format('Y-m') }}" required class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <input name="due_date" type="date" value="{{ now()->startOfMonth()->addDays(9)->format('Y-m-d') }}" required class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <input name="electricity_amount" type="number" step="0.01" min="0" placeholder="Electricity (if no meter)" title="Used only for rooms without a meter reading this month" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <input name="water_amount" type="number" step="0.01" min="0" placeholder="Water (if no meter)" title="Used only for rooms without a meter reading this month" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <button class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Generate Monthly Bills</button>
    </form>
    @endstaffcan

    <form method="GET" class="mb-5 grid gap-3 rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200 md:grid-cols-4">
        @include('partials.search-input', ['placeholder' => 'Search tenant name or room number…'])
        <select name="tenant_id" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">All tenants</option>
            @foreach ($tenants as $tenant)
                <option value="{{ $tenant->id }}" @selected((string) ($filters['tenant_id'] ?? '') === (string) $tenant->id)>{{ $tenant->full_name }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <input name="billing_month" type="month" value="{{ $filters['billing_month'] ?? '' }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <button class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Filter</button>
    </form>

    <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Tenant</th>
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
                        <td class="px-4 py-3">{{ $bill->tenant?->full_name }}</td>
                        <td class="px-4 py-3">{{ $bill->billing_month?->format('F Y') }}</td>
                        <td class="px-4 py-3">PHP {{ number_format($bill->total_amount, 2) }}</td>
                        <td class="px-4 py-3">PHP {{ number_format($bill->paid_amount, 2) }}</td>
                        <td class="px-4 py-3">PHP {{ number_format($bill->balance, 2) }}</td>
                        <td class="px-4 py-3 capitalize">{{ $bill->status }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.bills.show', $bill) }}" class="font-medium text-slate-800 hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">No bills found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $bills->links() }}</div>
@endsection
