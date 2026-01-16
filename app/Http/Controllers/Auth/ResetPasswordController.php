<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    /**
     * Show the reset password form.
     */
    public function showForm(Request $request, string $token): View|RedirectResponse
    {
        $email = $request->query('email');

        // Verify token exists and is not expired (30 minutes)
        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$record) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Invalid password reset link.']);
        }

        // Check if token is expired based on system setting
        $expiry = SystemSetting::get('password_reset_expiry', 30);
        if (now()->diffInMinutes($record->created_at) > $expiry) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return redirect()->route('password.request')
                ->withErrors(['email' => 'This password reset link has expired. Please request a new one.']);
        }

        // Verify token hash
        if (!Hash::check($token, $record->token)) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Invalid password reset link.']);
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Reset the user's password.
     */
    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'confirmed',
                'min:8',
                'regex:/[a-zA-Z]/',      // at least one letter
                'regex:/[0-9]/',          // at least one number
                'regex:/[@$!%*#?&]/',     // at least one symbol
            ],
        ], [
            'password.regex' => 'Password must contain at least one letter, one number, and one special character (@$!%*#?&).',
        ]);

        // Get the reset record
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return back()->withErrors(['email' => 'Invalid password reset request.']);
        }

        // Check if token is expired based on system setting
        $expiry = SystemSetting::get('password_reset_expiry', 30);
        if (now()->diffInMinutes($record->created_at) > $expiry) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return redirect()->route('password.request')
                ->withErrors(['email' => 'This password reset link has expired. Please request a new one.']);
        }

        // Verify token
        if (!Hash::check($request->token, $record->token)) {
            return back()->withErrors(['email' => 'Invalid password reset token.']);
        }

        // Find user and update password
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'User not found.']);
        }

        $user->update([
            'password' => $request->password, // Will be hashed by the model cast
            'must_change_password' => false,
        ]);

        // Delete the reset token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Log password reset completion
        AuditService::logPasswordResetCompleted($user->id);

        return redirect()->route('login')
            ->with('status', 'Your password has been reset successfully. Please login with your new password.');
    }
}
