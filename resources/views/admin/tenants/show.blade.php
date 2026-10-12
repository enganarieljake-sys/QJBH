@extends('layouts.app')

@section('title', $tenant->full_name)
@section('page-title', $tenant->full_name)
@section('page-actions')
    <a href="{{ route('admin.tenants.index') }}" aria-label="Back to tenants table" title="Back to tenants table" class="flex h-10 w-10 items-center justify-center rounded-md bg-slate-900 text-base font-black leading-none text-white shadow-sm hover:bg-slate-700"><svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg></a>
    <form method="POST" action="{{ route('admin.tenants.invite', $tenant) }}">
        @csrf
        <button class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">{{ $tenant->user ? 'Resend invitation' : 'Send invitation' }}</button>
    </form>
    <a href="{{ route('admin.tenants.edit', $tenant) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Edit</a>
    @if ($tenant->status !== 'moved-out')
        @staffcan('admin.tenants.move-out')
        <a href="{{ route('admin.tenants.move-out', $tenant) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Move Out</a>
        @endstaffcan
    @endif
    @staffcan('admin.tenants.destroy')
    <form method="POST" action="{{ route('admin.tenants.destroy', $tenant) }}" onsubmit="return confirm('Delete this tenant record?')">
        @csrf
        @method('DELETE')
        <button class="rounded-md border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Delete</button>
    </form>
    @endstaffcan
@endsection

@section('content')
    <div class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <dl class="grid gap-4 text-sm">
                <div><dt class="text-slate-500">Email</dt><dd class="font-medium">{{ $tenant->email }}</dd></div>
                <div><dt class="text-slate-500">Contact</dt><dd class="font-medium">{{ $tenant->contact_number }}</dd></div>
                <div><dt class="text-slate-500">Emergency Contact</dt><dd class="font-medium">{{ $tenant->emergency_contact }}</dd></div>
                <div><dt class="text-slate-500">Valid ID</dt><dd class="font-medium">{{ $tenant->valid_id_reference ?: 'Not provided' }}</dd></div>
                <div><dt class="text-slate-500">Move-in Date</dt><dd class="font-medium">{{ $tenant->move_in_date?->format('Y-m-d') }}</dd></div>
                <div><dt class="text-slate-500">Contract End</dt><dd class="font-medium">{{ $tenant->lease_end_date?->format('Y-m-d') ?? 'Not set' }}</dd></div>
                <div><dt class="text-slate-500">Room</dt><dd class="font-medium">{{ $tenant->room ? 'Room '.$tenant->room->room_number : 'Unassigned' }}</dd></div>
                <div><dt class="text-slate-500">Status</dt><dd class="font-medium capitalize">{{ $tenant->status }}</dd></div>
                <div><dt class="text-slate-500">Portal User</dt><dd class="font-medium">{{ $tenant->user?->email ?? 'Not linked' }}</dd></div>
            </dl>

            <h2 class="mt-6 border-t border-slate-200 pt-4 font-semibold text-slate-950">Deposit &amp; Move-out</h2>
            <dl class="mt-3 grid gap-4 text-sm">
                <div><dt class="text-slate-500">Security Deposit</dt><dd class="font-medium">PHP {{ number_format($tenant->deposit_amount, 2) }} <span class="capitalize text-slate-500">· {{ ['none' => 'Not yet paid', 'held' => 'Held'][$tenant->deposit_status] ?? str_replace('-', ' ', $tenant->deposit_status) }}</span></dd></div>
                @if ($tenant->move_out_date)
                    <div><dt class="text-slate-500">Moved Out</dt><dd class="font-medium">{{ $tenant->move_out_date->format('Y-m-d') }}</dd></div>
                    <div><dt class="text-slate-500">Deductions</dt><dd class="font-medium">PHP {{ number_format($tenant->deposit_deductions, 2) }}</dd></div>
                    <div><dt class="text-slate-500">Refunded</dt><dd class="font-medium">PHP {{ number_format((float) $tenant->deposit_refund, 2) }}</dd></div>
                    @if ($tenant->move_out_notes)
                        <div><dt class="text-slate-500">Move-out Notes</dt><dd class="whitespace-pre-line font-medium">{{ $tenant->move_out_notes }}</dd></div>
                    @endif
                @endif
            </dl>
        </section>

        <section class="space-y-6">
            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="font-semibold text-slate-950">Billing History</h2>
                <div class="mt-4 divide-y divide-slate-100">
                    @forelse ($tenant->bills as $bill)
                        <div class="flex items-center justify-between gap-3 py-3 text-sm">
                            <div>
                                <a href="{{ route('admin.bills.show', $bill) }}" class="font-medium text-slate-900 hover:underline">{{ $bill->billing_month?->format('F Y') }}</a>
                                <p class="text-slate-500">Balance: PHP {{ number_format($bill->balance, 2) }}</p>
                            </div>
                            <span class="capitalize">{{ $bill->status }}</span>
                        </div>
                    @empty
                        <p class="py-3 text-sm text-slate-500">No bills yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="font-semibold text-slate-950">Maintenance Requests</h2>
                <div class="mt-4 divide-y divide-slate-100">
                    @forelse ($tenant->maintenanceRequests as $request)
                        <div class="py-3 text-sm">
                            <a href="{{ route('admin.maintenance-requests.show', $request) }}" class="font-medium text-slate-900 hover:underline">{{ ucfirst($request->priority) }} priority</a>
                            <p class="text-slate-500">{{ ucfirst($request->status) }} - {{ $request->description }}</p>
                        </div>
                    @empty
                        <p class="py-3 text-sm text-slate-500">No maintenance requests yet.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
@endsection
