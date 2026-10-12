@csrf
@if ($room->exists)
    @method('PUT')
@endif

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-slate-700">Room number</label>
        <input name="room_number" value="{{ old('room_number', $room->room_number) }}" required
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Floor</label>
        <input name="floor" type="number" min="0" value="{{ old('floor', $room->floor) }}" required
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Type</label>
        <select name="type" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @foreach ($types as $type)
                <option value="{{ $type }}" @selected(old('type', $room->type) === $type)>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Capacity</label>
        <input name="capacity" type="number" min="1" value="{{ old('capacity', $room->capacity) }}" required
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Monthly rate</label>
        <input name="monthly_rate" type="number" step="0.01" min="0" value="{{ old('monthly_rate', $room->monthly_rate) }}" required
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Electricity rate (PHP per kWh)</label>
        <input name="electricity_rate" type="number" step="0.01" min="0" max="1000" value="{{ old('electricity_rate', $room->electricity_rate) }}" required
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        @error('electricity_rate') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Water rate (PHP per m³)</label>
        <input name="water_rate" type="number" step="0.01" min="0" max="10000" value="{{ old('water_rate', $room->water_rate) }}" required
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        @error('water_rate') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Status</label>
        <select name="status" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(old('status', $room->status) === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700">Room photo</label>
        <input name="photo" type="file" accept="image/*" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        @if ($room->photo)
            <img src="{{ $room->photoUrl() }}" alt="Current photo of room {{ $room->room_number }}" class="mt-2 h-24 w-36 rounded-md object-cover">
        @endif
    </div>
</div>

<div class="mt-6 flex gap-2">
    <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
        Save Room
    </button>
    <a href="{{ route('admin.rooms.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
        Cancel
    </a>
</div>
