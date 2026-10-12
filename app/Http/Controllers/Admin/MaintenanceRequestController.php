<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenanceRequest;
use App\Http\Requests\UpdateMaintenanceRequest;
use App\Models\MaintenanceRequest;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ActivityNotification;
use App\Notifications\RepairTicketMail;
use App\Support\Search;
use App\Support\PaymentFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Throwable;

class MaintenanceRequestController extends Controller
{
    public function history(Request $request)
    {
        $isPanday = $request->user()->role === 'maintenance';
        $maintenanceRequests = MaintenanceRequest::with(['tenant', 'room', 'assignee', 'toolsVerifier'])
            ->whereIn('status', ['awaiting-verification', 'resolved'])
            ->when($isPanday, fn ($query) => $query->where('assigned_to', $request->user()->id))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->tap(fn ($query) => Search::apply($query, $request->input('q'), ['description', 'resolution_details', 'tenant.full_name', 'room.room_number', 'assignee.name']))
            ->latest('tools_returned_at')->latest('updated_at')->paginate(15)->withQueryString();

        return view('admin.maintenance-requests.history', compact('maintenanceRequests', 'isPanday'));
    }

    public function historyShow(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        abort_unless(in_array($maintenanceRequest->status, ['awaiting-verification', 'resolved'], true), 404);
        $this->guardPanday($request, $maintenanceRequest);
        $maintenanceRequest->load(['tenant', 'room', 'assignee', 'assigner', 'toolsVerifier']);

        return view('admin.maintenance-requests.show', [
            'maintenanceRequest' => $maintenanceRequest, 'pandays' => collect(),
            'priorities' => MaintenanceRequest::PRIORITIES, 'fromHistory' => true,
        ]);
    }

