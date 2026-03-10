<?php

namespace App\Http\Requests\Places;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlaceRequest extends FormRequest
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
            'cupboard_id' => ['required', 'integer', 'exists:cupboards,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('places', 'code')->where(
                    fn ($query) => $query->where('cupboard_id', $this->integer('cupboard_id'))
                ),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
