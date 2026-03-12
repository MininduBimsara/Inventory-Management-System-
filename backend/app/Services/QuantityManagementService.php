<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\InventoryItemStatus;
use App\Models\ActivityLog;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Quantity Management Service
 *
 * This service encapsulates ALL business logic for quantity and status mutations.
 * It ensures:
 * - Quantity never goes below zero
 * - Status transitions follow defined business rules
 * - All changes are auditable
 * - Transaction safety for critical operations
 * - No raw quantity or status updates are allowed outside this service
 *
 * Design principle: SERVICE OWNS THE BUSINESS LOGIC
 * Controllers call service methods. Services never allow unsafe direct mutations.
 * Everything is logged. Every change is atomic.
 */
class QuantityManagementService
{
    /**
     * Increase quantity by a positive amount
     * (e.g., stock received, adjustment upward)
     *
     * @param InventoryItem $item
     * @param int $amount Must be positive
     * @param string $reason Adjustment reason/note for audit trail
     * @param int|null $userId User performing the action (if null, uses auth user)
     * @return array Contains 'item', 'old_quantity', 'new_quantity', 'new_status'
     *
     * @throws InvalidArgumentException if amount <= 0
     */
    public function increaseQuantity(
        InventoryItem $item,
        int $amount,
        string $reason,
        ?int $userId = null
    ): array {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount to increase must be greater than 0.');
        }

        $userId ??= auth()->id();
        $oldQuantity = $item->quantity;
        $oldStatus = $item->status;

