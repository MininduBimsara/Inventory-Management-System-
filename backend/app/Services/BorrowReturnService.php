<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\BorrowTransactionStatus;
use App\Enums\ReturnCondition;
use App\Models\ActivityLog;
use App\Models\BorrowTransaction;
use App\Models\BorrowTransactionItem;
use App\Models\InventoryItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BorrowReturnService
{
    public function __construct(
        private readonly QuantityManagementService $quantityManagementService,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function returnItems(BorrowTransaction $borrow, array $payload, int $userId): BorrowTransaction
    {
        /** @var array<int, array{borrow_transaction_item_id:int, quantity_returned:int, item_condition_on_return:string}> $returnLines */
        $returnLines = collect($payload['items'])
            ->map(fn (array $line): array => [
                'borrow_transaction_item_id' => (int) $line['borrow_transaction_item_id'],
                'quantity_returned' => (int) $line['quantity_returned'],
                'item_condition_on_return' => (string) $line['item_condition_on_return'],
            ])
            ->values()
            ->all();

        $returnedAt = isset($payload['returned_at'])
            ? CarbonImmutable::parse((string) $payload['returned_at'])
            : CarbonImmutable::now();

        return DB::transaction(function () use ($borrow, $returnLines, $userId, $returnedAt): BorrowTransaction {
            /** @var BorrowTransaction $lockedBorrow */
            $lockedBorrow = BorrowTransaction::query()
                ->whereKey($borrow->id)
                ->lockForUpdate()
                ->firstOrFail();

            $requestedLineIds = collect($returnLines)
                ->pluck('borrow_transaction_item_id')
                ->sort()
                ->values()
                ->all();

            /** @var Collection<int, BorrowTransactionItem> $lockedBorrowItems */
            $lockedBorrowItems = BorrowTransactionItem::query()
                ->where('borrow_transaction_id', $lockedBorrow->id)
                ->whereIn('id', $requestedLineIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($lockedBorrowItems->count() !== count($requestedLineIds)) {
                throw new InvalidArgumentException('One or more return lines do not belong to this borrow transaction.');
            }

            $itemIds = $lockedBorrowItems->pluck('item_id')->sort()->values()->all();

            /** @var Collection<int, InventoryItem> $lockedInventoryItems */
            $lockedInventoryItems = InventoryItem::query()
                ->whereIn('id', $itemIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($returnLines as $linePayload) {
                $borrowItem = $lockedBorrowItems->get($linePayload['borrow_transaction_item_id']);
                if (!$borrowItem) {
                    throw new InvalidArgumentException('Borrowed line item not found for this transaction.');
                }

                $oldQuantityReturned = $borrowItem->quantity_returned;
                $oldCondition = $borrowItem->item_condition_on_return;

                $remaining = $borrowItem->remainingToReturn();
                if ($linePayload['quantity_returned'] > $remaining) {
                    throw new InvalidArgumentException(
                        "Cannot return {$linePayload['quantity_returned']} units for line #{$borrowItem->id}. Remaining quantity is {$remaining}."
                    );
                }

                $item = $lockedInventoryItems->get($borrowItem->item_id);
                if (!$item) {
                    throw new InvalidArgumentException("Inventory item #{$borrowItem->item_id} not found.");
                }

                $condition = ReturnCondition::from($linePayload['item_condition_on_return']);
                if ($condition->shouldRestoreStock()) {
                    $this->quantityManagementService->increaseQuantity(
                        item: $item,
                        amount: $linePayload['quantity_returned'],
                        reason: sprintf(
                            'Returned in good condition via borrow #%d, line #%d.',
                            $lockedBorrow->id,
                            $borrowItem->id
                        ),
                        userId: $userId,
                    );
                } else {
                    $this->logNonRestockReturn(
                        borrow: $lockedBorrow,
                        borrowItem: $borrowItem,
                        condition: $condition,
                        quantityReturned: $linePayload['quantity_returned'],
                        userId: $userId,
                    );
                }

                $borrowItem->update([
                    'quantity_returned' => $borrowItem->quantity_returned + $linePayload['quantity_returned'],
                    'item_condition_on_return' => $condition->value,
                ]);

                $this->logReturnProcessed(
                    borrow: $lockedBorrow,
                    borrowItem: $borrowItem,
                    condition: $condition,
                    quantityReturned: $linePayload['quantity_returned'],
                    oldQuantityReturned: $oldQuantityReturned,
                    oldCondition: $oldCondition,
                    userId: $userId,
                );
            }

            $hasPendingItems = BorrowTransactionItem::query()
                ->where('borrow_transaction_id', $lockedBorrow->id)
                ->whereColumn('quantity_returned', '<', 'quantity_borrowed')
                ->exists();

            $status = $this->resolveStatus(
                hasPendingItems: $hasPendingItems,
                expectedReturnDate: $lockedBorrow->expected_return_date,
            );

            $lockedBorrow->update([
                'status' => $status,
                'actual_return_date' => $status === BorrowTransactionStatus::RETURNED->value
                    ? $returnedAt->toDateString()
                    : null,
            ]);

            return $lockedBorrow->load([
                'creator:id,name,email',
                'borrowTransactionItems',
                'borrowTransactionItems.inventoryItem:id,place_id,name,code,quantity,status',
                'borrowTransactionItems.inventoryItem.place:id,cupboard_id,name,code',
            ]);
        });
    }

    private function resolveStatus(bool $hasPendingItems, mixed $expectedReturnDate): string
    {
        if (!$hasPendingItems) {
            return BorrowTransactionStatus::RETURNED->value;
        }

        $expected = $expectedReturnDate instanceof CarbonImmutable
            ? $expectedReturnDate
            : CarbonImmutable::parse((string) $expectedReturnDate);

        if ($expected->isPast()) {
            return BorrowTransactionStatus::OVERDUE->value;
        }

        return BorrowTransactionStatus::ACTIVE->value;
    }

    private function logNonRestockReturn(
        BorrowTransaction $borrow,
        BorrowTransactionItem $borrowItem,
        ReturnCondition $condition,
        int $quantityReturned,
        int $userId,
    ): void {
        $action = $condition === ReturnCondition::DAMAGED
            ? ActivityAction::RETURN_DAMAGED_RECORDED
            : ActivityAction::RETURN_MISSING_RECORDED;

        ActivityLog::query()->create([
            'user_id' => $userId,
            'action' => $action->value,
            'entity_type' => BorrowTransaction::class,
            'entity_id' => $borrow->id,
            'old_values' => null,
            'new_values' => json_encode([
                'borrow_transaction_item_id' => $borrowItem->id,
                'item_id' => $borrowItem->item_id,
                'quantity_returned' => $quantityReturned,
                'condition' => $condition->value,
                'restocked' => false,
            ], JSON_THROW_ON_ERROR),
            'description' => sprintf(
                'Return recorded as %s for borrow #%d line #%d (no stock restored).',
                $condition->value,
                $borrow->id,
                $borrowItem->id
            ),
        ]);
    }

    private function logReturnProcessed(
        BorrowTransaction $borrow,
        BorrowTransactionItem $borrowItem,
        ReturnCondition $condition,
        int $quantityReturned,
        int $oldQuantityReturned,
        ?string $oldCondition,
        int $userId,
    ): void {
        ActivityLog::query()->create([
            'user_id' => $userId,
            'action' => ActivityAction::RETURN_PROCESSED->value,
            'entity_type' => BorrowTransaction::class,
            'entity_id' => $borrow->id,
            'old_values' => [
                'borrow_transaction_item_id' => $borrowItem->id,
                'item_id' => $borrowItem->item_id,
                'quantity_returned_total' => $oldQuantityReturned,
                'condition' => $oldCondition,
            ],
            'new_values' => [
                'borrow_transaction_item_id' => $borrowItem->id,
                'item_id' => $borrowItem->item_id,
                'quantity_returned' => $quantityReturned,
                'condition' => $condition->value,
                'restocked' => $condition->shouldRestoreStock(),
                'quantity_returned_total' => $borrowItem->quantity_returned,
            ],
            'description' => sprintf(
                'Processed return for borrow #%d line #%d.',
                $borrow->id,
                $borrowItem->id
            ),
        ]);
    }
}
