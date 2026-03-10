<?php

namespace App\Http\Requests\Places;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlaceRequest extends FormRequest
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
        $place = $this->route('place');
        $cupboardId = $this->integer('cupboard_id') ?: $place?->cupboard_id;

        return [
            'cupboard_id' => ['required', 'integer', 'exists:cupboards,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('places', 'code')
                    ->where(fn ($query) => $query->where('cupboard_id', $cupboardId))
                    ->ignore($place?->id),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
