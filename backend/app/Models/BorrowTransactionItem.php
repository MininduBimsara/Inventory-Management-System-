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
        'inventory_item_id',
        'quantity_borrowed',
        'quantity_returned',
        'line_status',
        'remarks',
    ];

    public function borrowTransaction(): BelongsTo
    {
        return $this->belongsTo(BorrowTransaction::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
