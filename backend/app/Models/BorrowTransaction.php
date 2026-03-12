<?php

namespace App\Models;

use App\Enums\BorrowTransactionStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

class BorrowTransaction extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_RETURNED = 'RETURNED';
    public const STATUS_OVERDUE = 'OVERDUE';

    protected $fillable = [
        'borrower_name',
        'borrower_contact',
        'borrow_date',
        'expected_return_date',
        'actual_return_date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'borrow_date' => 'immutable_date',
        'expected_return_date' => 'immutable_date',
        'actual_return_date' => 'immutable_date',
    ];

    protected static function booted(): void
    {
        static::deleting(function (): void {
            throw new LogicException('Borrow transactions are immutable and cannot be deleted.');
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function borrowTransactionItems(): HasMany
    {
        return $this->hasMany(BorrowTransactionItem::class);
    }

    public function items(): HasMany
    {
        return $this->borrowTransactionItems();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeReturned(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RETURNED);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder->where('status', self::STATUS_OVERDUE)
                ->orWhere(function (Builder $activeBuilder): void {
                    $activeBuilder
                        ->where('status', self::STATUS_ACTIVE)
                        ->whereDate('expected_return_date', '<', now()->toDateString());
                });
        });
    }

    public function hasPendingReturns(): bool
    {
        return $this->borrowTransactionItems()
            ->whereColumn('quantity_returned', '<', 'quantity_borrowed')
            ->exists();
    }

    public function resolveStatus(): string
    {
        if (!$this->hasPendingReturns()) {
            return BorrowTransactionStatus::RETURNED->value;
        }

        if ($this->expected_return_date instanceof CarbonImmutable && $this->expected_return_date->isPast()) {
            return BorrowTransactionStatus::OVERDUE->value;
        }

        return BorrowTransactionStatus::ACTIVE->value;
    }
}
