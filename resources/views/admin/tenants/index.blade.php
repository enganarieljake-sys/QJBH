@extends('layouts.app')

@section('title', 'Tenant Management')
@section('page-title', 'Tenant Management')
@section('page-actions')
    <a href="{{ route('admin.tenants.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Add Tenant</a>
@endsection

@section('content')
    <form method="GET" class="mb-5 grid gap-3 rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200 md:grid-cols-[1fr_11rem_11rem_auto_auto]">
        @include('partials.search-input', ['placeholder' => 'Search name, email, phone, or room…'])
        <select name="status" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <select name="room_id" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">All rooms</option>
            @foreach ($rooms as $room)
                <option value="{{ $room->id }}" @selected((string) ($filters['room_id'] ?? '') === (string) $room->id)>Room {{ $room->room_number }}</option>
            @endforeach
        </select>
        <button class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Filter</button>
        <a href="{{ route('admin.users.index', ['group' => 'staff']) }}" class="flex items-center justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">View Staffs</a>
    </form>

    <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_19rem]">
        <div>
            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Tenant</th>
                    <th class="px-4 py-3">Contact</th>
                    <th class="px-4 py-3">Room</th>
                    <th class="px-4 py-3">Move-in</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($tenants as $tenant)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if ($tenant->user)
                                    @include('partials.avatar', ['user' => $tenant->user, 'size' => 'h-11 w-11 text-sm'])
                                @else
                                    <img src="{{ asset('images/default-avatar.jpg') }}" alt="{{ $tenant->full_name }}" class="user-avatar h-11 w-11 text-sm">
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-slate-900">{{ $tenant->full_name }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $tenant->email }}</p>
                                    @if ($tenant->user)
                                        <p class="mt-0.5 flex items-center gap-1.5 text-xs {{ $tenant->user->isOnline() ? 'font-semibold text-emerald-600' : 'text-slate-500' }}">
                                            <span class="size-2 rounded-full {{ $tenant->user->isOnline() ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                            {{ $tenant->user->presenceLabel() }}
                                        </p>
                                    @else
                                        <p class="mt-0.5 text-xs text-slate-500">No login account</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ $tenant->contact_number }}</td>
                        <td class="px-4 py-3">{{ $tenant->room ? 'Room '.$tenant->room->room_number : 'Unassigned' }}</td>
                        <td class="px-4 py-3">{{ $tenant->move_in_date?->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 capitalize">{{ $tenant->status }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.tenants.show', $tenant) }}" class="font-medium text-slate-800 hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No tenants found.</td></tr>
                @endforelse
            </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $tenants->links() }}</div>
        </div>

        <aside class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200 xl:sticky xl:top-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Presence</p>
                    <h2 class="font-bold text-slate-950">Tenants &amp; Staff</h2>
                </div>
                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">{{ $people->filter->isOnline()->count() }} online</span>
            </div>
            <div class="mt-4 max-h-[36rem] space-y-2 overflow-y-auto pr-1">
                @forelse ($people as $person)
                    <a href="{{ route('admin.users.show', $person) }}" class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                        <span class="relative shrink-0">
                            @include('partials.avatar', ['user' => $person, 'size' => 'h-10 w-10 text-sm'])
                            <span class="absolute -bottom-0.5 -right-0.5 size-3 rounded-full border-2 border-white {{ $person->isOnline() ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-slate-900">{{ $person->name }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $person->roleLabel() }}{{ $person->tenant?->room ? ' · Room '.$person->tenant->room->room_number : '' }}</span>
                            <span class="block text-xs {{ $person->isOnline() ? 'font-semibold text-emerald-600' : 'text-slate-500' }}">{{ $person->presenceLabel() }}</span>
                        </span>
                    </a>
                @empty
                    <p class="py-6 text-center text-sm text-slate-500">No user accounts yet.</p>
                @endforelse
            </div>
        </aside>
    </div>
@endsection
