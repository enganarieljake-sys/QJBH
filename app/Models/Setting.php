<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public const DEFAULTS = [
        'electricity_rate' => '12.00',
        'water_rate' => '60.00',
        'reminder_days_before_due' => '3',
        'receipt_keep_months' => '3',
        'landing_headline' => 'Queen Jackie Boarding House Management System',
        'landing_intro' => 'Welcome to Queen Jackie Boarding House. Tenants can sign in to view their room details, check bills and payments, send maintenance requests, and read announcements from Ate Jackie, all in one place.',
        'landing_show_gallery' => '1',
        'landing_show_rooms' => '1',
        'landing_background_photo_id' => null,
        'house_rules' => "1. Curfew is at 10:00 PM. Inform Ate Jackie ahead if you will be late.\n2. Visitors are allowed until 8:00 PM and must be logged in the app.\n3. Keep noise down, especially from 10:00 PM to 6:00 AM.\n4. Keep your room and shared areas clean. Throw garbage in the proper bins.\n5. No smoking, alcohol, or illegal substances inside the boarding house.\n6. Cooking is only allowed in the kitchen area.\n7. Turn off lights, fans, and appliances when not in use.\n8. Pay rent on or before the due date. Late payments may be charged a penalty.\n9. Report damages or repairs right away through the Maintenance page.\n10. Give at least 30 days notice before moving out to get your deposit back.",
    ];

    protected $fillable = ['key', 'value'];

    /** @var array<string, string|null> */
    private static array $cache = [];

    public static function get(string $key, ?string $default = null): ?string
    {
        if (! array_key_exists($key, self::$cache)) {
            self::$cache[$key] = static::where('key', $key)->value('value');
        }

        return self::$cache[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function number(string $key): float
    {
        return (float) static::get($key);
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        self::$cache[$key] = $value;
    }

    public static function flushCache(): void
    {
        self::$cache = [];
    }
}
