@extends('layouts.app')

@section('title', 'My Visitors')
@section('page-title', 'My Visitors')
@section('page-subtitle', 'Log your guests so Ate Jackie knows who is in the house.')

@section('content')
    @if (! $tenant)
        <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200 text-sm text-slate-600">
            {{ $approvalPending ? 'Your account is still waiting for admin approval.' : 'Your account is not linked to a tenant record yet.' }}
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-[1fr_1.4fr]">
            <form method="POST" action="{{ route('tenant.visitors.store') }}" class="h-fit rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
                @csrf
                <h2 class="font-semibold text-slate-950">Log a visitor</h2>
                <div class="mt-4 space-y-4">
                    <div>
                        <label for="visitor_name" class="block text-sm font-medium text-slate-700">Visitor's name</label>
                        <input id="visitor_name" name="visitor_name" required maxlength="120" value="{{ old('visitor_name') }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        @error('visitor_name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="relationship" class="block text-sm font-medium text-slate-700">Relationship <span class="text-slate-500">(optional)</span></label>
                        <input id="relationship" name="relationship" maxlength="60" value="{{ old('relationship') }}" placeholder="e.g. Mother, classmate" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label for="purpose" class="block text-sm font-medium text-slate-700">Purpose <span class="text-slate-500">(optional)</span></label>
                        <input id="purpose" name="purpose" maxlength="200" value="{{ old('purpose') }}" placeholder="e.g. Group study, dropping off food" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="visit_date" class="block text-sm font-medium text-slate-700">Date</label>
                            <input id="visit_date" name="visit_date" type="date" required min="{{ today()->toDateString() }}" value="{{ old('visit_date', today()->toDateString()) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                            @error('visit_date') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="time_in" class="block text-sm font-medium text-slate-700">Time in</label>
                            <input id="time_in" name="time_in" type="time" required value="{{ old('time_in', now()->format('H:i')) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                            @error('time_in') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
                <button class="mt-6 w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Log Visitor</button>
            </form>

            <div>
                <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-4 py-3">Visitor</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">In</th>
                                <th class="px-4 py-3">Out</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($visitors as $visitor)
                                <tr>
                                    <td class="px-4 py-3">
                                        <span class="font-medium">{{ $visitor->visitor_name }}</span>
                                        @if ($visitor->relationship)<span class="block text-xs text-slate-500">{{ $visitor->relationship }}</span>@endif
                                    </td>
                                    <td class="px-4 py-3">{{ $visitor->visit_date->format('M j, Y') }}</td>
                                    <td class="px-4 py-3">{{ \App\Models\Visitor::formatTime($visitor->time_in) }}</td>
                                    <td class="px-4 py-3">{{ $visitor->isInside() ? 'Still here' : \App\Models\Visitor::formatTime($visitor->time_out) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        @if ($visitor->isInside())
                                            <form method="POST" action="{{ route('tenant.visitors.checkout', $visitor) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="rounded-md border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700">Mark as left</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No visitors logged yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $visitors->links() }}</div>
            </div>
        </div>
    @endif
@endsection
