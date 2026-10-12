<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistItem extends Model
{
    public const PERIODS = ['morning', 'evening', 'both'];

    protected $fillable = ['title', 'period', 'position', 'is_active'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'is_active' => 'boolean'];
    }

    public function scopeForPeriod($query, string $period)
    {
        return $query->where('is_active', true)->whereIn('period', [$period, 'both'])->orderBy('position')->orderBy('id');
    }
}
