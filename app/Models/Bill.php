<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    public const STATUSES = ['pending', 'paid', 'overdue'];

    protected $fillable = [
        'tenant_id',
        'room_id',
        'billing_month',
        'room_rate',
        'electricity_amount',
        'water_amount',
        'other_charges',
        'total_amount',
        'due_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'billing_month' => 'date',
            'room_rate' => 'decimal:2',
            'electricity_amount' => 'decimal:2',
            'water_amount' => 'decimal:2',
            'other_charges' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'due_date' => 'date',
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

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentSubmissions()
    {
        return $this->hasMany(PaymentSubmission::class);
    }

    public function getPaidAmountAttribute(): float
    {
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->sum('amount');
        }

        return (float) $this->payments()->sum('amount');
    }

    public function getBalanceAttribute(): float
    {
        return max((float) $this->total_amount - $this->paid_amount, 0);
    }

    public function calculateTotal(): float
    {
        return (float) $this->room_rate
            + (float) $this->electricity_amount
            + (float) $this->water_amount
            + (float) $this->other_charges;
    }

    public function updatePaymentStatus(): void
    {
        $status = 'pending';

        if ($this->paid_amount >= (float) $this->total_amount) {
            $status = 'paid';
        } elseif ($this->due_date instanceof Carbon && $this->due_date->isPast() && ! $this->due_date->isToday()) {
            $status = 'overdue';
        }

        if ($this->status !== $status) {
            $this->forceFill(['status' => $status])->save();
        }
    }
}
