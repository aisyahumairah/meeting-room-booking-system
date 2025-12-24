<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->canManageUsers();
    }

    public function rules(): array
    {
        return [
            'staff_number' => ['required', 'string', 'max:20', 'unique:users,staff_number'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'department' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(['regular_user', 'administrator', 'director', 'system_admin'])],
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'staff_number.unique' => 'This staff number is already registered.',
            'email.unique' => 'This email address is already registered.',
        ];
    }
}
