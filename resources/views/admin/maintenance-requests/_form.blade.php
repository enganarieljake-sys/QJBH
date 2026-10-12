@csrf
@if ($maintenanceRequest->exists)
    @method('PUT')
@endif

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-slate-700">Tenant</label>
        <select name="tenant_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">Staff request / no tenant</option>
            @foreach ($tenants as $tenant)
                <option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $maintenanceRequest->tenant_id) === (string) $tenant->id)>
                    {{ $tenant->full_name }} - {{ $tenant->room ? 'Room '.$tenant->room->room_number : 'No room' }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Room</label>
        <select name="room_id" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @foreach ($rooms as $room)
                <option value="{{ $room->id }}" @selected((string) old('room_id', $maintenanceRequest->room_id) === (string) $room->id)>Room {{ $room->room_number }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Priority</label>
        <select name="priority" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @foreach ($priorities as $priority)
                <option value="{{ $priority }}" @selected(old('priority', $maintenanceRequest->priority) === $priority)>{{ ucfirst($priority) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Status</label>
        <select name="status" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(old('status', $maintenanceRequest->status) === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700">Description</label>
        <textarea name="description" rows="4" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('description', $maintenanceRequest->description) }}</textarea>
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700">Resolution details</label>
        <textarea name="resolution_details" rows="3" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('resolution_details', $maintenanceRequest->resolution_details) }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Completion date</label>
        <input name="completion_date" type="date" value="{{ old('completion_date', optional($maintenanceRequest->completion_date)->format('Y-m-d')) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
</div>

<div class="mt-6 flex gap-2">
    <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Save Request</button>
    <a href="{{ route('admin.maintenance-requests.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
</div>
