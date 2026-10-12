<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    public const STATUSES = ['active', 'moved-out', 'reserved'];
    public const DEPOSIT_STATUSES = ['none', 'held', 'refunded', 'partially-refunded', 'forfeited'];

    protected $fillable = [
        'user_id',
        'room_id',
        'full_name',
        'contact_number',
        'email',
        'emergency_contact',
        'valid_id_reference',
        'move_in_date',
        'lease_end_date',
        'status',
        'deposit_amount',
        'deposit_status',
        'move_out_date',
        'deposit_deductions',
        'deposit_refund',
        'move_out_notes',
    ];

    protected function casts(): array
    {
        return [
            'move_in_date' => 'date',
            'lease_end_date' => 'date',
            'move_out_date' => 'date',
            'deposit_amount' => 'decimal:2',
            'deposit_deductions' => 'decimal:2',
            'deposit_refund' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function maintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class);
    }
}