        return DB::transaction(function () use (
            $item,
            $amount,
            $reason,
            $userId,
            $oldQuantity,
            $oldStatus
        ) {
            // Update quantity
            $newQuantity = $oldQuantity + $amount;
            $item->update(['quantity' => $newQuantity]);

            // Auto-recalculate status
            $newStatus = $this->calculateAutomaticStatus($item);
            if ($newStatus !== $oldStatus) {
                $item->update(['status' => $newStatus]);
            }

            // Fresh load to ensure we have latest values
            $item->refresh();

            // Log the activity
            $this->logActivity(
                user_id: $userId,
                action: ActivityAction::QUANTITY_INCREASED->value,
                item: $item,
                oldValues: [
                    'quantity' => $oldQuantity,
                    'status' => $oldStatus,
                ],
                newValues: [
                    'quantity' => $newQuantity,
                    'status' => $item->status,
                ],
                description: $reason,
            );

            return [
                'item' => $item,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'new_status' => $item->status,
                'status_changed' => $oldStatus !== $item->status,
            ];
        });
    }

    /**
     * Decrease quantity by a positive amount
     * (e.g., stock taken out, items removed)
     *
     * @param InventoryItem $item
     * @param int $amount Must be positive and not exceed current quantity
     * @param string $reason Adjustment reason/note for audit trail
     * @param int|null $userId User performing the action
     * @return array Contains 'item', 'old_quantity', 'new_quantity', 'new_status'
     *
     * @throws InvalidArgumentException if amount <= 0 or amount > quantity
     */
    public function decreaseQuantity(
        InventoryItem $item,
        int $amount,
        string $reason,
        ?int $userId = null
    ): array {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount to decrease must be greater than 0.');
        }

        if ($amount > $item->quantity) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot decrease quantity by %d. Current quantity is %d.',
                    $amount,
                    $item->quantity
                )
            );
        }

        $userId ??= auth()->id();
        $oldQuantity = $item->quantity;
        $oldStatus = $item->status;

        return DB::transaction(function () use (
            $item,
            $amount,
            $reason,
            $userId,
            $oldQuantity,
            $oldStatus
        ) {
            // Update quantity
            $newQuantity = $oldQuantity - $amount;
            $item->update(['quantity' => $newQuantity]);

            // Auto-recalculate status
            $newStatus = $this->calculateAutomaticStatus($item);
            if ($newStatus !== $oldStatus) {
                $item->update(['status' => $newStatus]);
            }

            // Fresh load
            $item->refresh();

            // Log the activity
            $this->logActivity(
                user_id: $userId,
                action: ActivityAction::QUANTITY_DECREASED->value,
                item: $item,
                oldValues: [
                    'quantity' => $oldQuantity,
                    'status' => $oldStatus,
                ],
                newValues: [
                    'quantity' => $newQuantity,
                    'status' => $item->status,
                ],
                description: $reason,
            );

            return [
                'item' => $item,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'new_status' => $item->status,
                'status_changed' => $oldStatus !== $item->status,
            ];
        });
    }

    /**
     * Mark item as Damaged
     *
     * This is an explicit, manual staff action.
     * Does NOT automatically decrease quantity.
     * (Damaged items handling during borrow/return will be defined in later steps)
     *
     * @param InventoryItem $item
     * @param string $reason Why item is damaged (required)
     * @param int|null $userId User performing the action
     * @return array Contains updated item and before/after values
     *
     * @throws InvalidArgumentException if reason is empty
     */
    public function markAsDamaged(
        InventoryItem $item,
        string $reason,
        ?int $userId = null
    ): array {
        if (blank($reason)) {
            throw new InvalidArgumentException('Reason for marking item as damaged is required.');
        }

        $userId ??= auth()->id();
        $oldStatus = $item->status;

        return DB::transaction(function () use (
            $item,
            $reason,
            $userId,
            $oldStatus
        ) {
            // Update status and store reason
            $item->update([
                'status' => InventoryItemStatus::DAMAGED->value,
                'manual_status_reason' => $reason,
            ]);

            // Fresh load
            $item->refresh();

            // Log the activity
            $this->logActivity(
                user_id: $userId,
                action: ActivityAction::MARKED_DAMAGED->value,
                item: $item,
                oldValues: [
                    'status' => $oldStatus,
                    'reason' => null,
                ],
                newValues: [
                    'status' => InventoryItemStatus::DAMAGED->value,
                    'reason' => $reason,
                ],
                description: "Marked as damaged: {$reason}",
            );

            return [
                'item' => $item,
                'old_status' => $oldStatus,
                'new_status' => InventoryItemStatus::DAMAGED->value,
            ];
        });
    }

    /**
     * Mark item as Missing
     *
     * This is an explicit, manual staff action.
     * Does NOT automatically decrease quantity.
     * (Missing items handling during borrow/return will be defined in later steps)
     *
     * @param InventoryItem $item
     * @param string $reason Why item is missing (required)
     * @param int|null $userId User performing the action
     * @return array Contains updated item and before/after values
     *
     * @throws InvalidArgumentException if reason is empty
     */
    public function markAsMissing(
        InventoryItem $item,
        string $reason,
        ?int $userId = null
    ): array {
        if (blank($reason)) {
            throw new InvalidArgumentException('Reason for marking item as missing is required.');
        }

        $userId ??= auth()->id();
        $oldStatus = $item->status;

        return DB::transaction(function () use (
            $item,
            $reason,
            $userId,
            $oldStatus
        ) {
            // Update status and store reason
            $item->update([
                'status' => InventoryItemStatus::MISSING->value,
                'manual_status_reason' => $reason,
            ]);

            // Fresh load
            $item->refresh();

            // Log the activity
            $this->logActivity(
                user_id: $userId,
                action: ActivityAction::MARKED_MISSING->value,
                item: $item,
                oldValues: [
                    'status' => $oldStatus,
                    'reason' => null,
                ],
                newValues: [
                    'status' => InventoryItemStatus::MISSING->value,
                    'reason' => $reason,
                ],
                description: "Marked as missing: {$reason}",
            );

            return [
                'item' => $item,
                'old_status' => $oldStatus,
                'new_status' => InventoryItemStatus::MISSING->value,
            ];
        });
    }

    /**
     * Restore item from Damaged status back to automatic status calculation
     *
     * This removes the manual Damaged override and recalculates status based on quantity.
     * Must include reason for audit trail.
     *
     * @param InventoryItem $item
     * @param string $reason Why item is being restored (required)
     * @param int|null $userId User performing the action
     * @return array Contains updated item and status transition details
     *
     * @throws InvalidArgumentException if not currently Damaged
     */
    public function restoreFromDamaged(
        InventoryItem $item,
        string $reason,
        ?int $userId = null
    ): array {
        if ($item->status !== InventoryItemStatus::DAMAGED->value) {
            throw new InvalidArgumentException(
                sprintf(
                    'Item is not marked as damaged. Current status: %s',
                    $item->status
                )
            );
        }

        if (blank($reason)) {
            throw new InvalidArgumentException('Reason for restoring from damaged status is required.');
        }

        $userId ??= auth()->id();
        $oldStatus = $item->status;
        $oldReason = $item->manual_status_reason;

        return DB::transaction(function () use (
            $item,
            $reason,
            $userId,
            $oldStatus,
            $oldReason
        ) {
            // Clear manual override and recalculate status
            $newStatus = $this->calculateAutomaticStatus($item);
            $item->update([
                'status' => $newStatus,
                'manual_status_reason' => null,
            ]);

            // Fresh load
            $item->refresh();

            // Log the activity
            $this->logActivity(
                user_id: $userId,
                action: ActivityAction::RESTORED_FROM_DAMAGED->value,
                item: $item,
                oldValues: [
                    'status' => $oldStatus,
                    'reason' => $oldReason,
                ],
                newValues: [
                    'status' => $newStatus,
                    'reason' => null,
                ],
                description: "Restored from damaged: {$reason}",
            );

            return [
                'item' => $item,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'restoration_reason' => $reason,
            ];
        });
    }

    /**
     * Restore item from Missing status back to automatic status calculation
     *
     * This removes the manual Missing override and recalculates status based on quantity.
     * Must include reason for audit trail.
     *
     * @param InventoryItem $item
     * @param string $reason Why item is being restored (required)
     * @param int|null $userId User performing the action
     * @return array Contains updated item and status transition details
     *
     * @throws InvalidArgumentException if not currently Missing
     */
    public function restoreFromMissing(
        InventoryItem $item,
        string $reason,
        ?int $userId = null
    ): array {
        if ($item->status !== InventoryItemStatus::MISSING->value) {
            throw new InvalidArgumentException(
                sprintf(
                    'Item is not marked as missing. Current status: %s',
                    $item->status
                )
            );
        }

        if (blank($reason)) {
            throw new InvalidArgumentException('Reason for restoring from missing status is required.');
        }

        $userId ??= auth()->id();
        $oldStatus = $item->status;
        $oldReason = $item->manual_status_reason;

        return DB::transaction(function () use (
            $item,
            $reason,
            $userId,
            $oldStatus,
            $oldReason
        ) {
            // Clear manual override and recalculate status
            $newStatus = $this->calculateAutomaticStatus($item);
            $item->update([
                'status' => $newStatus,
                'manual_status_reason' => null,
            ]);

            // Fresh load
            $item->refresh();

            // Log the activity
            $this->logActivity(
                user_id: $userId,
                action: ActivityAction::RESTORED_FROM_MISSING->value,
                item: $item,
                oldValues: [
                    'status' => $oldStatus,
                    'reason' => $oldReason,
                ],
                newValues: [
                    'status' => $newStatus,
                    'reason' => null,
                ],
                description: "Restored from missing: {$reason}",
            );

            return [
                'item' => $item,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'restoration_reason' => $reason,
            ];
        });
    }

    /**
     * Automatically calculate status based on quantity
     *
     * Business rules:
     * - If quantity > 0 AND item is not manually overridden: IN_STORE
     * - If quantity = 0 AND item is not manually overridden: BORROWED
     * - If item is Damaged or Missing: return current status (manual override)
     *
     * This method does NOT modify the item. It only calculates what the status should be.
     * Manual statuses (Damaged/Missing) take precedence and are never auto-overwritten.
     *
     * @param InventoryItem $item
     * @return string The calculated status value
     */
    public function calculateAutomaticStatus(InventoryItem $item): string
    {
        // Manual overrides take precedence: never auto-change Damaged or Missing
        if (in_array($item->status, [
            InventoryItemStatus::DAMAGED->value,
            InventoryItemStatus::MISSING->value,
        ])) {
            return $item->status;
        }

        // Auto-calculate based on quantity
        return $item->quantity > 0
            ? InventoryItemStatus::IN_STORE->value
            : InventoryItemStatus::BORROWED->value;
    }

    /**
     * Log an activity for audit trail
     *
     * This is the single point for all audit logging in quantity management.
     * Every mutation MUST call this to maintain complete traceability.
     *
     * @param int $userId User who performed the action
     * @param string $action ActivityAction enum value
     * @param InventoryItem $item Item being modified
     * @param array $oldValues Previous values (quantity, status, reason, etc)
     * @param array $newValues New values
     * @param string|null $description Human-readable description
     * @return ActivityLog The created log entry
     */
    private function logActivity(
        int $userId,
        string $action,
        InventoryItem $item,
        array $oldValues,
        array $newValues,
        ?string $description = null,
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => InventoryItem::class,
            'entity_id' => $item->id,
            'old_values' => json_encode($oldValues),
            'new_values' => json_encode($newValues),
            'description' => $description,
        ]);
    }
}
