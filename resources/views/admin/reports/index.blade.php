@extends('layouts.app')

@section('title', 'Reports & Dashboard')
@section('page-title', 'Reports & Dashboard')
@section('page-actions')
    <a href="{{ route('admin.dashboard') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">View Dashboard</a>
    <a href="{{ route('admin.reports.tenants.export') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Export Tenants CSV</a>
    <a href="{{ route('admin.reports.billing.export') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Export Billing CSV</a>
@endsection

@section('content')
    <h2 class="mb-4 text-lg font-semibold text-slate-950">Reports</h2>

    <form method="GET" class="mb-5 flex flex-wrap gap-3 rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200">
        <input name="month" type="month" value="{{ $month }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <button class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Update Report</button>
    </form>

    <div class="stat-grid grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200"><p class="text-xs uppercase text-slate-500">Rooms</p><p class="mt-2 text-2xl font-bold">{{ $roomSummary['total'] }}</p></div>
        <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200"><p class="text-xs uppercase text-slate-500">Available</p><p class="mt-2 text-2xl font-bold">{{ $roomSummary['available'] }}</p></div>
        <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200"><p class="text-xs uppercase text-slate-500">Monthly Income</p><p class="mt-2 text-2xl font-bold">PHP {{ number_format($monthlyIncome, 2) }}</p></div>
        <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200"><p class="text-xs uppercase text-slate-500">Overdue Bills</p><p class="mt-2 text-2xl font-bold">{{ $billingSummary['overdue'] }}</p></div>
        <a href="{{ route('admin.expenses.index', ['month' => $month]) }}" class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200"><p class="text-xs uppercase text-slate-500">Monthly Expenses</p><p class="mt-2 text-2xl font-bold">PHP {{ number_format($monthlyExpenses, 2) }}</p></a>
        <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200"><p class="text-xs uppercase text-slate-500">Net Income</p><p class="mt-2 text-2xl font-bold {{ $monthlyIncome - $monthlyExpenses < 0 ? 'text-rose-600' : '' }}">PHP {{ number_format($monthlyIncome - $monthlyExpenses, 2) }}</p></div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold text-slate-950">Tenant List Report</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead><tr class="text-left text-xs uppercase text-slate-500"><th class="py-2 pr-4">Tenant</th><th class="py-2 pr-4">Room</th><th class="py-2">Status</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($tenants as $tenant)
                            <tr><td class="py-3 pr-4">{{ $tenant->full_name }}</td><td class="py-3 pr-4">{{ $tenant->room?->room_number ?? 'Unassigned' }}</td><td class="py-3 capitalize">{{ $tenant->status }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $tenants->links() }}</div>
        </section>

        <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold text-slate-950">Billing Report</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead><tr class="text-left text-xs uppercase text-slate-500"><th class="py-2 pr-4">Tenant</th><th class="py-2 pr-4">Month</th><th class="py-2 pr-4">Total</th><th class="py-2">Status</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($bills as $bill)
                            <tr><td class="py-3 pr-4">{{ $bill->tenant?->full_name }}</td><td class="py-3 pr-4">{{ $bill->billing_month?->format('M Y') }}</td><td class="py-3 pr-4">PHP {{ number_format($bill->total_amount, 2) }}</td><td class="py-3 capitalize">{{ $bill->status }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $bills->links() }}</div>
        </section>
    </div>

    <section class="mt-6 rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <h2 class="font-semibold text-slate-950">Maintenance Request Report</h2>
        <div class="stat-grid mt-4 grid grid-cols-3 gap-3">
            @foreach (['submitted', 'in-progress', 'awaiting-verification', 'resolved'] as $status)
                <div class="rounded-md border border-slate-200 p-4">
                    <p class="text-xs uppercase text-slate-500">{{ $status }}</p>
                    <p class="mt-2 text-xl font-bold">{{ $maintenanceSummary[$status] ?? 0 }}</p>
                </div>
            @endforeach
        </div>
    </section>
@endsection
