<?php

namespace App\Models;

use App\Enums\InventoryItemStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Legacy status constants - kept for backward compatibility during Step 10 transition
     * These will be phased out in favor of InventoryItemStatus enum
     */
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_LOW_STOCK = 'low_stock';
    public const STATUS_OUT_OF_STOCK = 'out_of_stock';
    public const STATUS_DAMAGED = 'damaged';
    public const STATUS_INACTIVE = 'inactive';

    /**
     * @var array<int, string>
     */
    public const ALLOWED_STATUSES = [
        self::STATUS_AVAILABLE,
        self::STATUS_LOW_STOCK,
        self::STATUS_OUT_OF_STOCK,
        self::STATUS_DAMAGED,
        self::STATUS_INACTIVE,
    ];

    protected $fillable = [
        'place_id',
        'name',
        'code',
        'quantity',
        'serial_number',
        'image_path',
        'description',
        'status',
        'manual_status_reason',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'status' => 'string', // Will transition to enum cast after full Step 10 migration
    ];

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function borrowTransactionItems(): HasMany
    {
        return $this->hasMany(BorrowTransactionItem::class, 'item_id');
    }

    /**
     * Check if item status is automatic (calculated from quantity)
     */
    public function hasAutomaticStatus(): bool
    {
        return in_array($this->status, [
            InventoryItemStatus::IN_STORE->value,
            InventoryItemStatus::BORROWED->value,
        ]);
    }

    /**
     * Check if item status is manual (explicit staff action)
     */
    public function hasManualStatus(): bool
    {
        return in_array($this->status, [
            InventoryItemStatus::DAMAGED->value,
            InventoryItemStatus::MISSING->value,
        ]);
    }

    /**
     * Check if item is in stock (available for use)
     */
    public function isInStock(): bool
    {
        return $this->quantity > 0 && !$this->hasManualStatus();
    }

    /**
     * Check if item is available (not borrowed and not manually marked as damaged/missing)
     */
    public function isAvailable(): bool
    {
        return $this->status === InventoryItemStatus::IN_STORE->value;
    }
}
