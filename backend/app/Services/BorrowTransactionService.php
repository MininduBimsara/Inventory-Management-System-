<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\BorrowTransactionStatus;
use App\Models\ActivityLog;
use App\Models\BorrowTransaction;
use App\Models\BorrowTransactionItem;
use App\Models\InventoryItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BorrowTransactionService
{
    public function __construct(
        private readonly QuantityManagementService $quantityManagementService,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createBorrowTransaction(array $payload, int $userId): BorrowTransaction
    {
        /** @var array<int, array{item_id:int, quantity_borrowed:int}> $itemsPayload */
        $itemsPayload = collect($payload['items'])
            ->map(fn (array $line): array => [
                'item_id' => (int) $line['item_id'],
                'quantity_borrowed' => (int) $line['quantity_borrowed'],
            ])
            ->values()
            ->all();

        return DB::transaction(function () use ($payload, $itemsPayload, $userId): BorrowTransaction {
            $itemIds = collect($itemsPayload)
                ->pluck('item_id')
                ->sort()
                ->values()
                ->all();

            /** @var Collection<int, InventoryItem> $lockedItems */
            $lockedItems = InventoryItem::query()
                ->whereIn('id', $itemIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($itemsPayload as $line) {
                $item = $lockedItems->get($line['item_id']);
                if (!$item) {
                    throw new InvalidArgumentException("Item #{$line['item_id']} does not exist.");
                }

                if (!$item->place_id) {
                    throw new InvalidArgumentException(
                        "Item {$item->code} is not assigned to a valid place and cannot be borrowed."
                    );
                }

                if ($line['quantity_borrowed'] <= 0) {
                    throw new InvalidArgumentException(
                        "Borrow quantity for item {$item->code} must be greater than zero."
                    );
                }

                if ($line['quantity_borrowed'] > $item->quantity) {
                    throw new InvalidArgumentException(
                        "Cannot borrow {$line['quantity_borrowed']} of item {$item->code}. Available quantity is {$item->quantity}."
                    );
                }
            }

            $borrow = BorrowTransaction::query()->create([
                'borrower_name' => $payload['borrower_name'],
                'borrower_contact' => $payload['borrower_contact'],
                'borrow_date' => CarbonImmutable::parse((string) $payload['borrow_date'])->toDateString(),
                'expected_return_date' => CarbonImmutable::parse((string) $payload['expected_return_date'])->toDateString(),
                'status' => BorrowTransactionStatus::ACTIVE->value,
                'created_by' => $userId,
            ]);

            foreach ($itemsPayload as $line) {
                $item = $lockedItems->get($line['item_id']);
                if (!$item) {
                    throw new InvalidArgumentException("Item #{$line['item_id']} does not exist.");
                }

                $this->quantityManagementService->decreaseQuantity(
                    item: $item,
                    amount: $line['quantity_borrowed'],
                    reason: sprintf(
                        'Borrowed via transaction #%d by %s.',
                        $borrow->id,
                        $payload['borrower_name']
                    ),
                    userId: $userId,
                );

                BorrowTransactionItem::query()->create([
                    'borrow_transaction_id' => $borrow->id,
                    'item_id' => $item->id,
                    'quantity_borrowed' => $line['quantity_borrowed'],
                    'quantity_returned' => 0,
                    'item_condition_on_return' => null,
                ]);
            }

            $this->logBorrowCreated($borrow, $itemsPayload, $userId);

            return $borrow->load([
                'creator:id,name,email',
                'borrowTransactionItems',
                'borrowTransactionItems.inventoryItem:id,place_id,name,code,quantity,status',
                'borrowTransactionItems.inventoryItem.place:id,cupboard_id,name,code',
            ]);
        });
    }

    public function refreshOverdueTransactions(): int
    {
        return BorrowTransaction::query()
            ->where('status', BorrowTransactionStatus::ACTIVE->value)
            ->whereDate('expected_return_date', '<', now()->toDateString())
            ->update(['status' => BorrowTransactionStatus::OVERDUE->value]);
    }

    /**
     * @param array<int, array{item_id:int, quantity_borrowed:int}> $itemsPayload
     */
    private function logBorrowCreated(BorrowTransaction $borrow, array $itemsPayload, int $userId): void
    {
        ActivityLog::query()->create([
            'user_id' => $userId,
            'action' => ActivityAction::BORROW_CREATED->value,
            'entity_type' => BorrowTransaction::class,
            'entity_id' => $borrow->id,
            'old_values' => null,
            'new_values' => json_encode([
                'borrow_id' => $borrow->id,
                'status' => $borrow->status,
                'items' => $itemsPayload,
            ], JSON_THROW_ON_ERROR),
            'description' => sprintf('Borrow transaction #%d created.', $borrow->id),
        ]);
    }
}
