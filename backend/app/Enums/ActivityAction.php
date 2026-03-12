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
    // Item lifecycle
    case ITEM_CREATED = 'item_created';
    case ITEM_UPDATED = 'item_updated';

    // User lifecycle
    case USER_CREATED = 'user_created';

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

    // Borrow and return workflow
    case BORROW_CREATED = 'borrow_created';
    case RETURN_PROCESSED = 'return_processed';
    case RETURN_DAMAGED_RECORDED = 'return_damaged_recorded';
    case RETURN_MISSING_RECORDED = 'return_missing_recorded';

    public function label(): string
    {
        return match ($this) {
            self::ITEM_CREATED => 'Item Created',
            self::ITEM_UPDATED => 'Item Updated',
            self::USER_CREATED => 'User Created',
            self::QUANTITY_INCREASED => 'Quantity Increased',
            self::QUANTITY_DECREASED => 'Quantity Decreased',
            self::QUANTITY_ADJUSTED => 'Quantity Adjusted',
            self::STATUS_CHANGED => 'Status Changed',
            self::STATUS_AUTO_UPDATED => 'Status Auto-Updated',
            self::MARKED_DAMAGED => 'Marked as Damaged',
            self::MARKED_MISSING => 'Marked as Missing',
            self::RESTORED_FROM_DAMAGED => 'Restored from Damaged',
            self::RESTORED_FROM_MISSING => 'Restored from Missing',
            self::BORROW_CREATED => 'Borrow Transaction Created',
            self::RETURN_PROCESSED => 'Borrow Return Processed',
            self::RETURN_DAMAGED_RECORDED => 'Damaged Return Recorded',
            self::RETURN_MISSING_RECORDED => 'Missing Return Recorded',
        };
    }
}
