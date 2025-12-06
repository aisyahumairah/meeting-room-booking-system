# Step 1.5: User Profile Management

**Priority:** MEDIUM | **Ref:** §4.2.4 | **Dependencies:** Step 1.2, Step 1.4

---

## Objective
Allow users to view and edit their profile, and change their password.

---

## Task 1.5.1: Create Profile Controller

**Command:** `php artisan make:controller ProfileController`

**File:** `app/Http/Controllers/ProfileController.php`

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show()
    {
        return view('profile.show', ['user' => Auth::user()]);
    }

    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

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

    public function showChangePasswordForm()
    {
        return view('profile.change-password');
    }

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
```

---

## Task 1.5.2: Create Profile Routes

**File:** `routes/web.php` (add to authenticated routes)

```php
Route::middleware(['auth'])->group(function () {
    Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('profile/password', [ProfileController::class, 'showChangePasswordForm'])->name('profile.password');
    Route::put('profile/password', [ProfileController::class, 'changePassword'])->name('profile.password.update');
});
```

---

## Task 1.5.3: Create Profile Show View

**File:** `resources/views/profile/show.blade.php`

**Mockup Reference:** `mrbs-mock-up/pages/users-edit.html` (adapt for read-only view)

Display:
- Staff Number (read-only)
- Full Name
- Email Address (read-only)
- Department
- Phone
- Role (badge: success for Admin, primary for Director, warning for SysAdmin, secondary for Regular)
- Account Created date
- Last Login date

Buttons:
- Edit Profile
- Change Password

```php
@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <h5 class="card-header">Profile Details</h5>
            <div class="card-body">
                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Staff Number</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control-plaintext" value="{{ $user->staff_number }}" readonly>
                    </div>
                </div>
                <!-- Repeat for other fields -->
                
                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Role</label>
                    <div class="col-sm-9">
                        <span class="badge bg-{{ $user->status_badge }}">{{ $user->role_display }}</span>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('profile.edit') }}" class="btn btn-primary">Edit Profile</a>
                <a href="{{ route('profile.password') }}" class="btn btn-outline-secondary">Change Password</a>
            </div>
        </div>
    </div>
</div>
@endsection
```

---

## Task 1.5.4: Create Profile Edit View

**File:** `resources/views/profile/edit.blade.php`

**Mockup Reference:** `mrbs-mock-up/pages/users-edit.html`

Editable fields:
- Name (text input)
- Department (text input)
- Phone (text input)

Read-only display:
- Staff Number
- Email
- Role

```php
@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
<div class="card">
    <h5 class="card-header">Edit Profile</h5>
    <div class="card-body">
        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Staff Number</label>
                <div class="col-sm-9">
                    <input type="text" class="form-control-plaintext" value="{{ $user->staff_number }}" readonly>
                </div>
            </div>
            
            <div class="row mb-3">
                <label for="name" class="col-sm-3 col-form-label">Full Name</label>
                <div class="col-sm-9">
                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                           id="name" name="name" value="{{ old('name', $user->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <!-- Similar for department and phone -->
            
            <div class="row">
                <div class="col-sm-9 offset-sm-3">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
```

---

## Task 1.5.5: Create Change Password View

**File:** `resources/views/profile/change-password.blade.php`

Fields:
- Current Password (required for verification)
- New Password (with strength indicator)
- Confirm New Password

Password requirements display:
- Minimum 8 characters
- At least one letter
- At least one number
- At least one symbol

---

## Testing Requirements

**File:** `tests/Feature/ProfileTest.php`

Test cases:
- User can view their profile
- User can update their name, department, phone
- User cannot update email or staff_number
- User can change password with correct current password
- User cannot change password with wrong current password
- New password must meet requirements

```bash
php artisan test --filter=ProfileTest
```

---

## Acceptance Criteria
- [ ] Profile show page displays all user info
- [ ] Edit profile allows updating name, department, phone only
- [ ] Staff number, email, role are read-only
- [ ] Change password requires current password verification
- [ ] New password must be 8+ chars with letters, numbers, symbols
- [ ] Success messages display after updates
- [ ] All tests pass

---

**Next:** [Step 1.6 - Dashboards](./step-1.6-dashboards.md)
