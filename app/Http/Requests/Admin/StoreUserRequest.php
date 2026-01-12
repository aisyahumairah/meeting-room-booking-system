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
            'staff_number' => [
                'required',
                'string',
                'max:20',
                'unique:users,staff_number',
                'regex:/^[A-Za-z0-9-]+$/', // Alphanumeric + dash only
            ],
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'email' => [
                'required',
                'email:rfc', // RFC validation only (DNS check fails in test environments)
                'max:255',
                'unique:users,email',
            ],
            'department' => [
                'nullable',
                'string',
                'max:100',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9+\-\s()]+$/', // Phone number format
            ],
            'role' => [
                'required',
                'in:regular_user,administrator,director,system_admin',
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'regex:/[a-zA-Z]/', // At least one letter
                'regex:/[0-9]/',    // At least one number
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'staff_number.required' => 'Staff number is required.',
            'staff_number.unique' => 'This staff number is already registered.',
            'staff_number.regex' => 'Staff number can only contain letters, numbers, and dashes.',
            'email.unique' => 'This email address is already registered.',
            'email.email' => 'Please enter a valid email address.',
            'phone.regex' => 'Please enter a valid phone number.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.regex' => 'Password must contain letters and numbers.',
        ];
    }
}
