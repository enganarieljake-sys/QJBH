@extends('layouts.app')

@section('title', 'Move Out '.$tenant->full_name)
@section('page-title', 'Move Out')
@section('page-subtitle', $tenant->full_name.($tenant->room ? ' · Room '.$tenant->room->room_number : ''))
@section('page-actions')
    <a href="{{ route('admin.tenants.show', $tenant) }}" aria-label="Back to tenant" title="Back to tenant" class="flex h-10 w-10 items-center justify-center rounded-md bg-slate-900 text-base font-black leading-none text-white shadow-sm hover:bg-slate-700"><svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg></a>
@endsection

@section('content')
    <div class="grid max-w-4xl gap-6 lg:grid-cols-[1fr_1.2fr]">
        <section class="h-fit rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold text-slate-950">Summary</h2>
            <dl class="mt-4 grid gap-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Moved in</dt><dd class="font-medium">{{ $tenant->move_in_date?->format('M j, Y') }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Security deposit</dt><dd class="font-medium">PHP {{ number_format($tenant->deposit_amount, 2) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Deposit status</dt><dd class="font-medium capitalize">{{ $tenant->deposit_status === 'held' ? 'Held' : 'Not paid' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Unpaid bills</dt><dd class="font-medium {{ $unpaidBalance > 0 ? 'text-rose-600' : '' }}">PHP {{ number_format($unpaidBalance, 2) }}</dd></div>
            </dl>
            @if ($unpaidBalance > 0)
                <p class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">This tenant still owes PHP {{ number_format($unpaidBalance, 2) }}. You can deduct it from the deposit below.</p>
            @endif
        </section>

        <form method="POST" action="{{ route('admin.tenants.move-out.store', $tenant) }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200" onsubmit="return confirm('Record this move-out? The tenant will be marked as moved out and the room slot freed.')">
            @csrf
            <h2 class="font-semibold text-slate-950">Record move-out</h2>
            <div class="mt-4 space-y-4">
                <div>
                    <label for="move_out_date" class="block text-sm font-medium text-slate-700">Move-out date</label>
                    <input id="move_out_date" name="move_out_date" type="date" required value="{{ old('move_out_date', today()->toDateString()) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    @error('move_out_date') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="deposit_deductions" class="block text-sm font-medium text-slate-700">Deductions from deposit (PHP)</label>
                    <input id="deposit_deductions" name="deposit_deductions" type="number" step="0.01" min="0" max="{{ (float) $tenant->deposit_amount }}" value="{{ old('deposit_deductions', min($unpaidBalance, (float) $tenant->deposit_amount)) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <p class="mt-1 text-xs text-slate-500">Unpaid bills, damages, cleaning, or missing items.</p>
                    @error('deposit_deductions') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="move_out_notes" class="block text-sm font-medium text-slate-700">Notes <span class="text-slate-500">(optional)</span></label>
                    <textarea id="move_out_notes" name="move_out_notes" rows="4" maxlength="2000" placeholder="e.g. Broken lock replaced, PHP 500" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('move_out_notes') }}</textarea>
                </div>
            </div>
            <button class="mt-6 w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Record Move-out</button>
        </form>
    </div>
@endsection
