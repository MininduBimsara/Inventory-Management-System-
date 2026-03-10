<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Place */
class PlaceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cupboard_id' => $this->cupboard_id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'cupboard' => $this->whenLoaded('cupboard', function (): array {
                return [
                    'id' => $this->cupboard->id,
                    'name' => $this->cupboard->name,
                    'code' => $this->cupboard->code,
                ];
            }),
        ];
    }
}
