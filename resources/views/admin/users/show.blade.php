@extends('layouts.app')

@section('title', $user->name)
@section('page-title', $user->name)
@section('page-actions')
    <a href="{{ route('admin.users.index') }}" aria-label="Back to users table" title="Back to users table" class="flex h-10 w-10 items-center justify-center rounded-md bg-slate-900 text-base font-black leading-none text-white shadow-sm hover:bg-slate-700"><svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg></a>
    @if ($user->isTenant() && ! $user->isApprovedTenant())
        <form method="POST" action="{{ route('admin.users.approve-tenant', $user) }}">
            @csrf
            @method('PATCH')
            <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Approve Tenant</button>
        </form>
    @endif
    <a href="{{ route('admin.users.edit', $user) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Edit</a>
    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user account?')">
        @csrf
        @method('DELETE')
        <button class="rounded-md border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Delete</button>
    </form>
@endsection

@section('content')
    <section class="max-w-3xl rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="mb-6 flex items-center gap-4 border-b border-slate-200 pb-5">
            @include('partials.avatar', ['user' => $user, 'size' => 'h-20 w-20 text-xl'])
            <div>
                <h2 class="text-xl font-bold text-slate-950">{{ $user->name }}</h2>
                <p class="mt-1 flex items-center gap-2 text-sm {{ $user->isOnline() ? 'font-semibold text-emerald-600' : 'text-slate-500' }}">
                    <span class="size-2.5 rounded-full {{ $user->isOnline() ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                    {{ $user->presenceLabel() }}
                </p>
            </div>
        </div>
        <dl class="grid gap-4 text-sm md:grid-cols-2">
            <div><dt class="text-slate-500">Email</dt><dd class="font-medium">{{ $user->email }}</dd></div>
            <div><dt class="text-slate-500">Role</dt><dd class="font-medium">{{ $user->roleLabel() }}</dd></div>
            <div><dt class="text-slate-500">Tenant Approval</dt><dd class="font-medium">{{ $user->isTenant() ? ($user->isApprovedTenant() ? 'Approved' : 'Pending approval') : 'Not required' }}</dd></div>
            <div><dt class="text-slate-500">Tenant Record</dt><dd class="font-medium">{{ $user->tenant?->full_name ?? 'Not linked' }}</dd></div>
            <div><dt class="text-slate-500">Room</dt><dd class="font-medium">{{ $user->tenant?->room?->room_number ?? 'Not assigned' }}</dd></div>
            @if ($user->isTenant())
                <div>
                    <dt class="text-slate-500">Room Applied For</dt>
                    <dd class="font-medium">
                        @if ($user->preferredRoom)
                            Room {{ $user->preferredRoom->room_number }}
                            <span class="text-slate-500">({{ $user->preferredRoom->hasAvailableCapacity() ? 'has a free slot' : 'now full' }})</span>
                        @else
                            No preference
                        @endif
                    </dd>
                </div>
                <div><dt class="text-slate-500">Applied On</dt><dd class="font-medium">{{ $user->created_at?->format('M j, Y g:i A') }}</dd></div>
            @endif
        </dl>
    </section>
@endsection
