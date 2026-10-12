@extends('layouts.app')

@section('title', 'Room Management')
@section('page-title', 'Room Management')
@section('page-actions')
    @staffcan('admin.rooms.create')
    <a href="{{ route('admin.rooms.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Add Room</a>
    @endstaffcan
@endsection

@section('content')
    <form method="GET" class="mb-5 grid gap-3 rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200 md:grid-cols-4">
        @include('partials.search-input', ['placeholder' => 'Search room number, type, or tenant name…'])
        <select name="status" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <select name="type" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">All types</option>
            @foreach ($types as $type)
                <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
        <select name="floor" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">All floors</option>
            @foreach ($floors as $floor)
                <option value="{{ $floor }}" @selected((string) ($filters['floor'] ?? '') === (string) $floor)>Floor {{ $floor }}</option>
            @endforeach
        </select>
        <button class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Filter</button>
    </form>

    <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Room</th>
                    <th class="px-4 py-3">Floor</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Capacity</th>
                    <th class="px-4 py-3">Rate</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rooms as $room)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $room->room_number }}</td>
                        <td class="px-4 py-3">{{ $room->floor }}</td>
                        <td class="px-4 py-3 capitalize">{{ $room->type }}</td>
                        <td class="px-4 py-3">{{ $room->active_tenants_count }} / {{ $room->capacity }}</td>
                        <td class="px-4 py-3">PHP {{ number_format($room->monthly_rate, 2) }}</td>
                        <td class="px-4 py-3 capitalize">{{ $room->status }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.rooms.show', $room) }}" class="font-medium text-slate-800 hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">No rooms found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $rooms->links() }}</div>
@endsection
