<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Concern extends Model
{
    public const CATEGORIES = [
        'noise' => 'Noise / disturbance',
        'roommate' => 'Roommate or neighbor',
        'safety' => 'Safety & security',
        'cleanliness' => 'Cleanliness',
        'billing' => 'Billing question',
        'suggestion' => 'Suggestion',
        'other' => 'Other',
    ];

    public const STATUSES = ['open', 'in-review', 'resolved'];

    protected $fillable = [
        'tenant_id',
        'category',
        'subject',
        'message',
        'status',
        'admin_reply',
        'replied_at',
    ];

    protected function casts(): array
    {
        return [
            'replied_at' => 'datetime',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }
}
