<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class ChecklistSchedule
{
    public static function currentPeriod(?Carbon $time = null): string
    {
        $time ??= now();

        return $time->hour < 12 ? 'morning' : 'evening';
    }

    public static function target(string $period, ?Carbon $date = null): Carbon
    {
        $date = ($date ?? now())->copy()->startOfDay();

        return $period === 'morning' ? $date->addHours(7) : $date->addHours(19);
    }

    public static function timingLabel(string $period, Carbon $time, bool $secondPerson = true): string
    {
        $minutes = (int) self::target($period, $time)->diffInMinutes($time, false);
        $subject = $secondPerson ? 'You are ' : '';

        if (abs($minutes) < 1) {
            return $subject.'on time';
        }

        $amount = abs($minutes);
        $duration = $amount >= 60
            ? intdiv($amount, 60).' hr'.(intdiv($amount, 60) === 1 ? '' : 's').($amount % 60 ? ' '.($amount % 60).' min' : '')
            : $amount.' min';

        return $subject.$duration.' '.($minutes > 0 ? 'late' : 'early');
    }
}
