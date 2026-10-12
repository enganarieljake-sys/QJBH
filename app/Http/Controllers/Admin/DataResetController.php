<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityCode;
use App\Notifications\SecurityCodeMail;
use App\Support\DataReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * "Reset all data" needs three proofs: a code emailed to the admin, the
 * admin's password, and typing RESET.
 */
class DataResetController extends Controller
{
    private const PURPOSE = 'data-reset';

    public function sendCode(Request $request)
    {
        $user = $request->user();
        $email = self::codeRecipient($user->email);
        $code = SecurityCode::issue($user, self::PURPOSE);

        try {
            Notification::route('mail', $email)
                ->notify(new SecurityCodeMail($code, 'reset all data in Queen Jackie Boarding House', $user->name));
        } catch (Throwable $e) {
            Log::warning('Reset code email failed.', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            SecurityCode::where('user_id', $user->id)->where('purpose', self::PURPOSE)->delete();

            return back()->with('error', 'We could not send the code to '.$email.'. Check the mail settings and try again.');
        }

        return back()
            ->with('reset_code_sent', true)
            ->with('success', 'We sent a 6-digit code to '.$email.'. It expires in '.SecurityCode::LIFETIME_MINUTES.' minutes.');
    }

    public static function codeRecipient(string $fallback): string
    {
        return config('services.data_reset.email') ?: $fallback;
    }

    public function destroy(Request $request)
    {
        $request->validateWithBag('reset', [
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'current_password'],
            'confirmation' => ['required', 'in:RESET'],
        ], [
            'password.current_password' => 'Your password is incorrect.',
            'confirmation.in' => 'Type RESET in capital letters to confirm.',
        ]);

        if (! SecurityCode::verify($request->user(), self::PURPOSE, $request->input('code'))) {
            return back()
                ->with('reset_code_sent', true)
                ->withErrors(['code' => 'The code is wrong or expired. Send a new code if needed.'], 'reset');
        }

        $counts = DataReset::run();
        Log::warning('All data was reset by an admin.', ['user_id' => $request->user()->id, 'counts' => $counts]);

        return redirect()->route('admin.dashboard')
            ->with('success', 'All tenant and billing data was reset. Removed '.($counts['tenants'] ?? 0).' tenants and '.($counts['bills'] ?? 0).' bills.');
    }
}
