<?php

namespace App\Http\Requests\Items;

use App\Models\InventoryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var \App\Models\InventoryItem|null $item */
        $item = $this->route('item');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('inventory_items', 'code')->ignore($item?->id),
            ],
            'quantity' => ['required', 'integer', 'min:0'],
            'serial_number' => [
                'nullable',
                'string',
                'max:150',
                Rule::unique('inventory_items', 'serial_number')->ignore($item?->id),
            ],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'description' => ['nullable', 'string', 'max:2000'],
            'place_id' => ['required', 'integer', 'exists:places,id'],
            'status' => ['required', 'string', Rule::in(InventoryItem::ALLOWED_STATUSES)],
        ];
    }
}