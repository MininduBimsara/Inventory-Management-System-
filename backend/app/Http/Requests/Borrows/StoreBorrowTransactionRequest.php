<?php

namespace App\Http\Requests\Borrows;

use Illuminate\Foundation\Http\FormRequest;

class StoreBorrowTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('borrow.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'borrower_name' => ['required', 'string', 'max:255'],
            'borrower_contact' => ['required', 'string', 'max:255'],
            'borrow_date' => ['required', 'date'],
            'expected_return_date' => ['required', 'date', 'after_or_equal:borrow_date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'exists:inventory_items,id', 'distinct'],
            'items.*.quantity_borrowed' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required to create a borrow transaction.',
            'items.*.item_id.distinct' => 'Each item can only appear once in the same borrow transaction.',
            'expected_return_date.after_or_equal' => 'Expected return date must be on or after borrow date.',
        ];
    }
}
