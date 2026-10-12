<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'body',
        'is_pinned',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
        ];
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Pinned announcements first, then newest.
     */
    public function scopeForBoard($query)
    {
        return $query->orderByDesc('is_pinned')->latest();
    }
}
