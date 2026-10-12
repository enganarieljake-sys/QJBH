<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    public const PRIORITIES = ['low', 'medium', 'high'];
    public const STATUSES = ['submitted', 'in-progress', 'awaiting-verification', 'resolved'];

    protected $fillable = [
        'tenant_id',
        'room_id',
        'description',
        'priority',
        'status',
        'resolution_details',
        'completion_date',
        'tools_used',
    ];

    protected function casts(): array
    {
        return [
            'completion_date' => 'date',
            'assigned_at' => 'datetime',
            'target_date' => 'date',
            'tools_taken_at' => 'datetime',
            'tools_returned_at' => 'datetime',
            'tools_verified_at' => 'datetime',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /** The maintenance worker (panday) the ticket was forwarded to. */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function toolsVerifier()
    {
        return $this->belongsTo(User::class, 'tools_verified_by');
    }

    public function isAssignedTo(User $user): bool
    {
        return (int) $this->assigned_to === (int) $user->id;
    }

    /**
     * Short label for notifications and the panday's inbox, e.g. "Room 4: Leaking faucet".
     */
    public function ticketLabel(): string
    {
        return 'Room '.($this->room?->room_number ?? '?').': '.str($this->description)->limit(60);
    }
}
