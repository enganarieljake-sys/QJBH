<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Search;
use App\Support\TenantInvitations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TenantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $tenants = Tenant::with(['room', 'user' => fn ($query) => $query->withPresence()])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('room_id'), fn ($query) => $query->where('room_id', $request->room_id))
            ->tap(fn ($query) => Search::apply($query, $request->input('q'), ['full_name', 'email', 'contact_number', 'room.room_number']))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $people = User::withPresence()
            ->with('tenant.room')
            ->whereIn('role', User::ROLES)
            ->orderByDesc('presence_last_activity')
            ->orderBy('name')
            ->get();

        return view('admin.tenants.index', [
            'tenants' => $tenants,
            'rooms' => Room::orderByNumber()->get(),
            'statuses' => Tenant::STATUSES,
            'filters' => $request->only(['status', 'room_id']),
            'people' => $people,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.tenants.create', [
            'tenant' => new Tenant(['status' => 'active', 'move_in_date' => today()]),
            'rooms' => Room::withCount('activeTenants')->orderByNumber()->get(),
            'users' => User::where('role', 'tenant')->whereDoesntHave('tenant')->orderBy('name')->get(),
            'statuses' => Tenant::STATUSES,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTenantRequest $request)
    {
        $tenant = DB::transaction(function () use ($request) {
            $tenant = Tenant::create($request->validated());
            $tenant->room?->syncOccupancyStatus();

            return $tenant;
        });

        // Tenants linked to an existing login already have a password; only invite new ones.
        $invitation = $tenant->user_id
            ? ['sent' => false, 'message' => null]
            : TenantInvitations::send($tenant);

        return redirect()->route('admin.tenants.show', $tenant)
            ->with('success', 'Tenant record created successfully.'.($invitation['sent'] ? ' '.$invitation['message'] : ''))
            ->with($invitation['sent'] || ! $invitation['message'] ? [] : ['warning' => $invitation['message']]);
    }

    /**
     * Email the tenant a fresh invitation link to set their password.
     */
    public function invite(Tenant $tenant)
    {
        $invitation = TenantInvitations::send($tenant);

        return back()->with($invitation['sent'] ? 'success' : 'warning', $invitation['message']);
    }

    /**
     * Display the specified resource.
     */
    public function show(Tenant $tenant)
    {
        $tenant->load(['user', 'room', 'bills.payments', 'payments.bill', 'maintenanceRequests.room']);

        return view('admin.tenants.show', compact('tenant'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tenant $tenant)
    {
        return view('admin.tenants.edit', [
            'tenant' => $tenant,
            'rooms' => Room::withCount('activeTenants')->orderByNumber()->get(),
            'users' => User::where('role', 'tenant')
                ->where(function ($query) use ($tenant) {
                    $query->whereDoesntHave('tenant')
                        ->when($tenant->user_id, fn ($query) => $query->orWhere('id', $tenant->user_id));
                })
                ->orderBy('name')
                ->get(),
            'statuses' => Tenant::STATUSES,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant)
    {
        DB::transaction(function () use ($request, $tenant) {
            $oldRoom = $tenant->room;

            $tenant->update($request->validated());
            $tenant->refresh();

            $oldRoom?->syncOccupancyStatus();
            $tenant->room?->syncOccupancyStatus();
        });

        return redirect()->route('admin.tenants.show', $tenant)->with('success', 'Tenant record updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tenant $tenant)
    {
        $room = $tenant->room;
        $tenant->delete();
        $room?->syncOccupancyStatus();

        return redirect()->route('admin.tenants.index')->with('success', 'Tenant record deleted successfully.');
    }
}
