<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    /**
     * Show the forgot password form.
     */
    public function showForm(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a password reset link.
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        // Always show success message to prevent email enumeration
        if (!$user) {
            return back()->with('status', 'If your email exists in our system, you will receive a password reset link shortly.');
        }

        // Check if user is active
        if ($user->status === 'inactive') {
            return back()->with('status', 'If your email exists in our system, you will receive a password reset link shortly.');
        }

        // Generate token
        $token = Str::random(64);

        // Delete any existing tokens for this email
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Insert new token (expires in 30 minutes)
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        // Build reset URL
        $resetUrl = route('password.reset', ['token' => $token, 'email' => $request->email]);

        // Send email
        try {
            Mail::send('emails.password-reset', ['resetUrl' => $resetUrl, 'user' => $user], function ($message) use ($user) {
                $message->to($user->email, $user->name)
                    ->subject('Password Reset Request - MRBS');
            });
        } catch (\Exception $e) {
            // Log the error but still show success message to prevent enumeration
            logger()->error('Failed to send password reset email: ' . $e->getMessage());
        }

        // Log password reset request
        AuditService::logPasswordResetRequested($request->email);

        return back()->with('status', 'If your email exists in our system, you will receive a password reset link shortly.');
    }
}
