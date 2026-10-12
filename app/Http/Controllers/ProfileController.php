<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Setting;
use App\Support\PaymentFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

/**
 * Lets every signed-in user update their display name and profile photo.
 */
class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
            'dashboard_color' => ['required', Rule::in(['orange', 'yellow', 'blue', 'gold', 'black', 'green', 'red', 'purple'])],
        ]);

        $user->name = $data['name'];

        if ($request->hasFile('avatar') || $request->boolean('remove_avatar')) {
            PaymentFiles::delete($user->avatar_disk, $user->avatar_path);
            $user->avatar_path = null;
            $user->avatar_disk = PaymentFiles::FALLBACK_DISK;
        }

        if ($request->hasFile('avatar')) {
            $stored = PaymentFiles::store($request->file('avatar'), 'avatars');
            $user->avatar_path = $stored['path'];
            $user->avatar_disk = $stored['disk'];
        }

        $user->save();
        Setting::put('dashboard_color_user_'.$user->getKey(), $data['dashboard_color']);

        return redirect()->route('profile.edit')->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults(), 'different:current_password'],
        ], [
            'current_password.current_password' => 'Your current password is incorrect.',
            'password.different' => 'Choose a new password that is different from your current one.',
        ]);

        $request->user()->forceFill(['password' => Hash::make($data['password'])])->save();
        $request->session()->regenerate();

        return redirect()->route('profile.edit')->with('success', 'Password changed successfully.');
    }

    public function avatar(User $user)
    {
        abort_unless($user->avatar_path, 404);

        return PaymentFiles::response($user->avatar_disk, $user->avatar_path, null, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
