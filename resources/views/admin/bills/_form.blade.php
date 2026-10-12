P@csrf
@if ($bill->exists)
    @method('PUT')
@endif

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-slate-700">Tenant</label>
        <select id="tenant_id" name="tenant_id" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">Select tenant</option>
            @foreach ($tenants as $tenant)
                <option value="{{ $tenant->id }}" data-room-id="{{ $tenant->room_id }}" @selected((string) old('tenant_id', $bill->tenant_id) === (string) $tenant->id)>
                    {{ $tenant->full_name }} - {{ $tenant->room ? 'Room '.$tenant->room->room_number : 'No room' }}
                </option>
            @endforeach
        </select>
        <input id="room_id" name="room_id" type="hidden" value="{{ old('room_id', $bill->room_id) }}">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Billing month</label>
        <input name="billing_month" type="month" value="{{ old('billing_month', optional($bill->billing_month)->format('Y-m')) }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Electricity amount</label>
        <input name="electricity_amount" type="number" step="0.01" min="0" value="{{ old('electricity_amount', $bill->electricity_amount ?? 0) }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Water amount</label>
        <input name="water_amount" type="number" step="0.01" min="0" value="{{ old('water_amount', $bill->water_amount ?? 0) }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Other charges</label>
        <input name="other_charges" type="number" step="0.01" min="0" value="{{ old('other_charges', $bill->other_charges ?? 0) }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Due date</label>
        <input name="due_date" type="date" value="{{ old('due_date', optional($bill->due_date)->format('Y-m-d')) }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Status</label>
        <select name="status" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(old('status', $bill->status ?? 'pending') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="mt-6 flex gap-2">
    <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Save Bill</button>
    <a href="{{ route('admin.bills.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
</div>

<script>
    const tenantSelect = document.getElementById('tenant_id');
    const roomInput = document.getElementById('room_id');
    const syncRoom = () => {
        const option = tenantSelect.options[tenantSelect.selectedIndex];
        if (option && option.dataset.roomId) roomInput.value = option.dataset.roomId;
    };
    tenantSelect?.addEventListener('change', syncRoom);
    syncRoom();
</script>
