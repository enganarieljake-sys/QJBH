<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Lets an invited tenant choose their password from the emailed link.
 */
class InvitationController extends Controller
{
    public function show(Request $request, string $token)
    {
        return view('auth.invitation', [
            'token' => $token,
            'email' => (string) $request->query('email'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $user = null;
        $status = Password::broker('invitations')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $account, string $password) use (&$user) {
                $account->forceFill(['password' => Hash::make($password)])->save();
                $user = $account;
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'This invitation link is invalid or has expired. Please ask the admin to send a new one.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Your account is ready. Welcome!');
    }
}
