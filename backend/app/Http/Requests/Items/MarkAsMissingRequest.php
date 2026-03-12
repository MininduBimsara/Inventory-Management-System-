<?php

namespace App\Http\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for marking item as Missing
 *
 * Business rules enforced:
 * - Only manual action, explicit staff decision
 * - Reason is required and must be meaningful
 * - User must have permission to update item status
 */
class MarkAsMissingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('status.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Reason for marking item as missing is required.',
            'reason.min' => 'Please provide a detailed reason (at least 10 characters).',
            'reason.max' => 'Reason cannot exceed 1000 characters.',
        ];
    }
}
