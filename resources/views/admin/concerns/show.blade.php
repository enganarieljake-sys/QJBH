@extends('layouts.app')

@section('title', $concern->subject)
@section('page-title', 'Tenant Concern')
@section('page-actions')
    <a href="{{ route('admin.concerns.index') }}" aria-label="Back to concerns" title="Back to concerns" class="flex h-10 w-10 items-center justify-center rounded-md bg-slate-900 text-base font-black leading-none text-white shadow-sm hover:bg-slate-700"><svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg></a>
    @staffcan('admin.concerns.destroy')
    <form method="POST" action="{{ route('admin.concerns.destroy', $concern) }}" onsubmit="return confirm('Delete this concern?')">
        @csrf
        @method('DELETE')
        <button class="rounded-md border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Delete</button>
    </form>
    @endstaffcan
@endsection

@section('content')
    <div class="grid max-w-5xl gap-6 lg:grid-cols-[1.2fr_1fr]">
        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <dl class="grid gap-4 text-sm md:grid-cols-2">
                <div>
                    <dt class="text-slate-500">Tenant</dt>
                    <dd class="font-medium">
                        @if ($concern->tenant)
                            <a href="{{ route('admin.tenants.show', $concern->tenant) }}" class="hover:underline">{{ $concern->tenant->full_name }}</a>
                        @else
                            Former tenant
                        @endif
                    </dd>
                </div>
                <div><dt class="text-slate-500">Room</dt><dd class="font-medium">{{ $concern->tenant?->room?->room_number ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Category</dt><dd class="font-medium">{{ $concern->categoryLabel() }}</dd></div>
                <div><dt class="text-slate-500">Sent</dt><dd class="font-medium">{{ $concern->created_at->format('M j, Y g:i A') }}</dd></div>
                <div class="md:col-span-2"><dt class="text-slate-500">Subject</dt><dd class="mt-1 font-semibold">{{ $concern->subject }}</dd></div>
                <div class="md:col-span-2"><dt class="text-slate-500">Details</dt><dd class="mt-1 whitespace-pre-line font-medium">{{ $concern->message }}</dd></div>
            </dl>
        </section>

        <form method="POST" action="{{ route('admin.concerns.update', $concern) }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
            @csrf
            @method('PATCH')
            <h2 class="font-semibold text-slate-950">Respond</h2>
            <div class="mt-4 space-y-4">
                <div>
                    <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                    <select id="status" name="status" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $concern->status) === $status)>{{ ucfirst(str_replace('-', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="admin_reply" class="block text-sm font-medium text-slate-700">Reply to tenant</label>
                    <textarea id="admin_reply" name="admin_reply" rows="6" maxlength="3000" placeholder="The tenant will see this reply." class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('admin_reply', $concern->admin_reply) }}</textarea>
                    @error('admin_reply') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    @if ($concern->replied_at)
                        <p class="mt-1 text-xs text-slate-500">Last replied {{ $concern->replied_at->format('M j, Y g:i A') }}</p>
                    @endif
                </div>
            </div>
            <button class="mt-6 w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Save &amp; Notify Tenant</button>
        </form>
    </div>
@endsection
