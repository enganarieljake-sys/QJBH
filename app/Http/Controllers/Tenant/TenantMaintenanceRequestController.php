<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenanceRequest;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class TenantMaintenanceRequestController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->isApprovedTenant()) {
            return view('tenant.maintenance-requests.index', ['tenant' => null, 'maintenanceRequests' => collect(), 'approvalPending' => true]);
        }

        $tenant = $request->user()->tenant;

        if (! $tenant) {
            return view('tenant.maintenance-requests.index', ['tenant' => null, 'maintenanceRequests' => collect(), 'approvalPending' => false]);
        }

        $maintenanceRequests = MaintenanceRequest::with('room')
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->paginate(10);

        return view('tenant.maintenance-requests.index', compact('tenant', 'maintenanceRequests'));
    }

    public function create(Request $request)
    {
        if (! $request->user()->isApprovedTenant()) {
            return redirect()->route('tenant.maintenance-requests.index')
                ->with('warning', 'Your account is still waiting for admin approval.');
        }

        $tenant = $request->user()->tenant()->with('room')->first();

        if (! $tenant || ! $tenant->room) {
            return redirect()->route('tenant.maintenance-requests.index')
                ->with('warning', 'Your account must be linked to an assigned room before filing a maintenance request.');
        }

        return view('tenant.maintenance-requests.create', [
            'tenant' => $tenant,
            'maintenanceRequest' => new MaintenanceRequest(['priority' => 'medium']),
            'priorities' => MaintenanceRequest::PRIORITIES,
        ]);
    }

    public function store(StoreMaintenanceRequest $request)
    {
        if (! $request->user()->isApprovedTenant()) {
            return redirect()->route('tenant.maintenance-requests.index')
                ->with('warning', 'Your account is still waiting for admin approval.');
        }

        $tenant = $request->user()->tenant()->with('room')->first();

        if (! $tenant || ! $tenant->room) {
            return redirect()->route('tenant.maintenance-requests.index')
                ->with('warning', 'Your account must be linked to an assigned room before filing a maintenance request.');
        }

        $maintenanceRequest = MaintenanceRequest::create([
            'tenant_id' => $tenant->id,
            'room_id' => $tenant->room_id,
            'description' => $request->validated('description'),
            'priority' => $request->validated('priority'),
            'status' => 'submitted',
        ]);

        Notification::send(User::staff(['admin', 'caretaker']), new ActivityNotification([
            'title' => 'New maintenance request',
            'message' => $tenant->full_name.' reported a '.$maintenanceRequest->priority.' priority issue in room '.$tenant->room->room_number.'.',
            'url' => route('admin.maintenance-requests.show', $maintenanceRequest),
            'kind' => 'maintenance',
        ]));

        return redirect()
            ->route('tenant.maintenance-requests.show', $maintenanceRequest)
            ->with('success', 'Maintenance request submitted successfully.');
    }

    public function show(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $tenant = $request->user()->tenant;

        abort_if(! $request->user()->isApprovedTenant() || ! $tenant || (int) $maintenanceRequest->tenant_id !== (int) $tenant->id, 403);

        $maintenanceRequest->load('room');

        return view('tenant.maintenance-requests.show', compact('tenant', 'maintenanceRequest'));
    }
}
