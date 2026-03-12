<?php

namespace App\Http\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for decreasing item quantity
 *
 * Business rules enforced:
 * - Amount must be a positive integer
 * - Amount must not exceed current quantity (validated in service)
 * - Reason/note is required for audit trail
 */
class DecreaseQuantityRequest extends FormRequest
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
            'amount.required' => 'Amount to decrease is required.',
            'amount.integer' => 'Amount must be a whole number.',
            'amount.min' => 'Amount must be at least 1.',
            'reason.required' => 'Reason for decreasing quantity is required.',
            'reason.min' => 'Reason must be at least 3 characters.',
            'reason.max' => 'Reason cannot exceed 500 characters.',
        ];
    }
}
