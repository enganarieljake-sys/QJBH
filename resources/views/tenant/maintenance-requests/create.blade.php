@extends('layouts.app')

@section('title', 'File Maintenance Request')
@section('page-title', 'File Maintenance Request')

@section('content')
    <form method="POST" action="{{ route('tenant.maintenance-requests.store') }}" class="max-w-3xl rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @csrf
        <p class="mb-4 text-sm text-slate-600">Room {{ $tenant->room?->room_number }}</p>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700">Priority</label>
                <select name="priority" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    @foreach ($priorities as $priority)
                        <option value="{{ $priority }}" @selected(old('priority', $maintenanceRequest->priority) === $priority)>{{ ucfirst($priority) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Description</label>
                <textarea name="description" rows="5" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('description') }}</textarea>
            </div>
        </div>
        <div class="mt-6 flex gap-2">
            <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Submit Request</button>
            <a href="{{ route('tenant.maintenance-requests.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
        </div>
    </form>
@endsection
