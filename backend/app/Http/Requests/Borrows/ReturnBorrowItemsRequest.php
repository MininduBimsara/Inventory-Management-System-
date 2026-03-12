<?php

namespace App\Http\Requests\Borrows;

use App\Enums\ReturnCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReturnBorrowItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('borrow.return');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'returned_at' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.borrow_transaction_item_id' => ['required', 'integer', 'exists:borrow_transaction_items,id', 'distinct'],
            'items.*.quantity_returned' => ['required', 'integer', 'min:1'],
            'items.*.item_condition_on_return' => ['required', 'string', Rule::in(ReturnCondition::values())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'At least one return line is required.',
            'items.*.borrow_transaction_item_id.distinct' => 'Duplicate return lines for the same borrowed item are not allowed.',
        ];
    }
}
