<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    protected $fillable = [
        'user_id', 'endpoint_hash', 'endpoint', 'public_key', 'auth_token',
        'content_encoding', 'device_label', 'user_agent', 'last_success_at', 'failure_count',
    ];

    protected function casts(): array
    {
        return ['last_success_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
