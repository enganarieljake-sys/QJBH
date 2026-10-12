<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public const METHODS = ['cash', 'GCash', 'bank transfer'];

    protected $fillable = [
        'bill_id',
        'tenant_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function receiptNumber(): string
    {
        return 'OR-'.($this->created_at ?? now())->format('Y').'-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
