<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\BorrowTransactionItem */
class BorrowTransactionItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'borrow_transaction_id' => $this->borrow_transaction_id,
            'item_id' => $this->item_id,
            'quantity_borrowed' => $this->quantity_borrowed,
            'quantity_returned' => $this->quantity_returned,
            'quantity_pending' => $this->remainingToReturn(),
            'item_condition_on_return' => $this->item_condition_on_return,
            'is_fully_returned' => $this->isFullyReturned(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'item' => $this->whenLoaded('inventoryItem', function (): array {
                return [
                    'id' => $this->inventoryItem->id,
                    'name' => $this->inventoryItem->name,
                    'code' => $this->inventoryItem->code,
                    'quantity' => $this->inventoryItem->quantity,
                    'status' => $this->inventoryItem->status,
                    'place_id' => $this->inventoryItem->place_id,
                ];
            }),
        ];
    }
}
