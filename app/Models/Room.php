<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    public const TYPES = ['single', 'double', 'shared'];
    public const STATUSES = ['available', 'occupied', 'under maintenance'];

    protected $fillable = [
        'room_number',
        'floor',
        'type',
        'capacity',
        'monthly_rate',
        'electricity_rate',
        'water_rate',
        'status',
        'photo',
        'photo_disk',
    ];

    protected function casts(): array
    {
        return [
            'floor' => 'integer',
            'capacity' => 'integer',
            'monthly_rate' => 'decimal:2',
            'electricity_rate' => 'decimal:2',
            'water_rate' => 'decimal:2',
        ];
    }

    /**
     * Sort room numbers naturally (1, 2, ... 10, 11) instead of as text (1, 10, 11, 2).
     */
    public function scopeOrderByNumber($query)
    {
        return $query->orderByRaw('LENGTH(room_number)')->orderBy('room_number');
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    public function activeTenants()
    {
        return $this->hasMany(Tenant::class)->where('status', 'active');
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function maintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function scopeFilter($query, array $filters)
    {
        return $query
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['floor'] ?? null, fn ($query, $floor) => $query->where('floor', $floor));
    }

    public function photoUrl(): ?string
    {
        return $this->photo
            ? route('rooms.photo', ['room' => $this, 'v' => $this->updated_at?->timestamp])
            : null;
    }

    /**
     * Rooms an applicant can still move into, with `slots_left` set on each.
     */
    public static function vacant()
    {
        return static::where('status', '!=', 'under maintenance')
            ->withCount('activeTenants')
            ->orderByNumber()
            ->get()
            ->each(fn (Room $room) => $room->slots_left = max($room->capacity - $room->active_tenants_count, 0))
            ->filter(fn (Room $room) => $room->slots_left > 0)
            ->values();
    }

    public function hasAvailableCapacity(?int $excludingTenantId = null): bool
    {
        if ($this->status === 'under maintenance') {
            return false;
        }

        $activeCount = $this->activeTenants()
            ->when($excludingTenantId, fn ($query) => $query->whereKeyNot($excludingTenantId))
            ->count();

        return $activeCount < $this->capacity;
    }

    public function syncOccupancyStatus(): void
    {
        if ($this->status === 'under maintenance') {
            return;
        }

        $activeCount = $this->activeTenants()->count();
        $this->status = $activeCount >= $this->capacity ? 'occupied' : 'available';
        $this->save();
    }
}
