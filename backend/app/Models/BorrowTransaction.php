<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BorrowTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'borrower_name',
        'borrower_contact',
        'borrow_date',
        'expected_return_date',
        'return_date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'borrow_date' => 'date',
        'expected_return_date' => 'date',
        'return_date' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function borrowTransactionItems(): HasMany
    {
        return $this->hasMany(BorrowTransactionItem::class);
    }
}
