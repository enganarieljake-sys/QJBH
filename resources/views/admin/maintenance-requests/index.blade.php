@extends('layouts.app')

@section('title', $isPanday ? 'My Tickets' : 'Maintenance Requests')
@section('page-title', $isPanday ? 'My Tickets' : 'Maintenance Requests')
@if ($isPanday)
    @section('page-subtitle', 'Repairs forwarded to you. Photograph tools before use, return them to place, and send proof for verification.')
@endif
@section('page-actions')
    @staffcan('admin.maintenance-requests.create')
    <a href="{{ route('admin.maintenance-requests.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Create Request</a>
    @endstaffcan
@endsection

@section('content')
    <form method="GET" class="mb-5 grid gap-3 rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200 {{ $isPanday ? 'md:grid-cols-3' : 'md:grid-cols-4' }}">
        @include('partials.search-input', ['placeholder' => 'Search issue, tenant, or room…'])
        <select name="status" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <select name="priority" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">All priorities</option>
            @foreach ($priorities as $priority)
                <option value="{{ $priority }}" @selected(($filters['priority'] ?? '') === $priority)>{{ ucfirst($priority) }}</option>
            @endforeach
        </select>
        @unless ($isPanday)
            <select name="assigned" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">Forwarded or not</option>
                <option value="none" @selected(($filters['assigned'] ?? '') === 'none')>Not yet forwarded</option>
            </select>
        @endunless
        <button class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Filter</button>
    </form>

    <div class="overflow-x-auto rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">{{ $isPanday ? 'Forwarded' : 'Reported' }}</th>
                    <th class="px-4 py-3">Room</th>
                    <th class="px-4 py-3">Issue</th>
                    @if ($isPanday)
                        <th class="px-4 py-3">Instructions</th>
                        <th class="px-4 py-3">Target</th>
                    @else
                        <th class="px-4 py-3">Tenant</th>
                        <th class="px-4 py-3">Panday</th>
                    @endif
                    <th class="px-4 py-3">Priority</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($maintenanceRequests as $request)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3">{{ ($isPanday ? $request->assigned_at : $request->created_at)?->format('M j, Y') }}</td>
                        <td class="px-4 py-3 font-semibold">{{ $request->room?->room_number }}</td>
                        <td class="max-w-xs truncate px-4 py-3">{{ $request->description }}</td>
                        @if ($isPanday)
                            <td class="max-w-xs truncate px-4 py-3 text-slate-600">{{ $request->assignment_notes }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $request->target_date?->format('M j') ?? '—' }}</td>
                        @else
                            <td class="px-4 py-3">{{ $request->tenant?->full_name ?? 'Staff' }}</td>
                            <td class="px-4 py-3">
                                @if ($request->assignee)
                                    {{ $request->assignee->firstName() }}
                                @else
                                    <span class="text-xs font-semibold text-amber-600">Not forwarded</span>
                                @endif
                            </td>
                        @endif
                        <td class="px-4 py-3"><span class="ticket-pill ticket-pill--{{ $request->priority }}">{{ $request->priority }}</span></td>
                        <td class="px-4 py-3"><span class="ticket-pill ticket-pill--{{ $request->status }}">{{ str_replace('-', ' ', $request->status) }}</span></td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.maintenance-requests.show', $request) }}" class="font-medium text-slate-800 hover:underline">{{ $isPanday ? 'Open' : 'View' }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-6 text-center text-slate-500">{{ $isPanday ? 'No tickets forwarded to you right now.' : 'No requests found.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $maintenanceRequests->links() }}</div>
@endsection
