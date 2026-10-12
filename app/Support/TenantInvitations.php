<?php

namespace App\Support;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantInvitation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;

/**
 * Gives a tenant record a login account and emails them an invitation
 * link where they choose their own password.
 */
class TenantInvitations
{
    /**
     * @return array{sent: bool, message: string}
     */
    public static function send(Tenant $tenant): array
    {
        $existing = $tenant->user ?? User::where('email', $tenant->email)->first();

        if ($existing?->isStaff()) {
            return ['sent' => false, 'message' => 'This email belongs to a staff account, so no tenant invitation was sent.'];
        }

        if ($existing?->tenant && (int) $existing->tenant->id !== (int) $tenant->id) {
            return ['sent' => false, 'message' => 'This email is already linked to another tenant record, so no invitation was sent.'];
        }

        $user = self::ensureAccount($tenant, $existing);

        try {
            $token = Password::broker('invitations')->createToken($user);
            $user->notify(new TenantInvitation($tenant, $token));
        } catch (Throwable $e) {
            Log::warning('Tenant invitation email failed.', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);

            return ['sent' => false, 'message' => 'The invitation email could not be sent. Check the mail settings, then use "Resend invitation".'];
        }

        return ['sent' => true, 'message' => "Invitation sent to {$user->email}."];
    }

    private static function ensureAccount(Tenant $tenant, ?User $user): User
    {
        if (! $user) {
            // Random password nobody knows; the tenant sets a real one from the invitation link.
            $user = User::forceCreate([
                'name' => $tenant->full_name,
                'email' => $tenant->email,
                'password' => Hash::make(Str::random(40)),
                'role' => 'tenant',
                'tenant_approved_at' => now(),
            ]);
        } elseif ($user->tenant_approved_at === null) {
            $user->forceFill(['tenant_approved_at' => now()])->save();
        }

        if ((int) $tenant->user_id !== (int) $user->id) {
            $tenant->forceFill(['user_id' => $user->id])->save();
            $tenant->setRelation('user', $user);
        }

        return $user;
    }
}
