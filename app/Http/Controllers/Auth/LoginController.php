<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle a login request.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Check if user exists and is active
        $user = User::where('email', $credentials['email'])->first();

        if ($user && $user->status === 'inactive') {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Your account has been deactivated. Please contact an administrator.',
                ]);
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Update last login timestamp
            Auth::user()->update([
                'last_login_at' => now(),
            ]);

            // Check if user must change password
            if (Auth::user()->must_change_password) {
                return redirect()->route('password.change');
            }

            // Redirect based on role
            return $this->redirectByRole(Auth::user());
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors([
                'email' => 'Invalid email or password.',
            ]);
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been logged out successfully.');
    }

    /**
     * Redirect user based on their role.
     */
    protected function redirectByRole(User $user): RedirectResponse
    {
        // Administrator and Director go to admin dashboard
        if ($user->isAdmin() || $user->isDirector()) {
            return redirect()->intended(route('dashboard.admin'));
        }

        // System Admin and Regular User go to user dashboard
        return redirect()->intended(route('dashboard.user'));
    }
}
