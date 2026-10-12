<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

/**
 * Short-lived, single-use code sent by email to confirm a dangerous action.
 */
class SecurityCode extends Model
{
    public const MAX_ATTEMPTS = 5;

    public const LIFETIME_MINUTES = 10;

    protected $fillable = ['user_id', 'purpose', 'code_hash', 'expires_at', 'attempts'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    /**
     * Create (or replace) the code for this user and purpose and return the
     * plain code so it can be emailed. Only the hash is stored.
     */
    public static function issue(User $user, string $purpose): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        static::updateOrCreate(
            ['user_id' => $user->id, 'purpose' => $purpose],
            ['code_hash' => Hash::make($code), 'expires_at' => now()->addMinutes(self::LIFETIME_MINUTES), 'attempts' => 0],
        );

        return $code;
    }

    /**
     * Check a code. A correct code is consumed; wrong guesses count toward
     * the attempt limit, after which the code is destroyed.
     */
    public static function verify(User $user, string $purpose, string $code): bool
    {
        $record = static::where('user_id', $user->id)->where('purpose', $purpose)->first();

        if (! $record || $record->expires_at->isPast() || $record->attempts >= self::MAX_ATTEMPTS) {
            $record?->delete();

            return false;
        }

        if (! Hash::check($code, $record->code_hash)) {
            $record->increment('attempts');

            if ($record->attempts >= self::MAX_ATTEMPTS) {
                $record->delete();
            }

            return false;
        }

        $record->delete();

        return true;
    }
}
