@extends('layouts.app')

@section('title', $concern->subject)
@section('page-title', 'My Concern')
@section('page-actions')
    <a href="{{ route('tenant.concerns.index') }}" aria-label="Back to my concerns" title="Back to my concerns" class="flex h-10 w-10 items-center justify-center rounded-md bg-slate-900 text-base font-black leading-none text-white shadow-sm hover:bg-slate-700"><svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg></a>
@endsection

@section('content')
    <section class="max-w-4xl rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <dl class="grid gap-4 text-sm md:grid-cols-3">
            <div><dt class="text-slate-500">Category</dt><dd class="font-medium">{{ $concern->categoryLabel() }}</dd></div>
            <div><dt class="text-slate-500">Status</dt><dd class="font-medium capitalize">{{ str_replace('-', ' ', $concern->status) }}</dd></div>
            <div><dt class="text-slate-500">Sent</dt><dd class="font-medium">{{ $concern->created_at->format('M j, Y g:i A') }}</dd></div>
            <div class="md:col-span-3"><dt class="text-slate-500">Subject</dt><dd class="mt-1 font-semibold">{{ $concern->subject }}</dd></div>
            <div class="md:col-span-3"><dt class="text-slate-500">Details</dt><dd class="mt-1 whitespace-pre-line font-medium">{{ $concern->message }}</dd></div>
        </dl>
    </section>

    <section class="mt-6 max-w-4xl rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 class="font-semibold text-slate-950">Admin reply</h2>
        @if ($concern->admin_reply)
            <p class="mt-2 whitespace-pre-line text-sm">{{ $concern->admin_reply }}</p>
            <p class="mt-2 text-xs text-slate-500">{{ $concern->replied_at?->format('M j, Y g:i A') }}</p>
        @else
            <p class="mt-2 text-sm text-slate-500">No reply yet. You'll get a notification when the admin responds.</p>
        @endif
    </section>
@endsection
