<?php

namespace App\Http\Resources;

use App\Enums\InventoryItemStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\InventoryItem */
class InventoryItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'quantity' => $this->quantity,
            'serial_number' => $this->serial_number,
            'image_path' => $this->image_path,
            'description' => $this->description,
            'place_id' => $this->place_id,
            'status' => $this->status,
            'manual_status_reason' => $this->manual_status_reason,
            'status_info' => [
                'value' => $this->status,
                'is_automatic' => $this->hasAutomaticStatus(),
                'is_manual' => $this->hasManualStatus(),
                'is_available' => $this->isAvailable(),
                'is_in_stock' => $this->isInStock(),
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'place' => $this->whenLoaded('place', function (): array {
                $place = $this->place;

                return [
                    'id' => $place->id,
                    'name' => $place->name,
                    'code' => $place->code,
                    'cupboard_id' => $place->cupboard_id,
                    'cupboard' => $place->relationLoaded('cupboard') && $place->cupboard
                        ? [
                            'id' => $place->cupboard->id,
                            'name' => $place->cupboard->name,
                            'code' => $place->cupboard->code,
                        ]
                        : null,
                ];
            }),
        ];
    }
}
