<?php

namespace App\Http\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for restoring item from Damaged status
 *
 * Business rules enforced:
 * - Item must currently be marked as Damaged
 * - Reason for restoration is required for audit trail
 */
class RestoreFromDamagedRequest extends FormRequest
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
                'min:5',
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
            'reason.required' => 'Reason for restoring item from damaged status is required.',
            'reason.min' => 'Reason must be at least 5 characters.',
            'reason.max' => 'Reason cannot exceed 500 characters.',
        ];
    }
}
