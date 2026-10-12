@extends('layouts.app')

@section('title', 'Maintenance Request')
@section('page-title', 'Maintenance Request')
@section('page-actions')
    <a href="{{ route('tenant.maintenance-requests.index') }}" aria-label="Back to maintenance requests table" title="Back to maintenance requests table" class="flex h-10 w-10 items-center justify-center rounded-md bg-slate-900 text-base font-black leading-none text-white shadow-sm hover:bg-slate-700"><svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg></a>
@endsection

@section('content')
    <section class="max-w-4xl rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <dl class="grid gap-4 text-sm md:grid-cols-2">
            <div><dt class="text-slate-500">Room</dt><dd class="font-medium">{{ $maintenanceRequest->room?->room_number }}</dd></div>
            <div><dt class="text-slate-500">Priority</dt><dd class="font-medium capitalize">{{ $maintenanceRequest->priority }}</dd></div>
            <div><dt class="text-slate-500">Status</dt><dd class="font-medium capitalize">{{ $maintenanceRequest->status }}</dd></div>
            <div><dt class="text-slate-500">Completion Date</dt><dd class="font-medium">{{ $maintenanceRequest->completion_date?->format('Y-m-d') ?? 'Not completed' }}</dd></div>
            <div class="md:col-span-2"><dt class="text-slate-500">Description</dt><dd class="mt-1 font-medium">{{ $maintenanceRequest->description }}</dd></div>
            <div class="md:col-span-2"><dt class="text-slate-500">Resolution Details</dt><dd class="mt-1 font-medium">{{ $maintenanceRequest->resolution_details ?: 'No resolution details yet.' }}</dd></div>
        </dl>
    </section>
@endsection
