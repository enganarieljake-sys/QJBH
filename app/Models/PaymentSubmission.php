<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSubmission extends Model
{
    public const STATUSES = ['pending', 'approved', 'rejected'];

    protected $fillable = [
        'bill_id',
        'tenant_id',
        'payment_channel_id',
        'payment_date',
        'amount',
        'reference_number',
        'receipt_path',
        'receipt_disk',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'reviewed_at' => 'datetime',
            'receipt_purged_at' => 'datetime',
        ];
    }

    public function hasReceiptImage(): bool
    {
        return $this->receipt_purged_at === null;
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function channel()
    {
        return $this->belongsTo(PaymentChannel::class, 'payment_channel_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
