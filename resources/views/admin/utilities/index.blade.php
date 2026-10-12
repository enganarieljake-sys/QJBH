@extends('layouts.app')

@section('title', 'Utilities & Meters')
@section('page-title', 'Utilities & Meters')
@section('page-subtitle', 'Set each room\'s utility rates and enter its meter readings. Charges are split among the room\'s tenants when you generate bills.')
@section('page-actions')
    <a href="{{ route('admin.bills.index') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Go to Bills</a>
@endsection

@section('content')
    <form method="GET" class="mb-5 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200">
        <div>
            <label for="month" class="block text-sm font-medium text-slate-700">Billing month</label>
            <input id="month" name="month" type="month" value="{{ $month->format('Y-m') }}" class="mt-1 rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <button class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Show</button>
        <p class="text-sm text-slate-600">Each room can have a different electricity and water rate.</p>
    </form>

    <form method="POST" action="{{ route('admin.utilities.store') }}">
        @csrf
        <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">

        <div class="overflow-x-auto rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">Room</th>
                        <th class="px-4 py-3">Electricity rate<br><span class="normal-case tracking-normal">PHP per kWh</span></th>
                        <th class="px-4 py-3">Electricity previous (kWh)</th>
                        <th class="px-4 py-3">Electricity current (kWh)</th>
                        <th class="px-4 py-3">Water rate<br><span class="normal-case tracking-normal">PHP per m³</span></th>
                        <th class="px-4 py-3">Water previous (m³)</th>
                        <th class="px-4 py-3">Water current (m³)</th>
                        <th class="px-4 py-3">Charge this month</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rooms as $room)
                        @php
                            $reading = $readings[$room->id] ?? null;
                            $last = $previous[$room->id] ?? null;
                            $field = fn ($name, $fallback) => old("readings.{$room->id}.{$name}", $fallback);
                        @endphp
                        <tr>
                            <td class="px-4 py-3 font-semibold">Room {{ $room->room_number }}</td>
                            <td class="px-4 py-3">
                                <input type="number" step="0.01" min="0" max="1000" inputmode="decimal" name="readings[{{ $room->id }}][electricity_rate]" value="{{ $field('electricity_rate', $room->electricity_rate) }}" aria-label="Room {{ $room->room_number }} electricity rate per kWh" class="w-28 rounded-md border border-slate-300 px-2 py-1" required>
                                @error("readings.{$room->id}.electricity_rate") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-4 py-3"><input type="number" step="0.01" min="0" inputmode="decimal" name="readings[{{ $room->id }}][electricity_previous]" value="{{ $field('electricity_previous', $reading?->electricity_previous ?? $last?->electricity_current) }}" aria-label="Room {{ $room->room_number }} electricity previous" class="w-28 rounded-md border border-slate-300 px-2 py-1"></td>
                            <td class="px-4 py-3">
                                <input type="number" step="0.01" min="0" inputmode="decimal" name="readings[{{ $room->id }}][electricity_current]" value="{{ $field('electricity_current', $reading?->electricity_current) }}" aria-label="Room {{ $room->room_number }} electricity current" class="w-28 rounded-md border border-slate-300 px-2 py-1">
                                @error("readings.{$room->id}.electricity_current") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-4 py-3">
                                <input type="number" step="0.01" min="0" max="10000" inputmode="decimal" name="readings[{{ $room->id }}][water_rate]" value="{{ $field('water_rate', $room->water_rate) }}" aria-label="Room {{ $room->room_number }} water rate per cubic meter" class="w-28 rounded-md border border-slate-300 px-2 py-1" required>
                                @error("readings.{$room->id}.water_rate") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-4 py-3"><input type="number" step="0.01" min="0" inputmode="decimal" name="readings[{{ $room->id }}][water_previous]" value="{{ $field('water_previous', $reading?->water_previous ?? $last?->water_current) }}" aria-label="Room {{ $room->room_number }} water previous" class="w-28 rounded-md border border-slate-300 px-2 py-1"></td>
                            <td class="px-4 py-3">
                                <input type="number" step="0.01" min="0" inputmode="decimal" name="readings[{{ $room->id }}][water_current]" value="{{ $field('water_current', $reading?->water_current) }}" aria-label="Room {{ $room->room_number }} water current" class="w-28 rounded-md border border-slate-300 px-2 py-1">
                                @error("readings.{$room->id}.water_current") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-4 py-3">
                                @if ($reading)
                                    <span class="block">Kuryente: PHP {{ number_format($reading->electricity_amount, 2) }} <span class="text-xs text-slate-500">({{ number_format($reading->electricityUsage(), 2) }} kWh)</span></span>
                                    <span class="block">Tubig: PHP {{ number_format($reading->water_amount, 2) }} <span class="text-xs text-slate-500">({{ number_format($reading->waterUsage(), 2) }} m³)</span></span>
                                @else
                                    <span class="text-slate-500">Not entered</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-6 text-center text-slate-500">Add rooms first.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($rooms->isNotEmpty())
            <button class="mt-4 rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Save Rates & Readings</button>
        @endif
    </form>
@endsection
