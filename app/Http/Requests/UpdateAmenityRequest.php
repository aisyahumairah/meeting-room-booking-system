<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAmenityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['administrator', 'director']);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('amenities', 'name')->ignore($this->amenity)
            ],
            'icon' => [
                'required',
                'string',
                'max:50',
                'regex:/^(fa|bx)-[a-z0-9-]+$/', // FontAwesome or Boxicons format
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Amenity name is required.',
            'name.unique' => 'An amenity with this name already exists.',
            'icon.required' => 'Please select an icon for this amenity.',
            'icon.regex' => 'Please select a valid FontAwesome icon.',
        ];
    }
}
