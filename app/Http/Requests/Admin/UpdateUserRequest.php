<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->canManageUsers();
    }

    public function rules(): array
    {
        return [
            'staff_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'staff_number')->ignore($this->user),
            ],
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user),
            ],
            'department' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(['regular_user', 'administrator', 'director', 'system_admin'])],
        ];
    }
}
