@extends('layouts.app')

@section('title', 'Record Payment')
@section('page-title', 'Record Payment')

@section('content')
    <form method="POST" action="{{ route('admin.payments.store') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @csrf
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Bill</label>
                <select id="bill_id" name="bill_id" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Select bill</option>
                    @foreach ($bills as $bill)
                        <option value="{{ $bill->id }}" data-tenant-id="{{ $bill->tenant_id }}" data-balance="{{ $bill->balance }}" @selected((string) old('bill_id', $selectedBill?->id) === (string) $bill->id)>
                            {{ $bill->tenant?->full_name }} - {{ $bill->billing_month?->format('F Y') }} - Balance PHP {{ number_format($bill->balance, 2) }}
                        </option>
                    @endforeach
                </select>
                <input id="tenant_id" name="tenant_id" type="hidden" value="{{ old('tenant_id', $selectedBill?->tenant_id) }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Payment date</label>
                <input name="payment_date" type="date" value="{{ old('payment_date', optional($payment->payment_date)->format('Y-m-d')) }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Amount</label>
                <input id="amount" name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Payment method</label>
                <select name="payment_method" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    @foreach ($methods as $method)
                        <option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ $method }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Reference number</label>
                <input name="reference_number" value="{{ old('reference_number') }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Remarks</label>
                <input name="remarks" value="{{ old('remarks') }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
        </div>
        <div class="mt-6 flex gap-2">
            <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Save Payment</button>
            <a href="{{ route('admin.payments.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
        </div>
    </form>

    <script>
        const billSelect = document.getElementById('bill_id');
        const tenantInput = document.getElementById('tenant_id');
        const amountInput = document.getElementById('amount');
        const syncBill = () => {
            const option = billSelect.options[billSelect.selectedIndex];
            if (!option) return;
            tenantInput.value = option.dataset.tenantId || '';
            amountInput.max = option.dataset.balance || '';
        };
        billSelect?.addEventListener('change', syncBill);
        syncBill();
    </script>
@endsection
