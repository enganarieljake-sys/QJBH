<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeterReading extends Model
{
    protected $fillable = [
        'room_id',
        'billing_month',
        'electricity_previous',
        'electricity_current',
        'electricity_rate',
        'electricity_amount',
        'water_previous',
        'water_current',
        'water_rate',
        'water_amount',
    ];

    protected function casts(): array
    {
        return [
            'billing_month' => 'date',
            'electricity_previous' => 'decimal:2',
            'electricity_current' => 'decimal:2',
            'electricity_rate' => 'decimal:2',
            'electricity_amount' => 'decimal:2',
            'water_previous' => 'decimal:2',
            'water_current' => 'decimal:2',
            'water_rate' => 'decimal:2',
            'water_amount' => 'decimal:2',
        ];
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function electricityUsage(): float
    {
        return max((float) $this->electricity_current - (float) $this->electricity_previous, 0);
    }

    public function waterUsage(): float
    {
        return max((float) $this->water_current - (float) $this->water_previous, 0);
    }

    /**
     * Recompute peso amounts from the readings and the given rates.
     */
    public function applyRates(float $electricityRate, float $waterRate): void
    {
        $this->electricity_rate = $electricityRate;
        $this->water_rate = $waterRate;
        $this->electricity_amount = round($this->electricityUsage() * $electricityRate, 2);
        $this->water_amount = round($this->waterUsage() * $waterRate, 2);
    }
}
