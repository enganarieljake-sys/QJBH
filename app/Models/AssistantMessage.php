<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistantMessage extends Model
{
    protected $fillable = ['user_id', 'role', 'content', 'model', 'ai_requests'];

    protected function casts(): array
    {
        return [
            'ai_requests' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actions()
    {
        return $this->hasMany(AssistantAction::class);
    }
}
