@extends('layouts.app')

@php
    $ticket = $maintenanceRequest;
    $isPanday = auth()->user()->role === 'maintenance';
    $fromHistory = $fromHistory ?? false;
@endphp

@section('title', $isPanday ? 'Repair Ticket' : 'Maintenance Request')
@section('page-title', $isPanday ? 'Repair Ticket' : 'Maintenance Request')
@section('page-actions')
    <a href="{{ $fromHistory ? route('admin.maintenance-history.index') : route('admin.maintenance-requests.index') }}" aria-label="Back to the list" title="Back to the list" class="flex h-10 w-10 items-center justify-center rounded-md bg-slate-900 text-base font-black leading-none text-white shadow-sm hover:bg-slate-700"><svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg></a>
    @staffcan('admin.maintenance-requests.edit')
    <a href="{{ route('admin.maintenance-requests.edit', $ticket) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Edit</a>
    @endstaffcan
    @staffcan('admin.maintenance-requests.destroy')
    <form method="POST" action="{{ route('admin.maintenance-requests.destroy', $ticket) }}" onsubmit="return confirm('Delete this maintenance request?')">
        @csrf
        @method('DELETE')
        <button class="rounded-md border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Delete</button>
    </form>
    @endstaffcan
@endsection

@section('content')
    @if ($fromHistory && $isPanday)
        <section class="mb-6 max-w-5xl rounded-lg border border-blue-200 bg-blue-50 p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div><h2 class="font-semibold text-blue-950">Remind Ate Jackie</h2><p class="mt-1 text-sm text-blue-800">Use this if the admin asks whether this maintenance work is already done.</p>
                </div>
                <form method="POST" action="{{ route('admin.maintenance-history.trigger-admin', $ticket) }}">@csrf
                    <button class="w-full rounded-md bg-blue-600 px-5 py-3 text-sm font-bold text-white hover:bg-blue-700 sm:w-auto">Trigger to Admin</button>
                </form>
            </div>
        </section>
    @endif
    <div class="grid max-w-5xl gap-6 lg:grid-cols-[1.2fr_1fr]">
        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-center gap-2">
                <span class="ticket-pill ticket-pill--{{ $ticket->status }}">{{ str_replace('-', ' ', $ticket->status) }}</span>
                <span class="ticket-pill ticket-pill--{{ $ticket->priority }}">{{ $ticket->priority }} priority</span>
            </div>
            <dl class="mt-4 grid gap-4 text-sm md:grid-cols-2">
                <div><dt class="text-slate-500">Room</dt><dd class="text-lg font-bold">{{ $ticket->room?->room_number ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Reported by</dt><dd class="font-medium">{{ $ticket->tenant?->full_name ?? 'Staff' }}</dd></div>
                <div><dt class="text-slate-500">Reported</dt><dd class="font-medium">{{ $ticket->created_at->format('M j, Y g:i A') }}</dd></div>
                <div><dt class="text-slate-500">Completed</dt><dd class="font-medium">{{ $ticket->completion_date?->format('M j, Y') ?? 'Not yet' }}</dd></div>
                <div class="md:col-span-2"><dt class="text-slate-500">Issue</dt><dd class="mt-1 whitespace-pre-line font-medium">{{ $ticket->description }}</dd></div>
                @if ($ticket->resolution_details)
                    <div class="md:col-span-2"><dt class="text-slate-500">What was fixed</dt><dd class="mt-1 whitespace-pre-line font-medium">{{ $ticket->resolution_details }}</dd></div>
                @endif
            </dl>
        </section>

        <div class="space-y-6">
            <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="font-semibold text-slate-950">{{ $isPanday ? 'Instructions' : 'Panday' }}</h2>
                @if ($ticket->assignee)
                    <p class="mt-2 text-sm text-slate-600">
                        Forwarded to <b>{{ $ticket->assignee->name }}</b> by {{ $ticket->assigner?->firstName() ?? 'admin' }}
                        · {{ $ticket->assigned_at?->format('M j, Y g:i A') }}
                    </p>
                    @if ($ticket->target_date)
                        <p class="mt-1 text-sm text-slate-600">Target: <b>{{ $ticket->target_date->format('M j, Y') }}</b></p>
                    @endif
                    <p class="ticket-notes mt-3 whitespace-pre-line text-sm">{{ $ticket->assignment_notes }}</p>
                @else
                    <p class="mt-2 text-sm text-slate-600">Not forwarded to a panday yet.</p>
                @endif

                @staffcan('admin.maintenance-requests.assign')
                    @if ($pandays->isEmpty())
                        <p class="mt-4 rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800">
                            No panday account yet. Create one in Users &amp; Approvals → Add User, role "Maintenance (Panday)".
                        </p>
                    @elseif ($ticket->status !== 'resolved')
                        <form method="POST" action="{{ route('admin.maintenance-requests.assign', $ticket) }}" class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                            @csrf
                            <p class="text-sm font-semibold text-slate-950">{{ $ticket->assignee ? 'Forward again / change panday' : 'Forward to panday' }}</p>
                            <div>
                                <label for="assigned_to" class="block text-sm font-medium text-slate-700">Panday</label>
                                <select id="assigned_to" name="assigned_to" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                    @foreach ($pandays as $panday)
                                        <option value="{{ $panday->id }}" @selected(old('assigned_to', $ticket->assigned_to) == $panday->id)>{{ $panday->name }}</option>
                                    @endforeach
                                </select>
                                @error('assigned_to') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="assignment_notes" class="block text-sm font-medium text-slate-700">Instructions for the panday</label>
                                <textarea id="assignment_notes" name="assignment_notes" rows="4" maxlength="2000" required placeholder="e.g. Replace the faucet in the CR. The new one is in storage. Call Jake before going." class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('assignment_notes', $ticket->assignment_notes) }}</textarea>
                                @error('assignment_notes') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="priority" class="block text-sm font-medium text-slate-700">Priority</label>
                                    <select id="priority" name="priority" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                        @foreach ($priorities as $priority)
                                            <option value="{{ $priority }}" @selected(old('priority', $ticket->priority) === $priority)>{{ ucfirst($priority) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="target_date" class="block text-sm font-medium text-slate-700">Target date (optional)</label>
                                    <input id="target_date" name="target_date" type="date" min="{{ today()->toDateString() }}" value="{{ old('target_date', $ticket->target_date?->toDateString()) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                    @error('target_date') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <button class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Forward &amp; Notify Panday</button>
                        </form>
                    @endif
                @endstaffcan
            </section>

            @if ($ticket->assignee)
                <section class="space-y-4 rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div>
                        <h2 class="font-semibold text-slate-950">Tool accountability</h2>
                        <p class="mt-1 text-sm text-slate-600">The ticket closes only after the tools are returned and Ate Jackie verifies the photo.</p>
                    </div>

                    @if ($ticket->tools_taken_photo)
                        <div>
                            <p class="text-sm font-semibold">Tools checked out · {{ $ticket->tools_taken_at?->format('M j, Y g:i A') }}</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ $ticket->tools_used }}</p>
                            <a href="{{ route('admin.maintenance-requests.tools.photo', [$ticket, 'taken']) }}" target="_blank">
                                <img src="{{ route('admin.maintenance-requests.tools.photo', [$ticket, 'taken']) }}" alt="Tools used by the panday" class="mt-3 max-h-72 w-full rounded-lg object-cover">
                            </a>
                        </div>
                    @elseif ($isPanday && $ticket->status !== 'resolved')
                        <form method="POST" action="{{ route('admin.maintenance-requests.tools.upload', $ticket) }}" enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            <div>
                                <label for="tools_used" class="block text-sm font-medium text-slate-700">Tools you are taking</label>
                                <textarea id="tools_used" name="tools_used" rows="2" required maxlength="2000" placeholder="e.g. Pipe wrench, pliers, screwdriver" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('tools_used') }}</textarea>
                            </div>
                            <div>
                                <label for="tools_photo" class="block text-sm font-medium text-slate-700">Photo before using the tools</label>
                                <input id="tools_photo" name="tools_photo" type="file" accept="image/*" capture="environment" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <button class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Record Tools &amp; Start Work</button>
                        </form>
                    @else
                        <p class="text-sm text-slate-500">Waiting for the panday to upload the tools photo.</p>
                    @endif

                    @if ($ticket->tools_returned_photo)
                        <div class="border-t border-slate-200 pt-4">
                            <p class="text-sm font-semibold text-emerald-700">Marked returned · {{ $ticket->tools_returned_at?->format('M j, Y g:i A') }}</p>
                            <a href="{{ route('admin.maintenance-requests.tools.photo', [$ticket, 'returned']) }}" target="_blank">
                                <img src="{{ route('admin.maintenance-requests.tools.photo', [$ticket, 'returned']) }}" alt="Tools returned to their proper place" class="mt-3 max-h-72 w-full rounded-lg object-cover">
                            </a>
                        </div>
                    @elseif ($isPanday && $ticket->tools_taken_photo && $ticket->status !== 'resolved')
                        <form method="POST" action="{{ route('admin.maintenance-requests.tools.return', $ticket) }}" enctype="multipart/form-data" class="space-y-3 border-t border-slate-200 pt-4">
                            @csrf
                            <div>
                                <label for="resolution_details" class="block text-sm font-medium text-slate-700">What was fixed?</label>
                                <textarea id="resolution_details" name="resolution_details" rows="3" required maxlength="5000" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('resolution_details', $ticket->resolution_details) }}</textarea>
                            </div>
                            <div>
                                <label for="returned_photo" class="block text-sm font-medium text-slate-700">Photo of tools returned to their place</label>
                                <input id="returned_photo" name="returned_photo" type="file" accept="image/*" capture="environment" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <label class="flex items-start gap-2 text-sm font-medium"><input type="checkbox" name="tools_returned" value="1" required class="mt-1"> I returned every tool to its proper place.</label>
                            <button class="w-full rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Mark Returned &amp; Send for Verification</button>
                        </form>
                    @endif

                    @if ($ticket->tools_verified_at)
                        <p class="rounded-md bg-emerald-50 p-3 text-sm font-semibold text-emerald-800">Verified by {{ $ticket->toolsVerifier?->name ?? 'Ate Jackie' }} on {{ $ticket->tools_verified_at->format('M j, Y g:i A') }}. Ticket closed.</p>
                    @elseif (auth()->user()->isAdmin() && $ticket->status === 'awaiting-verification')
                        <form method="POST" action="{{ route('admin.maintenance-requests.tools.verify', $ticket) }}" onsubmit="return confirm('Verify that all tools were returned and close this ticket?')">
                            @csrf @method('PATCH')
                            <button class="w-full rounded-md bg-blue-600 px-4 py-3 font-bold text-white">Verify Tools &amp; Close Ticket</button>
                        </form>
                    @elseif ($ticket->status === 'awaiting-verification')
                        <p class="rounded-md bg-amber-50 p-3 text-sm font-semibold text-amber-800">Waiting for Ate Jackie to verify the returned tools.</p>
                    @endif
                </section>
            @endif
        </div>
    </div>
@endsection
