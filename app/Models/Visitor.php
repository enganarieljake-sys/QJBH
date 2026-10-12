<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    protected $fillable = [
        'tenant_id',
        'visitor_name',
        'relationship',
        'purpose',
        'visit_date',
        'time_in',
        'time_out',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isInside(): bool
    {
        return $this->time_out === null;
    }

    public static function formatTime(?string $time): string
    {
        return $time ? Carbon::createFromFormat('H:i:s', strlen($time) === 5 ? $time.':00' : $time)->format('g:i A') : '—';
    }
}
