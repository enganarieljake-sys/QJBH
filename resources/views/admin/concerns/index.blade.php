@extends('layouts.app')

@section('title', 'Tenant Concerns')
@section('page-title', 'Tenant Concerns')
@section('page-subtitle', 'Reports and concerns sent by tenants. Repairs are under Maintenance Requests.')

@section('content')
    <form method="GET" class="mb-5 grid gap-3 rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200 md:grid-cols-3">
        @include('partials.search-input', ['placeholder' => 'Search subject, message, or tenant…'])
        <select name="status" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst(str_replace('-', ' ', $status)) }}</option>
            @endforeach
        </select>
        <select name="category" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">All categories</option>
            @foreach ($categories as $value => $label)
                <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Filter</button>
    </form>

    <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Tenant</th>
                    <th class="px-4 py-3">Subject</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Sent</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($concerns as $concern)
                    <tr>
                        <td class="px-4 py-3">
                            {{ $concern->tenant?->full_name ?? 'Former tenant' }}
                            @if ($concern->tenant?->room)
                                <span class="block text-xs text-slate-500">Room {{ $concern->tenant->room->room_number }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 max-w-md truncate font-medium">{{ $concern->subject }}</td>
                        <td class="px-4 py-3">{{ $concern->categoryLabel() }}</td>
                        <td class="px-4 py-3 capitalize">{{ str_replace('-', ' ', $concern->status) }}</td>
                        <td class="px-4 py-3">{{ $concern->created_at->format('M j, Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.concerns.show', $concern) }}" class="font-medium text-slate-800 hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No concerns found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $concerns->links() }}</div>
@endsection
