@csrf
@if ($tenant->exists)
    @method('PUT')
@endif

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-slate-700">Full name</label>
        <input name="full_name" value="{{ old('full_name', $tenant->full_name) }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Email</label>
        <input name="email" type="email" value="{{ old('email', $tenant->email) }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Contact number</label>
        <input name="contact_number" value="{{ old('contact_number', $tenant->contact_number) }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Emergency contact</label>
        <input name="emergency_contact" value="{{ old('emergency_contact', $tenant->emergency_contact) }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Valid ID reference</label>
        <input name="valid_id_reference" value="{{ old('valid_id_reference', $tenant->valid_id_reference) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Move-in date</label>
        <input name="move_in_date" type="date" value="{{ old('move_in_date', optional($tenant->move_in_date)->format('Y-m-d')) }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label for="lease_end_date" class="block text-sm font-medium text-slate-700">Contract end date <span class="text-slate-500">(optional)</span></label>
        <input id="lease_end_date" name="lease_end_date" type="date" value="{{ old('lease_end_date', optional($tenant->lease_end_date)->format('Y-m-d')) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Assigned room</label>
        <select name="room_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">Unassigned</option>
            @foreach ($rooms as $room)
                <option value="{{ $room->id }}" @selected((string) old('room_id', $tenant->room_id) === (string) $room->id)>
                    Room {{ $room->room_number }} - {{ ucfirst($room->status) }} ({{ $room->active_tenants_count ?? $room->activeTenants()->count() }}/{{ $room->capacity }})
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Linked tenant user</label>
        <select name="user_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">No login account</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((string) old('user_id', $tenant->user_id) === (string) $user->id)>
                    {{ $user->name }} - {{ $user->email }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Status</label>
        <select name="status" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(old('status', $tenant->status) === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
    @if (in_array($tenant->deposit_status ?? 'none', ['none', 'held'], true))
        <div>
            <label for="deposit_amount" class="block text-sm font-medium text-slate-700">Security deposit (PHP)</label>
            <input id="deposit_amount" name="deposit_amount" type="number" step="0.01" min="0" value="{{ old('deposit_amount', $tenant->deposit_amount ?? 0) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="deposit_status" class="block text-sm font-medium text-slate-700">Deposit status</label>
            <select id="deposit_status" name="deposit_status" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="none" @selected(old('deposit_status', $tenant->deposit_status ?? 'none') === 'none')>Not yet paid</option>
                <option value="held" @selected(old('deposit_status', $tenant->deposit_status) === 'held')>Paid and held</option>
            </select>
        </div>
    @else
        <p class="text-sm text-slate-600 md:col-span-2">Deposit was settled on move-out ({{ str_replace('-', ' ', $tenant->deposit_status) }}).</p>
    @endif
</div>

<div class="mt-6 flex gap-2">
    <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Save Tenant</button>
    <a href="{{ route('admin.tenants.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
</div>
