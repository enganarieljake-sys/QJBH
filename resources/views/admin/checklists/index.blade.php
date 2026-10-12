@extends('layouts.app')

@section('title', 'Daily Checklist')
@section('page-title', auth()->user()->isAdmin() ? 'Caretaker Checklist' : 'My Daily Checklist')
@section('page-subtitle', auth()->user()->isAdmin() ? 'Customize daily tasks and review every completed or missed period.' : 'Finish the active checklist anytime within its half-day schedule.')

@section('content')
    @php
        $statusClasses = [
            'completed' => 'bg-emerald-100 text-emerald-800',
            'missed' => 'bg-rose-100 text-rose-800',
            'missed_override' => 'bg-amber-100 text-amber-900',
        ];
    @endphp

    @if (auth()->user()->role === 'caretaker')
        <div class="mx-auto max-w-3xl space-y-6">
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[.18em] text-slate-500">{{ $period === 'morning' ? '12:00 AM – 12:00 PM' : '12:00 PM – 12:00 AM' }}</p>
                        <h2 class="mt-1 text-xl font-bold text-slate-950">{{ ucfirst($period) }} checklist</h2>
                    </div>
                    <span class="rounded-full bg-[color-mix(in_srgb,var(--brand)_12%,white)] px-3 py-1 text-sm font-bold text-[var(--brand)]">{{ $timingMessage }}</span>
                </div>

                @if ($report && $report->status === AppModelsChecklistReport::STATUS_COMPLETED)
                    <div class="mt-5 rounded-lg bg-emerald-50 p-4 text-emerald-900">
                        <p class="font-bold">Report sent</p>
                        <p class="mt-1 text-sm">{{ $report->submitted_at->format('M j, Y g:i A') }} · {{ ucfirst($report->timingLabel()) }}. Ate Jackie has been notified.</p>
                    </div>
                    <ul class="mt-4 space-y-2 text-sm">
                        @foreach ($report->completed_items as $task)
                            <li class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2">✓ {{ $task }}</li>
                        @endforeach
                    </ul>
                @elseif ($items->isEmpty())
                    <p class="mt-5 text-sm text-slate-600">No {{ $period }} tasks have been assigned yet.</p>
                @else
                    <form method="POST" action="{{ route('admin.checklists.submit') }}" class="mt-5">
                        @csrf
                        <p class="text-sm text-slate-600">Check every item after finishing it. This period stays open until {{ $period === 'morning' ? '12 noon' : 'midnight' }}.</p>
                        <div class="mt-4 space-y-3">
                            @foreach ($items as $item)
                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4 transition hover:border-[var(--brand)]">
                                    <input type="checkbox" name="completed[]" value="{{ $item->id }}" class="mt-1 size-5 rounded" @checked(in_array($item->id, old('completed', [])))>
                                    <span class="font-medium text-slate-900">{{ $item->title }}</span>
                                </label>
                            @endforeach
                        </div>
                        <label for="notes" class="mt-5 block text-sm font-medium text-slate-700">Notes for Ate Jackie (optional)</label>
                        <textarea id="notes" name="notes" rows="3" maxlength="2000" placeholder="Report a leak, damage, missing supply, or anything that needs attention." class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('notes') }}</textarea>
                        <button class="mt-5 w-full rounded-md bg-[var(--brand)] px-4 py-3 font-semibold text-white">Send {{ ucfirst($period) }} Report</button>
                    </form>
                @endif
            </section>

            @if ($missedReports->isNotEmpty())
                <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
                    <h2 class="font-bold text-slate-950">Missed checklists need a reason</h2>
                    <p class="mt-1 text-sm text-slate-600">File the reason for every missed period so Ate Jackie can review it.</p>
                    <div class="mt-4 space-y-4">
                        @foreach ($missedReports as $missed)
                            <article class="rounded-lg border border-rose-200 bg-rose-50/50 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="font-bold text-slate-900">{{ $missed->report_date->format('M j, Y') }} · {{ ucfirst($missed->period) }}</p>
                                    <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-800">Missed</span>
                                </div>
                                <form method="POST" action="{{ route('admin.checklists.missed-reason', $missed) }}" class="mt-3">
                                    @csrf
                                    <label class="text-sm font-medium text-slate-700" for="reason-{{ $missed->id }}">Why was this checklist missed?</label>
                                    <textarea id="reason-{{ $missed->id }}" name="missed_reason" required maxlength="2000" rows="3" class="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">{{ old('missed_reason', $missed->missed_reason) }}</textarea>
                                    <button class="mt-2 rounded-md bg-rose-700 px-4 py-2 text-sm font-bold text-white">{{ $missed->missed_reason ? 'Update reason' : 'Send reason' }}</button>
                                </form>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    @else
        <div class="grid gap-6 xl:grid-cols-[minmax(20rem,.8fr)_minmax(0,1.2fr)]">
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
                <h2 class="font-bold text-slate-950">Customize tasks</h2>
                <p class="mt-1 text-sm text-slate-600">Morning runs 12 AM–12 PM. Afternoon runs 12 PM–12 AM.</p>
                <form method="POST" action="{{ route('admin.checklists.items.store') }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_9rem_auto]">
                    @csrf
                    <input name="title" required maxlength="255" placeholder="e.g. Inspect faucets and CR" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <select name="period" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($periods as $value)<option value="{{ $value }}">{{ ucfirst($value) }}</option>@endforeach
                    </select>
                    <button class="rounded-md bg-[var(--brand)] px-4 py-2 text-sm font-semibold text-white">Add</button>
                </form>

                <div class="mt-5 space-y-3">
                    @forelse ($allItems as $item)
                        <div class="rounded-lg border border-slate-200 p-3">
                            <form method="POST" action="{{ route('admin.checklists.items.update', $item) }}" class="grid gap-2 sm:grid-cols-[1fr_8rem_auto]">
                                @csrf @method('PUT')
                                <input name="title" value="{{ $item->title }}" required maxlength="255" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                                <select name="period" class="rounded-md border border-slate-300 px-2 py-2 text-sm">
                                    @foreach ($periods as $value)<option value="{{ $value }}" @selected($item->period === $value)>{{ ucfirst($value) }}</option>@endforeach
                                </select>
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($item->is_active)> Active</label>
                                <button class="text-left text-sm font-semibold text-[var(--brand)]">Save changes</button>
                            </form>
                            <form method="POST" action="{{ route('admin.checklists.items.destroy', $item) }}" class="mt-2" onsubmit="return confirm('Remove this checklist task?')">
                                @csrf @method('DELETE')
                                <button class="text-sm font-semibold text-rose-600">Remove</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No tasks yet. Add Ate Jackie’s first checklist task above.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
                <h2 class="font-bold text-slate-950">Daily log</h2>
                <p class="mt-1 text-sm text-slate-600">Completed, missed, and reviewed caretaker periods.</p>
                <div class="mt-4 divide-y divide-slate-200">
                    @forelse ($reports as $item)
                        <article class="py-5 first:pt-0">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-bold text-slate-950">{{ $item->user->name }} · {{ ucfirst($item->period) }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $item->report_date->format('j M, D') }} · {{ $item->submitted_at ? $item->submitted_at->format('g:i A').' ('.$item->timingLabel().')' : 'No report submitted' }}</p>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClasses[$item->status] ?? $statusClasses['completed'] }}">{{ $item->statusLabel() }}</span>
                            </div>
                            @if ($item->completed_items)
                                <ul class="mt-3 grid gap-1 text-sm sm:grid-cols-2">
                                    @foreach ($item->completed_items as $task)<li>✓ {{ $task }}</li>@endforeach
                                </ul>
                            @endif
                            @if ($item->notes)<p class="mt-3 rounded-md bg-slate-100 p-3 text-sm"><b>Report notes:</b> {{ $item->notes }}</p>@endif
                            @if ($item->missed_reason)<p class="mt-3 rounded-md bg-rose-50 p-3 text-sm text-rose-950"><b>Caretaker reason:</b> {{ $item->missed_reason }}</p>@endif

                            @if ($item->status === AppModelsChecklistReport::STATUS_MISSED)
                                @if ($item->missed_reason)
                                    <form method="POST" action="{{ route('admin.checklists.override', $item) }}" class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3">
                                        @csrf
                                        <label for="admin-note-{{ $item->id }}" class="text-sm font-medium text-slate-800">Ate Jackie’s note</label>
                                        <textarea id="admin-note-{{ $item->id }}" name="admin_note" required maxlength="2000" rows="2" class="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm"></textarea>
                                        <button class="mt-2 rounded-md bg-amber-700 px-4 py-2 text-sm font-bold text-white">Mark as Missed Override</button>
                                    </form>
                                @else
                                    <p class="mt-3 text-sm font-medium text-rose-700">Waiting for the caretaker to file a reason.</p>
                                @endif
                            @elseif ($item->status === AppModelsChecklistReport::STATUS_MISSED_OVERRIDE)
                                <p class="mt-3 rounded-md bg-amber-50 p-3 text-sm text-amber-950"><b>Admin note:</b> {{ $item->admin_note }}<br><span class="text-xs">Reviewed by {{ $item->overrider?->name ?? 'Admin' }} {{ $item->overridden_at?->format('M j, Y g:i A') }}</span></p>
                            @endif
                        </article>
                    @empty
                        <p class="text-sm text-slate-500">No checklist history yet.</p>
                    @endforelse
                </div>
                @if ($reports->hasPages())<div class="mt-4">{{ $reports->links() }}</div>@endif
            </section>
        </div>
    @endif
@endsection
