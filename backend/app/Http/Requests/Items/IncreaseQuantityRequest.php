<?php

namespace App\Http\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for increasing item quantity
 *
 * Business rules enforced:
 * - Amount must be a positive integer
 * - Reason/note is required for audit trail
 * - Item must exist (handled by route model binding)
 */
class IncreaseQuantityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('item.adjust-quantity');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'integer',
                'min:1',
            ],
            'reason' => [
                'required',
                'string',
                'min:3',
                'max:500',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Amount to increase is required.',
            'amount.integer' => 'Amount must be a whole number.',
            'amount.min' => 'Amount must be at least 1.',
            'reason.required' => 'Reason for increasing quantity is required.',
            'reason.min' => 'Reason must be at least 3 characters.',
            'reason.max' => 'Reason cannot exceed 500 characters.',
        ];
    }
}
