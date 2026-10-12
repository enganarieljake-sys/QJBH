@extends('layouts.app')

@section('title', 'Visitor Log')
@section('page-title', 'Visitor Log')
@section('page-subtitle', $insideNow.' '.Str::plural('visitor', $insideNow).' still inside today.')

@section('content')
    <form method="GET" class="mb-5 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200">
        @include('partials.search-input', ['placeholder' => 'Search visitor or tenant name (all dates)…', 'class' => 'w-full'])
        <div>
            <label for="date" class="block text-sm font-medium text-slate-700">Date</label>
            <input id="date" name="date" type="date" value="{{ $date->toDateString() }}" class="mt-1 rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <label class="flex items-center gap-2 pb-2 text-sm text-slate-700">
            <input type="checkbox" name="inside" value="1" @checked(request()->boolean('inside')) class="rounded border-slate-300">
            Still inside only
        </label>
        <button class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Show</button>
    </form>

    <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Visitor</th>
                    <th class="px-4 py-3">Visiting</th>
                    <th class="px-4 py-3">Purpose</th>
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
                        <td class="px-4 py-3">
                            {{ $visitor->tenant?->full_name ?? 'Former tenant' }}
                            @if ($visitor->tenant?->room)<span class="block text-xs text-slate-500">Room {{ $visitor->tenant->room->room_number }}</span>@endif
                        </td>
                        <td class="px-4 py-3">{{ $visitor->purpose ?: '—' }}</td>
                        <td class="px-4 py-3">{{ \App\Models\Visitor::formatTime($visitor->time_in) }}</td>
                        <td class="px-4 py-3">{{ $visitor->isInside() ? 'Still inside' : \App\Models\Visitor::formatTime($visitor->time_out) }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($visitor->isInside())
                                <form method="POST" action="{{ route('admin.visitors.checkout', $visitor) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="rounded-md border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700">Mark as left</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No visitors on {{ $date->format('F j, Y') }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $visitors->links() }}</div>
@endsection
