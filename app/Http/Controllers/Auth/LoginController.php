<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
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

        // Check if user exists
        $user = User::where('email', $credentials['email'])->first();

        // Check if account is locked
        if ($user && $user->isLocked()) {
            AuditService::logLogin(false, $credentials['email']);
            $remainingMinutes = $user->getRemainingLockoutMinutes();
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => "Your account has been locked due to too many failed login attempts. Please try again in {$remainingMinutes} minutes or contact an administrator.",
                ]);
        }

        // Check if user is inactive
        if ($user && $user->status === 'inactive') {
            AuditService::logLogin(false, $credentials['email']);
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Your account has been deactivated. Please contact an administrator.',
                ]);
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Reset failed login attempts on successful login
            $user->resetLoginAttempts();

            // Update last login timestamp
            Auth::user()->update([
                'last_login_at' => now(),
            ]);

            // Log successful login
            AuditService::logLogin(true);

            // Check if user must change password
            if (Auth::user()->must_change_password) {
                return redirect()->route('password.change');
            }

            // Redirect based on role
            return $this->redirectByRole(Auth::user());
        }

        // Log failed login attempt
        AuditService::logLogin(false, $credentials['email']);

        // Track failed login attempts if user exists
        if ($user) {
            $user->incrementLoginAttempts();

            // Check if we need to lock the account
            $limit = \App\Models\SystemSetting::get('login_attempt_limit', 5);
            if ($user->failed_login_attempts >= $limit) {
                $user->lockAccount();
                $lockoutDuration = \App\Models\SystemSetting::get('lockout_duration', 15);

                return back()
                    ->withInput($request->only('email'))
                    ->withErrors([
                        'email' => "Your account has been locked due to too many failed login attempts. Please try again in {$lockoutDuration} minutes or contact an administrator.",
                    ]);
            }

            $remainingAttempts = $limit - $user->failed_login_attempts;
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => "Invalid email or password. You have {$remainingAttempts} attempt(s) remaining.",
                ]);
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
        // Log logout before clearing session
        AuditService::logLogout();

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
