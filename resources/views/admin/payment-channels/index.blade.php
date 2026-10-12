@extends('layouts.app')

@section('title', 'Payment QR Codes')
@section('page-title', 'Payment QR Codes')
@section('page-subtitle', 'Tenants see these QR codes and account numbers when they pay a bill.')
@section('page-actions')
    <a href="{{ route('admin.payment-submissions.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Review Receipts</a>
    <a href="{{ route('admin.payment-channels.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Post QR Code</a>
@endsection

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($channels as $channel)
            <article class="flex flex-col rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="truncate font-semibold text-slate-950">{{ $channel->name }}</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ $channel->account_name }}</p>
                        <p class="text-sm font-semibold tracking-wide text-slate-900">{{ $channel->account_number }}</p>
                    </div>
                    <span class="shrink-0 rounded-md border px-2 py-1 text-xs font-semibold {{ $channel->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-slate-50 text-slate-500' }}">
                        {{ $channel->is_active ? 'Visible to tenants' : 'Hidden' }}
                    </span>
                </div>

                @if ($channel->qr_path)
                    <img src="{{ route('payment-channels.qr', $channel) }}" alt="{{ $channel->name }} QR code" class="payment-qr mx-auto mt-4 w-full max-w-56" loading="lazy">
                @endif

                <div class="mt-auto flex flex-wrap gap-2 pt-4">
                    <a href="{{ route('admin.payment-channels.edit', $channel) }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Edit</a>
                    <form method="POST" action="{{ route('admin.payment-channels.destroy', $channel) }}" onsubmit="return confirm('Remove this payment QR?')">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Remove</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="rounded-lg bg-white p-6 text-center shadow-sm ring-1 ring-slate-200 sm:col-span-2 xl:col-span-3">
                <h2 class="font-semibold text-slate-950">No payment QR posted yet</h2>
                <p class="mt-1 text-sm text-slate-600">Post your GCash, Maya, or bank QR code so tenants can scan it and pay.</p>
                <a href="{{ route('admin.payment-channels.create') }}" class="mt-4 inline-block rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Post QR Code</a>
            </div>
        @endforelse
    </div>
@endsection
