<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory;
    use SoftDeletes;

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
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
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
