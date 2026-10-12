<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentChannel extends Model
{
    protected $fillable = [
        'name',
        'account_name',
        'account_number',
        'qr_path',
        'qr_disk',
        'instructions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function submissions()
    {
        return $this->hasMany(PaymentSubmission::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
