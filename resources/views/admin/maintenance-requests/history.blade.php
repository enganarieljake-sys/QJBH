@extends('layouts.app')

@section('title', 'Maintenance History')
@section('page-title', 'Maintenance History')
@section('page-subtitle', 'Completed work, returned tools, verification, and the panday responsible for every repair.')

@section('content')
    <form method="GET" class="mb-5 grid gap-3 rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200 md:grid-cols-[1fr_14rem_auto]">
        @include('partials.search-input', ['placeholder' => 'Search room, issue, fix, tenant, or panday…'])
        <select name="status" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">All completed work</option>
            <option value="awaiting-verification" @selected(request('status') === 'awaiting-verification')>Awaiting verification</option>
            <option value="resolved" @selected(request('status') === 'resolved')>Verified and closed</option>
        </select>
        <button class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Filter</button>
    </form>
    <div class="overflow-x-auto rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50"><tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                <th class="px-4 py-3">Finished</th><th class="px-4 py-3">Room</th><th class="px-4 py-3">Issue</th><th class="px-4 py-3">Panday</th><th class="px-4 py-3">Tools</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"></th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($maintenanceRequests as $ticket)
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap px-4 py-3">{{ ($ticket->tools_returned_at ?? $ticket->updated_at)->format('M j, Y') }}</td>
                        <td class="px-4 py-3 font-bold">{{ $ticket->room?->room_number ?? '—' }}</td>
                        <td class="max-w-xs truncate px-4 py-3">{{ $ticket->description }}</td>
                        <td class="px-4 py-3">{{ $ticket->assignee?->name ?? 'Unassigned' }}</td>
                        <td class="px-4 py-3">{{ $ticket->tools_returned_at ? 'Returned' : 'No tool record' }}</td>
                        <td class="px-4 py-3"><span class="ticket-pill ticket-pill--{{ $ticket->status }}">{{ str_replace('-', ' ', $ticket->status) }}</span></td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.maintenance-history.show', $ticket) }}" class="font-semibold text-blue-700 hover:underline">View details</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No completed maintenance history yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $maintenanceRequests->links() }}</div>
@endsection
