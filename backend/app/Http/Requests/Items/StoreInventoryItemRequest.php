<?php

namespace App\Http\Requests\Items;

use App\Models\InventoryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryItemRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', Rule::unique('inventory_items', 'code')],
            'quantity' => ['required', 'integer', 'min:0'],
            'serial_number' => ['nullable', 'string', 'max:150', Rule::unique('inventory_items', 'serial_number')],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'description' => ['nullable', 'string', 'max:2000'],
            'place_id' => ['required', 'integer', 'exists:places,id'],
            'status' => ['required', 'string', Rule::in(InventoryItem::ALLOWED_STATUSES)],
        ];
    }
}
