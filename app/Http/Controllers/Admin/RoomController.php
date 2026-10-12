<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\Room;
use App\Models\Setting;
use App\Support\PaymentFiles;
use App\Support\Search;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'type', 'floor']);

        $rooms = Room::withCount('activeTenants')
            ->filter($filters)
            ->tap(fn ($query) => Search::apply($query, $request->input('q'), ['room_number', 'type', 'activeTenants.full_name']))
            ->orderByNumber()
            ->paginate(10)
            ->withQueryString();

        $floors = Room::select('floor')->distinct()->orderBy('floor')->pluck('floor');

        return view('admin.rooms.index', [
            'rooms' => $rooms,
            'filters' => $filters,
            'floors' => $floors,
            'types' => Room::TYPES,
            'statuses' => Room::STATUSES,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.rooms.create', [
            'room' => new Room([
                'status' => 'available',
                'electricity_rate' => Setting::number('electricity_rate'),
                'water_rate' => Setting::number('water_rate'),
            ]),
            'types' => Room::TYPES,
            'statuses' => Room::STATUSES,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoomRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $stored = PaymentFiles::store($request->file('photo'), 'rooms');
            $data['photo'] = $stored['path'];
            $data['photo_disk'] = $stored['disk'];
        }

        $room = Room::create($data);
        $room->syncOccupancyStatus();

        return redirect()->route('admin.rooms.show', $room)->with('success', 'Room created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Room $room)
    {
        $room->load(['tenants' => fn ($query) => $query->latest(), 'bills.payments', 'maintenanceRequests.tenant']);

        return view('admin.rooms.show', compact('room'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Room $room)
    {
        return view('admin.rooms.edit', [
            'room' => $room,
            'types' => Room::TYPES,
            'statuses' => Room::STATUSES,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoomRequest $request, Room $room)
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            PaymentFiles::delete($room->photo_disk, $room->photo);

            $stored = PaymentFiles::store($request->file('photo'), 'rooms');
            $data['photo'] = $stored['path'];
            $data['photo_disk'] = $stored['disk'];
        } else {
            unset($data['photo']);
        }

        $room->update($data);
        $room->syncOccupancyStatus();

        return redirect()->route('admin.rooms.show', $room)->with('success', 'Room updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Room $room)
    {
        if ($room->tenants()->exists() || $room->bills()->exists() || $room->maintenanceRequests()->exists()) {
            return back()->with('error', 'Room cannot be deleted while it has tenants, bills, or maintenance records.');
        }

        PaymentFiles::delete($room->photo_disk, $room->photo);

        $room->delete();

        return redirect()->route('admin.rooms.index')->with('success', 'Room deleted successfully.');
    }
}
