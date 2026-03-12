<?php

namespace App\Enums;

/**
 * Inventory Item Status Enumeration
 *
 * Statuses are divided into two categories:
 * 1. AUTOMATIC: Recalculated based on quantity and business logic
 *    - IN_STORE: Has quantity available (qty > 0, no manual override)
 *    - BORROWED: No quantity available (qty = 0, not manually marked as damaged/missing)
 *
 * 2. MANUAL: Explicit staff actions requiring reason
 *    - DAMAGED: Marked as damaged by staff (reason required)
 *    - MISSING: Marked as missing by staff (reason required)
 *
 * Design principle:
 * - In-Store and Borrowed are AUTOMATIC and system-controlled
 * - Damaged and Missing are MANUAL and user-controlled
 * - Manual statuses can coexist with any quantity state
 * - Status transitions follow clear business rules
 */
enum InventoryItemStatus: string
{
    case IN_STORE = 'in_store';
    case BORROWED = 'borrowed';
    case DAMAGED = 'damaged';
    case MISSING = 'missing';

    /**
     * Get all automatic statuses (calculated from quantity)
     */
    public static function automaticStatuses(): array
    {
        return [
            self::IN_STORE,
            self::BORROWED,
        ];
    }

    /**
     * Get all manual statuses (explicit staff action)
     */
    public static function manualStatuses(): array
    {
        return [
            self::DAMAGED,
            self::MISSING,
        ];
    }

    /**
     * Get all status values as strings
     */
    public static function allValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if status is automatic
     */
    public function isAutomatic(): bool
    {
        return in_array($this->value, array_column(self::automaticStatuses(), 'value'));
    }

    /**
     * Check if status is manual
     */
    public function isManual(): bool
    {
        return in_array($this->value, array_column(self::manualStatuses(), 'value'));
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::IN_STORE => 'In Store',
            self::BORROWED => 'Borrowed',
            self::DAMAGED => 'Damaged',
            self::MISSING => 'Missing',
        };
    }
}