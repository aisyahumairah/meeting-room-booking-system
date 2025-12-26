<?php

namespace App\Http\Controllers;

use App\Models\UserNotificationPreference;
use App\Services\AuditService;
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

        // Log profile update to audit trail
        AuditService::logProfileUpdated([
            'changed_fields' => array_keys($validated)
        ]);

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
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ]);

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        // Log password change to audit trail
        AuditService::logPasswordChanged();

        return redirect()->route('profile.show')
            ->with('success', 'Password changed successfully.');
    }

    /**
     * Show notification preferences.
     */
    public function notifications()
    {
        $user = auth()->user();
        $preferences = $user->notificationPreferences ?? new UserNotificationPreference(
            UserNotificationPreference::getDefaults()
        );

        return view('profile.notifications', compact('preferences'));
    }

    /**
     * Update notification preferences.
     */
    public function updateNotifications(Request $request)
    {
        $validated = $request->validate([
            'booking_confirmed' => 'boolean',
            'booking_cancelled' => 'boolean',
            'booking_reminder' => 'boolean',
            'room_status_changed' => 'boolean',
        ]);

        $user = auth()->user();

        UserNotificationPreference::updateOrCreate(
            ['user_id' => $user->id],
            [
                'booking_confirmed' => $request->boolean('booking_confirmed'),
                'booking_cancelled' => $request->boolean('booking_cancelled'),
                'booking_reminder' => $request->boolean('booking_reminder'),
                'room_status_changed' => $request->boolean('room_status_changed'),
            ]
        );

        return back()->with('success', 'Notification preferences updated.');
    }
}
