<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\BorrowTransaction */
class BorrowTransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'borrower_name' => $this->borrower_name,
            'borrower_contact' => $this->borrower_contact,
            'borrow_date' => $this->borrow_date,
            'expected_return_date' => $this->expected_return_date,
            'actual_return_date' => $this->actual_return_date,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'creator' => $this->whenLoaded('creator', function (): array {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            'items' => BorrowTransactionItemResource::collection($this->whenLoaded('borrowTransactionItems')),
            'summary' => [
                'total_lines' => $this->borrowTransactionItems->count(),
                'total_quantity_borrowed' => $this->borrowTransactionItems->sum('quantity_borrowed'),
                'total_quantity_returned' => $this->borrowTransactionItems->sum('quantity_returned'),
                'total_quantity_pending' => $this->borrowTransactionItems->sum(
                    fn ($line): int => $line->remainingToReturn()
                ),
                'is_fully_returned' => $this->borrowTransactionItems->every(
                    fn ($line): bool => $line->isFullyReturned()
                ),
            ],
        ];
    }
}
