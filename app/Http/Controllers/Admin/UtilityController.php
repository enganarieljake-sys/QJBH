<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MeterReading;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UtilityController extends Controller
{
    public function index(Request $request)
    {
        $month = $this->month($request->input('month'));

        $rooms = Room::orderByNumber()->get();
        $readings = MeterReading::whereDate('billing_month', $month)->get()->keyBy('room_id');

        // Last month's "current" reading becomes this month's "previous".
        $previous = MeterReading::whereDate('billing_month', '<', $month)
            ->orderByDesc('billing_month')
            ->get()
            ->unique('room_id')
            ->keyBy('room_id');

        return view('admin.utilities.index', [
            'month' => $month,
            'rooms' => $rooms,
            'readings' => $readings,
            'previous' => $previous,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'readings' => ['array'],
            'readings.*.electricity_previous' => ['nullable', 'numeric', 'min:0'],
            'readings.*.electricity_current' => ['nullable', 'numeric', 'min:0'],
            'readings.*.water_previous' => ['nullable', 'numeric', 'min:0'],
            'readings.*.water_current' => ['nullable', 'numeric', 'min:0'],
            'readings.*.electricity_rate' => ['required', 'numeric', 'min:0', 'max:1000'],
            'readings.*.water_rate' => ['required', 'numeric', 'min:0', 'max:10000'],
        ]);

        $month = $this->month($data['month']);
        $rooms = Room::get()->keyBy('id');
        $saved = 0;

        DB::transaction(function () use ($data, $month, $rooms, &$saved) {
            foreach ($data['readings'] ?? [] as $roomId => $values) {
                $room = $rooms->get((int) $roomId);

                if (! $room) {
                    continue;
                }

                $room->update([
                    'electricity_rate' => (float) $values['electricity_rate'],
                    'water_rate' => (float) $values['water_rate'],
                ]);

                if (! filled($values['electricity_current'] ?? null) && ! filled($values['water_current'] ?? null)) {
                    continue;
                }

                foreach (['electricity', 'water'] as $meter) {
                    if ((float) ($values["{$meter}_current"] ?? 0) < (float) ($values["{$meter}_previous"] ?? 0) && filled($values["{$meter}_current"] ?? null)) {
                        throw ValidationException::withMessages([
                            "readings.{$roomId}.{$meter}_current" => 'The current '.$meter.' reading cannot be lower than the previous reading.',
                        ]);
                    }
                }

                $reading = MeterReading::where('room_id', $roomId)->whereDate('billing_month', $month)->first()
                    ?? new MeterReading(['room_id' => $roomId, 'billing_month' => $month]);
                $reading->fill([
                    'electricity_previous' => (float) ($values['electricity_previous'] ?? 0),
                    'electricity_current' => (float) ($values['electricity_current'] ?? $values['electricity_previous'] ?? 0),
                    'water_previous' => (float) ($values['water_previous'] ?? 0),
                    'water_current' => (float) ($values['water_current'] ?? $values['water_previous'] ?? 0),
                ]);
                $reading->applyRates((float) $room->electricity_rate, (float) $room->water_rate);
                $reading->save();
                $saved++;
            }
        });

        return redirect()
            ->route('admin.utilities.index', ['month' => $month->format('Y-m')])
            ->with('success', "Room rates saved. Meter readings saved for {$saved} ".str('room')->plural($saved).'.');
    }

    private function month(?string $value): Carbon
    {
        $parsed = $value && preg_match('/^\d{4}-\d{2}$/', $value) ? Carbon::createFromFormat('Y-m', $value) : now();

        return $parsed->startOfMonth()->startOfDay();
    }
}
