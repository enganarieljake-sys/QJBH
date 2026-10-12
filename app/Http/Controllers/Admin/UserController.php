<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ActivityNotification;
use App\Notifications\StaffInvitation;
use App\Support\Search;
use App\Support\StaffAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Throwable;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $users = User::withPresence()->with('tenant')
            ->when($request->input('group') === 'staff', fn ($query) => $query->whereIn('role', User::STAFF_ROLES))
            ->tap(fn ($query) => Search::apply($query, $request->input('q'), ['name', 'email', 'role']))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.users.create', ['user' => new User(['role' => 'tenant'])]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(User::ROLES)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $data['role'],
                'password' => Hash::make($data['password']),
                'tenant_approved_at' => $data['role'] === 'tenant' ? now() : null,
            ]);

            if ($user->isTenant()) {
                $this->createOrLinkTenantRecord($user);
            }
        });

        return redirect()->route('admin.users.index')->with('success', 'User account created successfully.');
    }

    /**
     * Add a caretaker or panday and email them a link to set their own password.
     */
    public function inviteStaff(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['caretaker', 'maintenance'])],
        ]);

        // Random password nobody knows; they set a real one from the invitation link.
        $user = User::create([
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'role' => $data['role'],
            'password' => Hash::make(Str::random(40)),
        ]);

        try {
            $user->notify(new StaffInvitation(PasswordBroker::broker('invitations')->createToken($user)));
        } catch (Throwable $e) {
            Log::warning('Staff invitation email failed.', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return redirect()->route('admin.users.show', $user)
                ->with('warning', 'Staff account created, but the invitation email could not be sent. Check the mail settings, then set a password for them under Edit.');
        }

        return redirect()->route('admin.users.show', $user)
            ->with('success', StaffAccess::LABELS[$user->role]." account created. Invitation sent to {$user->email}.");
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $user->setAttribute('presence_last_activity', DB::table('sessions')->where('user_id', $user->id)->max('last_activity'));
        $user->load(['tenant.room', 'preferredRoom']);

        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', Rule::in(User::ROLES)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'tenant_approved_at' => $data['role'] === 'tenant' && $user->tenant_approved_at === null ? now() : ($data['role'] === 'tenant' ? $user->tenant_approved_at : null),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        DB::transaction(function () use ($user, $payload, $data) {
            $user->update($payload);

            if ($data['role'] === 'tenant' && $user->tenant_approved_at !== null) {
                $this->createOrLinkTenantRecord($user->refresh());
            }
        });

        return redirect()->route('admin.users.show', $user)->with('success', 'User account updated successfully.');
    }

    public function approveTenant(User $user)
    {
        if (! $user->isTenant()) {
            return back()->with('error', 'Only tenant users can be approved as tenants.');
        }

        DB::transaction(function () use ($user) {
            $this->createOrLinkTenantRecord($user);
            $user->forceFill(['tenant_approved_at' => now()])->save();
        });

        $user->notify(new ActivityNotification([
            'title' => 'Application approved',
            'message' => 'Welcome to Queen Jackie Boarding House! Your tenant portal is now open.',
            'url' => route('tenant.dashboard'),
            'kind' => 'application',
        ]));

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Tenant registration approved and linked to a tenant record.');
    }

    private function createOrLinkTenantRecord(User $user): void
    {
        if ($user->tenant) {
            return;
        }

        $tenant = Tenant::where('email', $user->email)->first();

        if ($tenant && $tenant->user_id && (int) $tenant->user_id !== (int) $user->id) {
            abort(422, 'A tenant record with this email is already linked to another user.');
        }

        if ($tenant) {
            $tenant->update(['user_id' => $user->id]);

            return;
        }

        $room = $user->preferredRoom;

        Tenant::create([
            'user_id' => $user->id,
            'room_id' => $room?->hasAvailableCapacity() ? $room->id : null,
            'full_name' => $user->name,
            'contact_number' => 'Pending update',
            'email' => $user->email,
            'emergency_contact' => 'Pending update',
            'valid_id_reference' => null,
            'move_in_date' => today(),
            'status' => 'reserved',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'You cannot delete your own account while logged in.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User account deleted successfully.');
    }
}
