<?php

namespace App\Enums;

/**
 * Activity Log Action Types for Audit Trail
 *
 * Used to categorize all inventory mutations in audit logs
 * enabling complete traceability of stock changes and status updates.
 */
enum ActivityAction: string
{
    // Quantity mutations
    case QUANTITY_INCREASED = 'quantity_increased';
    case QUANTITY_DECREASED = 'quantity_decreased';
    case QUANTITY_ADJUSTED = 'quantity_adjusted';

    // Status changes
    case STATUS_CHANGED = 'status_changed';
    case STATUS_AUTO_UPDATED = 'status_auto_updated';
    case MARKED_DAMAGED = 'marked_damaged';
    case MARKED_MISSING = 'marked_missing';
    case RESTORED_FROM_DAMAGED = 'restored_from_damaged';
    case RESTORED_FROM_MISSING = 'restored_from_missing';

    public function label(): string
    {
        return match ($this) {
            self::QUANTITY_INCREASED => 'Quantity Increased',
            self::QUANTITY_DECREASED => 'Quantity Decreased',
            self::QUANTITY_ADJUSTED => 'Quantity Adjusted',
            self::STATUS_CHANGED => 'Status Changed',
            self::STATUS_AUTO_UPDATED => 'Status Auto-Updated',
            self::MARKED_DAMAGED => 'Marked as Damaged',
            self::MARKED_MISSING => 'Marked as Missing',
            self::RESTORED_FROM_DAMAGED => 'Restored from Damaged',
            self::RESTORED_FROM_MISSING => 'Restored from Missing',
        };
    }
}
