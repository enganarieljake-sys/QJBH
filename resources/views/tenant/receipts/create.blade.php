@extends('layouts.app')

@section('title', 'Pay & Send Receipt')
@section('page-title', 'Pay & Send Receipt')
@section('page-subtitle', 'Scan the QR, pay in your app, then attach a screenshot of your receipt.')
@section('page-actions')
    <a href="{{ route('tenant.receipts.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">My Receipts</a>
@endsection

@section('content')
    @if ($bills->isEmpty())
        <div class="max-w-xl rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold text-slate-950">You're all paid up</h2>
            <p class="mt-1 text-sm text-slate-600">You have no unpaid bills right now, so there's nothing to send a receipt for.</p>
            <a href="{{ route('tenant.bills.index') }}" class="mt-4 inline-block rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">View my bills</a>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-[1fr_1.1fr]">
            <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200" aria-labelledby="pay-step-1">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Step 1</p>
                <h2 id="pay-step-1" class="font-semibold text-slate-950">Scan &amp; pay</h2>

                @forelse ($channels as $channel)
                    <article class="mt-4 rounded-md border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="font-semibold text-slate-950">{{ $channel->name }}</h3>
                                <p class="text-sm text-slate-600">{{ $channel->account_name }}</p>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-3 rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                            <span class="text-base font-bold tracking-wide text-slate-950">{{ $channel->account_number }}</span>
                            <button type="button" data-copy="{{ preg_replace('/\s+/', '', $channel->account_number) }}" class="shrink-0 rounded-md border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700">Copy</button>
                        </div>
                        @if ($channel->qr_path)
                            <img src="{{ route('payment-channels.qr', $channel) }}" alt="{{ $channel->name }} QR code for {{ $channel->account_name }}" class="payment-qr mx-auto mt-4 w-full max-w-64">
                            <a href="{{ route('payment-channels.qr', ['paymentChannel' => $channel, 'download' => 1]) }}" class="mt-3 block rounded-md border border-slate-300 px-4 py-2 text-center text-sm font-semibold text-slate-700 hover:bg-white">Save QR to phone</a>
                        @endif
                        @if ($channel->instructions)
                            <p class="mt-3 text-sm text-slate-600">{{ $channel->instructions }}</p>
                        @endif
                    </article>
                @empty
                    <p class="mt-3 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">The admin hasn't posted a payment QR yet. You can still pay in person and send your receipt here.</p>
                @endforelse

                @if ($channels->isNotEmpty())
                    <p class="mt-4 text-xs text-slate-500">Paying from this same phone? Tap <b>Save QR to phone</b>, then in GCash or Maya choose <b>Scan QR</b>, then <b>Upload QR</b>, and pick the saved image.</p>
                @endif
            </section>

            <form method="POST" action="{{ route('tenant.receipts.store') }}" enctype="multipart/form-data" class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200" aria-labelledby="pay-step-2">
                @csrf
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Step 2</p>
                <h2 id="pay-step-2" class="font-semibold text-slate-950">Send your receipt</h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <label for="bill_id" class="block text-sm font-medium text-slate-700">Bill you paid</label>
                        <select id="bill_id" name="bill_id" required data-bill-select class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                            @foreach ($bills as $bill)
                                <option value="{{ $bill->id }}" data-balance="{{ number_format($bill->balance, 2, '.', '') }}" @selected((int) old('bill_id', $selectedBill?->id) === $bill->id)>
                                    {{ $bill->billing_month?->format('F Y') }} · Balance PHP {{ number_format($bill->balance, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if ($channels->isNotEmpty())
                        <div>
                            <label for="payment_channel_id" class="block text-sm font-medium text-slate-700">Paid via</label>
                            <select id="payment_channel_id" name="payment_channel_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                @foreach ($channels as $channel)
                                    <option value="{{ $channel->id }}" @selected((int) old('payment_channel_id') === $channel->id)>{{ $channel->name }} · {{ $channel->account_number }}</option>
                                @endforeach
                                <option value="" @selected(old('payment_channel_id') === '')>Other / cash</option>
                            </select>
                        </div>
                    @endif

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="amount" class="block text-sm font-medium text-slate-700">Amount paid (PHP)</label>
                            <input id="amount" type="number" name="amount" step="0.01" min="0.01" inputmode="decimal" required data-amount-input value="{{ old('amount', $selectedBill ? number_format($selectedBill->balance, 2, '.', '') : '') }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label for="payment_date" class="block text-sm font-medium text-slate-700">Date paid</label>
                            <input id="payment_date" type="date" name="payment_date" required max="{{ today()->toDateString() }}" value="{{ old('payment_date', today()->toDateString()) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div>
                        <label for="reference_number" class="block text-sm font-medium text-slate-700">Reference no. <span class="text-slate-500">(from your receipt)</span></label>
                        <input id="reference_number" name="reference_number" maxlength="100" inputmode="numeric" autocomplete="off" value="{{ old('reference_number') }}" placeholder="e.g. 1012 345 678901" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    </div>

                    <div>
                        <label for="receipt" class="block text-sm font-medium text-slate-700">Receipt screenshot</label>
                        <input id="receipt" type="file" name="receipt" accept="image/png,image/jpeg,image/webp" required data-receipt-input class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <p class="mt-1 text-xs text-slate-500">PNG, JPG, or WEBP up to 5 MB.</p>
                        <img data-receipt-preview alt="Selected receipt preview" class="receipt-image mt-3 hidden max-h-80 w-auto rounded-md">
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-slate-700">Note to admin <span class="text-slate-500">(optional)</span></label>
                        <textarea id="notes" name="notes" rows="2" maxlength="500" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <button class="mt-6 w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Send Receipt</button>
            </form>
        </div>
    @endif
@endsection
