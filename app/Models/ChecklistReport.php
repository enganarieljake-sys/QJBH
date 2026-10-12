<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistReport extends Model
{
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_MISSED = 'missed';
    public const STATUS_MISSED_OVERRIDE = 'missed_override';

    protected $fillable = [
        'user_id', 'report_date', 'period', 'status', 'completed_items', 'notes', 'submitted_at',
        'missed_reason', 'admin_note', 'overridden_at', 'overridden_by',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date', 'completed_items' => 'array', 'submitted_at' => 'datetime',
            'overridden_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function overrider()
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_MISSED => 'Missed',
            self::STATUS_MISSED_OVERRIDE => 'Missed Override',
            default => 'Completed',
        };
    }

    public function timingLabel(): ?string
    {
        return $this->submitted_at
            ? \App\Support\ChecklistSchedule::timingLabel($this->period, $this->submitted_at, false)
            : null;
    }
}