    public function triggerAdmin(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        abort_unless($request->user()->role === 'maintenance', 403);
        abort_unless($maintenanceRequest->isAssignedTo($request->user()), 403);
        abort_unless(in_array($maintenanceRequest->status, ['awaiting-verification', 'resolved'], true), 422);

        $maintenanceRequest->load('room');
        Notification::send(User::staff(['admin']), new ActivityNotification([
            'title' => 'Panday follow-up: maintenance done',
            'message' => $request->user()->name.' is reminding you that '.$maintenanceRequest->ticketLabel().' is done. Tap to review the full history.',
            'url' => route('admin.maintenance-history.show', $maintenanceRequest),
            'kind' => 'maintenance-reminder',
        ]));

        return back()->with('success', 'Admin notified again that this maintenance work is done.');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $isPanday = $request->user()->role === 'maintenance';

        $maintenanceRequests = MaintenanceRequest::with(['tenant', 'room', 'assignee'])
            // A panday's list is their inbox: only tickets forwarded to them.
            ->when($isPanday, fn ($query) => $query->where('assigned_to', $request->user()->id)->orderByRaw("CASE WHEN status = 'resolved' THEN 1 ELSE 0 END"))
            ->when($request->input('assigned') === 'none', fn ($query) => $query->whereNull('assigned_to'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->priority))
            ->tap(fn ($query) => Search::apply($query, $request->input('q'), ['description', 'tenant.full_name', 'room.room_number']))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.maintenance-requests.index', [
            'maintenanceRequests' => $maintenanceRequests,
            'statuses' => MaintenanceRequest::STATUSES,
            'priorities' => MaintenanceRequest::PRIORITIES,
            'filters' => $request->only(['status', 'priority', 'assigned']),
            'isPanday' => $isPanday,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.maintenance-requests.create', [
            'maintenanceRequest' => new MaintenanceRequest(['status' => 'submitted', 'priority' => 'medium']),
            'tenants' => Tenant::with('room')->orderBy('full_name')->get(),
            'rooms' => Room::orderByNumber()->get(),
            'statuses' => MaintenanceRequest::STATUSES,
            'priorities' => MaintenanceRequest::PRIORITIES,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMaintenanceRequest $request)
    {
        $maintenanceRequest = MaintenanceRequest::create($request->validated());

        return redirect()
            ->route('admin.maintenance-requests.show', $maintenanceRequest)
            ->with('success', 'Maintenance request created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $this->guardPanday($request, $maintenanceRequest);
        $maintenanceRequest->load(['tenant', 'room', 'assignee', 'assigner', 'toolsVerifier']);

        return view('admin.maintenance-requests.show', [
            'maintenanceRequest' => $maintenanceRequest,
            'pandays' => User::where('role', 'maintenance')->orderBy('name')->get(),
            'priorities' => MaintenanceRequest::PRIORITIES,
        ]);
    }

    /**
     * Forwards the repair to a panday's inbox with the owner's instructions.
     */
    public function assign(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $data = $request->validate([
            'assigned_to' => ['required', Rule::exists('users', 'id')->where('role', 'maintenance')],
            'assignment_notes' => ['required', 'string', 'max:2000'],
            'priority' => ['required', Rule::in(MaintenanceRequest::PRIORITIES)],
            'target_date' => ['nullable', 'date', 'after_or_equal:today'],
        ], [
            'assigned_to.exists' => 'Pick a maintenance (panday) account.',
            'assignment_notes.required' => 'Write what needs to be fixed so the panday knows.',
        ]);

        if ($maintenanceRequest->assigned_to && (int) $maintenanceRequest->assigned_to !== (int) $data['assigned_to']) {
            PaymentFiles::delete($maintenanceRequest->tools_taken_disk, $maintenanceRequest->tools_taken_photo);
            PaymentFiles::delete($maintenanceRequest->tools_returned_disk, $maintenanceRequest->tools_returned_photo);
            $maintenanceRequest->forceFill([
                'tools_taken_photo' => null, 'tools_taken_disk' => null, 'tools_used' => null, 'tools_taken_at' => null,
                'tools_returned_photo' => null, 'tools_returned_disk' => null, 'tools_returned_at' => null,
                'tools_verified_at' => null, 'tools_verified_by' => null,
            ]);
        }

        $maintenanceRequest->forceFill($data + [
            'assigned_by' => $request->user()->id,
            'assigned_at' => now(),
        ])->save();
        $maintenanceRequest->load(['room', 'assignee', 'assigner']);

        $panday = $maintenanceRequest->assignee;
        $panday->notify(new ActivityNotification([
            'title' => 'New repair ticket',
            'message' => $maintenanceRequest->ticketLabel().'. '.str($data['assignment_notes'])->limit(80),
            'url' => route('admin.maintenance-requests.show', $maintenanceRequest),
            'kind' => 'maintenance',
        ]));

        try {
            $panday->notify(new RepairTicketMail($maintenanceRequest));
        } catch (Throwable $e) {
            Log::warning('Repair ticket email failed.', ['ticket' => $maintenanceRequest->id, 'error' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.maintenance-requests.show', $maintenanceRequest)
            ->with('success', 'Forwarded to '.$panday->name.'. They were notified.');
    }

    /**
     * The panday (or the owner/caretaker) reports progress on a ticket.
     */
    public function progress(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $this->guardPanday($request, $maintenanceRequest);

        $data = $request->validate([
            'status' => ['required', Rule::in(['in-progress'])],
            'resolution_details' => ['nullable', 'string', 'max:5000'],
        ]);

        $previousStatus = $maintenanceRequest->status;
        $maintenanceRequest->forceFill($data + [
            'completion_date' => null,
        ])->save();
        $maintenanceRequest->load(['room', 'tenant.user']);

        if ($maintenanceRequest->status !== $previousStatus) {
            $maintenanceRequest->tenant?->user?->notify(new ActivityNotification([
                'title' => 'Maintenance request updated',
                'message' => 'Your maintenance request is now '.str_replace('-', ' ', $maintenanceRequest->status).'.',
                'url' => route('tenant.maintenance-requests.show', $maintenanceRequest),
                'kind' => 'maintenance',
            ]));

            $who = $request->user();
            $staff = User::staff(['admin', 'caretaker'])->reject(fn (User $user) => $user->is($who));
            Notification::send($staff, new ActivityNotification([
                'title' => 'Repair started',
                'message' => $who->firstName().': '.$maintenanceRequest->ticketLabel(),
                'url' => route('admin.maintenance-requests.show', $maintenanceRequest),
                'kind' => 'maintenance',
            ]));
        }

        return redirect()
            ->route('admin.maintenance-requests.show', $maintenanceRequest)
            ->with('success', 'Marked as in progress.');
    }

    public function uploadTools(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $this->guardPanday($request, $maintenanceRequest);
        abort_unless($request->user()->role === 'maintenance', 403);
        $data = $request->validate([
            'tools_photo' => ['required', 'image', 'max:5120'],
            'tools_used' => ['required', 'string', 'max:2000'],
        ]);

        PaymentFiles::delete($maintenanceRequest->tools_taken_disk, $maintenanceRequest->tools_taken_photo);
        $stored = PaymentFiles::store($request->file('tools_photo'), 'maintenance-tools');
        $maintenanceRequest->forceFill([
            'tools_taken_photo' => $stored['path'],
            'tools_taken_disk' => $stored['disk'],
            'tools_used' => $data['tools_used'],
            'tools_taken_at' => now(),
            'status' => 'in-progress',
        ])->save();

        Notification::send(User::staff(['admin']), new ActivityNotification([
            'title' => 'Panday checked out tools',
            'message' => $request->user()->firstName().': '.$maintenanceRequest->ticketLabel(),
            'url' => route('admin.maintenance-requests.show', $maintenanceRequest),
            'kind' => 'maintenance',
        ]));

        return back()->with('success', 'Tool photo recorded. Upload a return photo after putting every tool back.');
    }

    public function returnTools(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $this->guardPanday($request, $maintenanceRequest);
        abort_unless($request->user()->role === 'maintenance' && $maintenanceRequest->tools_taken_photo, 403);
        $data = $request->validate([
            'returned_photo' => ['required', 'image', 'max:5120'],
            'tools_returned' => ['accepted'],
            'resolution_details' => ['required', 'string', 'max:5000'],
        ], ['tools_returned.accepted' => 'Confirm that every tool was returned to its proper place.']);

        PaymentFiles::delete($maintenanceRequest->tools_returned_disk, $maintenanceRequest->tools_returned_photo);
        $stored = PaymentFiles::store($request->file('returned_photo'), 'maintenance-tools');
        $maintenanceRequest->forceFill([
            'tools_returned_photo' => $stored['path'],
            'tools_returned_disk' => $stored['disk'],
            'tools_returned_at' => now(),
            'resolution_details' => $data['resolution_details'],
            'status' => 'awaiting-verification',
        ])->save();

        Notification::send(User::staff(['admin']), new ActivityNotification([
            'title' => 'Tools returned — verification needed',
            'message' => $request->user()->firstName().' returned the tools for '.$maintenanceRequest->ticketLabel().'. Check the photo and verify.',
            'url' => route('admin.maintenance-requests.show', $maintenanceRequest),
            'kind' => 'maintenance',
        ]));

        return back()->with('success', 'Return report sent. Ate Jackie must verify it before the ticket closes.');
    }

    public function verifyTools(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless($maintenanceRequest->tools_returned_photo && $maintenanceRequest->status === 'awaiting-verification', 422);

        $maintenanceRequest->forceFill([
            'tools_verified_at' => now(),
            'tools_verified_by' => $request->user()->id,
            'status' => 'resolved',
            'completion_date' => today(),
        ])->save();
        $maintenanceRequest->load(['tenant.user', 'assignee']);

        $maintenanceRequest->assignee?->notify(new ActivityNotification([
            'title' => 'Tool return verified',
            'message' => 'Ate Jackie verified the returned tools. Ticket closed: '.$maintenanceRequest->ticketLabel(),
            'url' => route('admin.maintenance-requests.show', $maintenanceRequest),
            'kind' => 'maintenance',
        ]));
        $maintenanceRequest->tenant?->user?->notify(new ActivityNotification([
            'title' => 'Maintenance request resolved',
            'message' => 'Your repair has been completed and verified.',
            'url' => route('tenant.maintenance-requests.show', $maintenanceRequest),
            'kind' => 'maintenance',
        ]));

        return back()->with('success', 'Tool return verified. The repair ticket is now closed.');
    }

    public function toolPhoto(Request $request, MaintenanceRequest $maintenanceRequest, string $kind)
    {
        $this->guardPanday($request, $maintenanceRequest);
        abort_unless(in_array($kind, ['taken', 'returned'], true), 404);
        $path = $kind === 'taken' ? $maintenanceRequest->tools_taken_photo : $maintenanceRequest->tools_returned_photo;
        $disk = $kind === 'taken' ? $maintenanceRequest->tools_taken_disk : $maintenanceRequest->tools_returned_disk;

        return PaymentFiles::response($disk, $path, null, ['Cache-Control' => 'private, max-age=3600']);
    }

    /**
     * A panday only works on tickets forwarded to them.
     */
    private function guardPanday(Request $request, MaintenanceRequest $maintenanceRequest): void
    {
        abort_if(
            $request->user()->role === 'maintenance' && ! $maintenanceRequest->isAssignedTo($request->user()),
            403,
            'This ticket was not forwarded to you.',
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MaintenanceRequest $maintenanceRequest)
    {
        return view('admin.maintenance-requests.edit', [
            'maintenanceRequest' => $maintenanceRequest,
            'tenants' => Tenant::with('room')->orderBy('full_name')->get(),
            'rooms' => Room::orderByNumber()->get(),
            'statuses' => MaintenanceRequest::STATUSES,
            'priorities' => MaintenanceRequest::PRIORITIES,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMaintenanceRequest $request, MaintenanceRequest $maintenanceRequest)
    {
        if ($request->input('status') === 'resolved' && $maintenanceRequest->assigned_to && ! $maintenanceRequest->tools_verified_at) {
            return back()->withErrors(['status' => 'Verify the returned tools photo before closing this assigned ticket.'])->withInput();
        }

        $previousStatus = $maintenanceRequest->status;
        $maintenanceRequest->update($request->validated());

        if ($maintenanceRequest->status !== $previousStatus) {
            $maintenanceRequest->load('tenant.user');
            $maintenanceRequest->tenant?->user?->notify(new ActivityNotification([
                'title' => 'Maintenance request updated',
                'message' => 'Your maintenance request is now '.str_replace('-', ' ', $maintenanceRequest->status).'.',
                'url' => route('tenant.maintenance-requests.show', $maintenanceRequest),
                'kind' => 'maintenance',
            ]));
        }

        return redirect()
            ->route('admin.maintenance-requests.show', $maintenanceRequest)
            ->with('success', 'Maintenance request updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MaintenanceRequest $maintenanceRequest)
    {
        PaymentFiles::delete($maintenanceRequest->tools_taken_disk, $maintenanceRequest->tools_taken_photo);
        PaymentFiles::delete($maintenanceRequest->tools_returned_disk, $maintenanceRequest->tools_returned_photo);
        $maintenanceRequest->delete();

        return redirect()->route('admin.maintenance-requests.index')->with('success', 'Maintenance request deleted successfully.');
    }
}
