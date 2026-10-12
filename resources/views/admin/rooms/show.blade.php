@extends('layouts.app')

@section('title', 'Room '.$room->room_number)
@section('page-title', 'Room '.$room->room_number)
@section('page-actions')
    <a href="{{ route('admin.rooms.index') }}" aria-label="Back to rooms table" title="Back to rooms table" class="flex h-10 w-10 items-center justify-center rounded-md bg-slate-900 text-base font-black leading-none text-white shadow-sm hover:bg-slate-700"><svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg></a>
    @staffcan('admin.rooms.edit')
    <a href="{{ route('admin.rooms.edit', $room) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Edit</a>
    @endstaffcan
    @staffcan('admin.rooms.destroy')
    <form method="POST" action="{{ route('admin.rooms.destroy', $room) }}" onsubmit="return confirm('Delete this room?')">
        @csrf
        @method('DELETE')
        <button class="rounded-md border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Delete</button>
    </form>
    @endstaffcan
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
            @if ($room->photo)
                <img src="{{ $room->photoUrl() }}" alt="Room {{ $room->room_number }}" class="mb-5 aspect-video w-full rounded-md object-cover">
            @endif
            <dl class="grid gap-4 text-sm">
                <div><dt class="text-slate-500">Floor</dt><dd class="font-medium">{{ $room->floor }}</dd></div>
                <div><dt class="text-slate-500">Type</dt><dd class="font-medium capitalize">{{ $room->type }}</dd></div>
                <div><dt class="text-slate-500">Capacity</dt><dd class="font-medium">{{ $room->activeTenants->count() }} / {{ $room->capacity }}</dd></div>
                <div><dt class="text-slate-500">Monthly Rate</dt><dd class="font-medium">PHP {{ number_format($room->monthly_rate, 2) }}</dd></div>
                <div><dt class="text-slate-500">Electricity Rate</dt><dd class="font-medium">PHP {{ number_format($room->electricity_rate, 2) }} per kWh</dd></div>
                <div><dt class="text-slate-500">Water Rate</dt><dd class="font-medium">PHP {{ number_format($room->water_rate, 2) }} per m³</dd></div>
                <div><dt class="text-slate-500">Status</dt><dd class="font-medium capitalize">{{ $room->status }}</dd></div>
            </dl>
        </section>

        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold text-slate-950">Tenants</h2>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse ($room->tenants as $tenant)
                    <div class="py-3">
                        <a href="{{ route('admin.tenants.show', $tenant) }}" class="font-medium text-slate-900 hover:underline">{{ $tenant->full_name }}</a>
                        <p class="text-sm text-slate-500">{{ $tenant->email }} - {{ ucfirst($tenant->status) }}</p>
                    </div>
                @empty
                    <p class="py-3 text-sm text-slate-500">No tenants assigned.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
