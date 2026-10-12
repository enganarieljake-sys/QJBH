<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistantAction extends Model
{
    /** A proposal can be confirmed for this long after the bot made it. */
    public const CONFIRM_WITHIN_MINUTES = 30;

    public const UNDO_WITHIN_MINUTES = 10;

    protected $fillable = ['user_id', 'assistant_message_id', 'type', 'title', 'lines', 'plan', 'status'];

    protected function casts(): array
    {
        return [
            'lines' => 'array',
            'plan' => 'array',
            'created_records' => 'array',
            'confirmed_at' => 'datetime',
            'undo_until' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isExpired(): bool
    {
        return $this->isPending() && $this->created_at->lt(now()->subMinutes(self::CONFIRM_WITHIN_MINUTES));
    }

    public function canUndo(): bool
    {
        return $this->status === 'done' && $this->undo_until?->isFuture() === true;
    }

    /**
     * What the chat card needs to draw this action.
     */
    public function card(): array
    {
        $status = $this->isExpired() ? 'expired' : $this->status;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'lines' => $this->lines,
            'status' => $status,
            'message' => $this->result_message,
            'can_undo' => $this->canUndo(),
            'by' => $this->user?->firstName(),
            'at' => ($this->confirmed_at ?? $this->updated_at)?->format('M j, g:i A'),
        ];
    }
}
