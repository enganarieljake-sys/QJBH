<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    public const CATEGORIES = [
        'electricity' => 'Electricity (building)',
        'water' => 'Water (building)',
        'internet' => 'Internet',
        'repairs' => 'Repairs & maintenance',
        'supplies' => 'Supplies & cleaning',
        'salaries' => 'Staff & helpers',
        'taxes' => 'Taxes & permits',
        'other' => 'Other',
    ];

    protected $fillable = [
        'expense_date',
        'category',
        'description',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }
}
