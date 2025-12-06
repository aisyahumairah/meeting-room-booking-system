<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    public function showForm(): View|RedirectResponse
    {
        if (!Auth::user()->must_change_password) {
            return redirect()->route('dashboard.user')
                ->with('info', 'You do not need to change your password.');
        }
        return view('auth.change-password');
    }

    public function change(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required'],
            'password' => [
                'required',
                'confirmed',
                'min:8',
                'regex:/[a-zA-Z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
            ],
        ], [
            'password.regex' => 'Password must contain letters, numbers, and special characters.',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        if (Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'New password must be different from current.']);
        }

        $user->update([
            'password' => $request->password,
            'must_change_password' => false,
        ]);

        if ($user->isAdmin() || $user->isDirector()) {
            return redirect()->route('dashboard.admin')->with('success', 'Password changed successfully!');
        }
        return redirect()->route('dashboard.user')->with('success', 'Password changed successfully!');
    }
}
