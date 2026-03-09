<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'place_id',
        'item_name',
        'code',
        'quantity',
        'serial_number',
        'image_path',
        'description',
        'status',
    ];

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function borrowTransactionItems(): HasMany
    {
        return $this->hasMany(BorrowTransactionItem::class);
    }
}
