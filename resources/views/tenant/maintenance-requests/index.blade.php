@extends('layouts.app')

@section('title', 'My Maintenance Requests')
@section('page-title', 'My Maintenance Requests')
@section('page-actions')
    <a href="{{ route('tenant.maintenance-requests.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">File Request</a>
@endsection

@section('content')
    @if (! $tenant)
        <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200 text-sm text-slate-600">
            {{ ($approvalPending ?? false) ? 'Your account is still waiting for admin approval.' : 'Your account is not linked to a tenant record yet.' }}
        </div>
    @else
        <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">Room</th>
                        <th class="px-4 py-3">Priority</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($maintenanceRequests as $request)
                        <tr>
                            <td class="px-4 py-3">{{ $request->room?->room_number }}</td>
                            <td class="px-4 py-3 capitalize">{{ $request->priority }}</td>
                            <td class="px-4 py-3 capitalize">{{ $request->status }}</td>
                            <td class="px-4 py-3 max-w-md truncate">{{ $request->description }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('tenant.maintenance-requests.show', $request) }}" class="font-medium text-slate-800 hover:underline">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No maintenance requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $maintenanceRequests->links() }}</div>
    @endif
@endsection
