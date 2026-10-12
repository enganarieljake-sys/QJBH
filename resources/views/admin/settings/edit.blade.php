@extends('layouts.app')

@section('title', 'Settings')
@section('page-title', 'Settings')
@section('page-subtitle', 'Bill reminders, receipt storage, and house rules.')

@section('content')
    <form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-5xl space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold text-slate-950">Bill reminders</h2>
            <p class="mt-1 text-sm text-slate-600">Tenants get one reminder before the due date and one notice when a bill becomes overdue. Reminders run every morning.</p>
            <div class="mt-4 max-w-xs">
                <label for="reminder_days_before_due" class="block text-sm font-medium text-slate-700">Remind how many days before due</label>
                <input id="reminder_days_before_due" name="reminder_days_before_due" type="number" min="0" max="30" required value="{{ old('reminder_days_before_due', $values['reminder_days_before_due']) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @error('reminder_days_before_due') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
        </section>

        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold text-slate-950">Receipt storage</h2>
            <p class="mt-1 text-sm text-slate-600">
                Receipt images take up storage, so old ones are deleted automatically every day. Only the image is deleted:
                the payment record and official receipt stay. Receipts still waiting for review are never deleted.
            </p>
            <div class="mt-4 max-w-xs">
                <label for="receipt_keep_months" class="block text-sm font-medium text-slate-700">Keep receipt images for (months)</label>
                <input id="receipt_keep_months" name="receipt_keep_months" type="number" min="0" max="60" required value="{{ old('receipt_keep_months', $values['receipt_keep_months']) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-slate-500">3 keeps the current month and the 2 before it. 0 keeps them forever.</p>
                @error('receipt_keep_months') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
        </section>

        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold text-slate-950">House rules</h2>
            <p class="mt-1 text-sm text-slate-600">Applicants must accept these before applying. Tenants can read them anytime.</p>
            <textarea id="house_rules" name="house_rules" rows="14" required maxlength="10000" aria-label="House rules" class="mt-4 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('house_rules', $values['house_rules']) }}</textarea>
            @error('house_rules') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </section>

        <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Save Settings</button>
    </form>
@endsection
