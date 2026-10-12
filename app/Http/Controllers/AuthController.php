<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\ActivityNotification;
use App\Support\StaffAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'The provided credentials do not match our records.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended($this->redirectPath($request->user()))
            ->with('success', 'Welcome back.');
    }

    public function showRegister()
    {
        return view('auth.register', [
            'vacantRooms' => Room::vacant(),
            'houseRules' => Setting::get('house_rules'),
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'preferred_room_id' => ['nullable', 'exists:rooms,id'],
            'accept_rules' => ['accepted'],
        ], [
            'accept_rules.accepted' => 'Please read and agree to the house rules to apply.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'tenant',
            'tenant_approved_at' => null,
            'preferred_room_id' => $data['preferred_room_id'] ?? null,
        ]);
        $user->forceFill(['rules_accepted_at' => now()])->save();

        $roomNote = $user->preferredRoom ? ' for room '.$user->preferredRoom->room_number : '';

        Notification::send(User::where('role', 'admin')->get(), new ActivityNotification([
            'title' => 'New tenant application',
            'message' => $user->name.' ('.$user->email.') applied as a tenant'.$roomNote.'. Please verify and approve.',
            'url' => route('admin.users.show', $user),
            'kind' => 'application',
        ]));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('tenant.dashboard')
            ->with('success', 'Your application was sent and is waiting for admin approval.');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out.');
    }

    private function redirectPath(User $user): string
    {
        return StaffAccess::home($user);
    }
}
