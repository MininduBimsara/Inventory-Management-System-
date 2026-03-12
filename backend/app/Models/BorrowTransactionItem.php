<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrowTransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'borrow_transaction_id',
        'item_id',
        'quantity_borrowed',
        'quantity_returned',
        'item_condition_on_return',
    ];

    protected $casts = [
        'quantity_borrowed' => 'integer',
        'quantity_returned' => 'integer',
    ];

    public function borrowTransaction(): BelongsTo
    {
        return $this->belongsTo(BorrowTransaction::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function item(): BelongsTo
    {
        return $this->inventoryItem();
    }

    public function remainingToReturn(): int
    {
        return max(0, $this->quantity_borrowed - $this->quantity_returned);
    }

    public function isFullyReturned(): bool
    {
        return $this->remainingToReturn() === 0;
    }
}
