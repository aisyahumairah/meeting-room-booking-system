<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Display the user's profile.
     */
    public function show()
    {
        return view('profile.show', ['user' => Auth::user()]);
    }

    /**
     * Show the form for editing the user's profile.
     */
    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    /**
     * Update the user's profile.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        Auth::user()->update($validated);

        // TODO: Log profile update to audit trail (Step 1.7)

        return redirect()->route('profile.show')
            ->with('success', 'Profile updated successfully.');
    }

    /**
     * Show the change password form.
     */
    public function showChangePasswordForm()
    {
        return view('profile.change-password');
    }

    /**
     * Change the user's password.
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->letters()->numbers()->symbols(),
            ],
        ]);

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        // TODO: Log password change to audit trail (Step 1.7)

        return redirect()->route('profile.show')
            ->with('success', 'Password changed successfully.');
    }
}
