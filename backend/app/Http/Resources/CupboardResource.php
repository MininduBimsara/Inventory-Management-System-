<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Cupboard */
class CupboardResource extends JsonResource
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
            'location' => $this->location,
            'description' => $this->description,
            'places_count' => $this->whenCounted('places'),
            'places' => PlaceResource::collection($this->whenLoaded('places')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
