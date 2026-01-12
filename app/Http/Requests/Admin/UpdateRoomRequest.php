<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->canManageRooms();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $roomId = $this->route('room')->id;

        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('rooms', 'name')->ignore($roomId),
            ],
            'capacity' => 'required|integer|min:1|max:500',
            'floor_location' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'status' => [
                'required',
                'in:active,inactive,under_maintenance',
            ],
            'amenities' => 'nullable|array',
            'amenities.*' => 'exists:amenities,id',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:5120', // 5MB
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The room name is required.',
            'name.unique' => 'A room with this name already exists.',
            'name.max' => 'The room name cannot exceed 50 characters.',
            'capacity.required' => 'The room capacity is required.',
            'capacity.min' => 'The room must have a capacity of at least 1.',
            'capacity.max' => 'The room capacity cannot exceed 500.',
            'floor_location.required' => 'The floor/location is required.',
            'floor_location.max' => 'The floor/location cannot exceed 100 characters.',
            'description.max' => 'The description cannot exceed 500 characters.',
            'images.max' => 'You can upload a maximum of 5 images.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.mimes' => 'Images must be JPEG or PNG format.',
            'images.*.max' => 'Each image cannot exceed 5MB.',
        ];
    }
}
