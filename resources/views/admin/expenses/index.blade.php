@extends('layouts.app')

@section('title', 'Expenses')
@section('page-title', 'Expenses')
@section('page-subtitle', 'Track what the boarding house spends to see the real monthly profit.')
@section('page-actions')
    <a href="{{ route('admin.expenses.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Add Expense</a>
@endsection

@section('content')
    <form method="GET" class="mb-5 grid gap-3 rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200 md:grid-cols-3">
        @include('partials.search-input', ['placeholder' => 'Search description…'])
        <input name="month" type="month" value="{{ $month->format('Y-m') }}" aria-label="Month" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <select name="category" aria-label="Category" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">All categories</option>
            @foreach ($categories as $value => $label)
                <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Filter</button>
    </form>

    @php $net = $income - $totalExpenses; @endphp
    <div class="stat-grid mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200"><p class="text-xs uppercase text-slate-500">Income ({{ $month->format('M Y') }})</p><p class="mt-2 text-2xl font-bold">PHP {{ number_format($income, 2) }}</p></div>
        <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200"><p class="text-xs uppercase text-slate-500">Expenses</p><p class="mt-2 text-2xl font-bold">PHP {{ number_format($totalExpenses, 2) }}</p></div>
        <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200"><p class="text-xs uppercase text-slate-500">Net income</p><p class="mt-2 text-2xl font-bold {{ $net < 0 ? 'text-rose-600' : '' }}">PHP {{ number_format($net, 2) }}</p></div>
    </div>

    @if ($byCategory->isNotEmpty())
        <section class="mb-5 rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200" aria-label="Spending by category">
            <h2 class="text-sm font-semibold text-slate-950">Where the money went</h2>
            <div class="mt-3 space-y-2">
                @foreach ($byCategory as $category => $total)
                    <div class="text-sm">
                        <div class="flex justify-between gap-3"><span>{{ $categories[$category] ?? ucfirst($category) }}</span><span class="font-semibold">PHP {{ number_format($total, 2) }}</span></div>
                        <div class="expense-bar mt-1" role="presentation"><span style="width: {{ $totalExpenses > 0 ? round($total / $totalExpenses * 100, 1) : 0 }}%"></span></div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Description</th>
                    <th class="px-4 py-3">Amount</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($expenses as $expense)
                    <tr>
                        <td class="px-4 py-3">{{ $expense->expense_date->format('M j, Y') }}</td>
                        <td class="px-4 py-3">{{ $expense->categoryLabel() }}</td>
                        <td class="px-4 py-3 max-w-md truncate">{{ $expense->description }}</td>
                        <td class="px-4 py-3 font-semibold">PHP {{ number_format($expense->amount, 2) }}</td>
                        <td class="px-4 py-3 text-right">@staffcan('admin.expenses.edit')<a href="{{ route('admin.expenses.edit', $expense) }}" class="font-medium text-slate-800 hover:underline">Edit</a>@endstaffcan</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No expenses recorded for {{ $month->format('F Y') }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $expenses->links() }}</div>
@endsection
