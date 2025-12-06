# Step 1.2: Authentication System

**Priority:** CRITICAL | **Ref:** §4.2.2, §4.2.3 | **Dependencies:** Step 1.1

---

## Objective
Implement login, logout, password reset, and first-login password change.

---

## Task 1.2.1: Copy Mockup Assets

```bash
xcopy /E /I mrbs-mock-up\assets public\assets
copy mrbs-mock-up\styles\index.css public\assets\css\custom.css
```

---

## Task 1.2.2: Create Auth Layout

**File:** `resources/views/layouts/auth.blade.php`

**Mockup Reference:** `mrbs-mock-up/pages/auth-login.html`

Create a minimal layout with:
- Meta tags, CSRF token
- CSS: core.css, theme-default.css, page-auth.css, custom.css
- JS: jQuery, Bootstrap
- `@yield('content')` block

---

## Task 1.2.3: Create Login Controller

**Command:** `php artisan make:controller Auth/LoginController`

**File:** `app/Http/Controllers/Auth/LoginController.php`

Key methods:
- `showLoginForm()` - return login view
- `login(Request $request)` - validate, check active status, authenticate, update `last_login_at`, redirect by role
- `logout(Request $request)` - logout, invalidate session

**Role-based redirect:**
- Administrator/Director → `dashboard.admin`
- System Admin/Regular User → `dashboard.user`

**Validation:**
- Check if user status is `inactive` → show deactivated message
- Generic error: "Invalid email or password"

---

## Task 1.2.4: Create Login View

**File:** `resources/views/auth/login.blade.php`

**Mockup Reference:** `mrbs-mock-up/pages/auth-login.html`

Elements:
- Logo/brand
- Email input with validation error display
- Password input with show/hide toggle
- "Forgot Password?" link
- Submit button
- Session status messages

---

## Task 1.2.5: Create Forgot Password Flow

**Controller:** `app/Http/Controllers/Auth/ForgotPasswordController.php`

**View:** `resources/views/auth/forgot-password.blade.php`

**Mockup Reference:** `mrbs-mock-up/pages/auth-forgot-password.html`

Methods:
- `showForm()` - display email input form
- `sendResetLink(Request $request)` - validate email, generate token (30 min expiry), send email

---

## Task 1.2.6: Create Reset Password Flow

**Controller:** `app/Http/Controllers/Auth/ResetPasswordController.php`

**View:** `resources/views/auth/reset-password.blade.php`

Methods:
- `showForm($token)` - show new password form
- `reset(Request $request)` - validate token, update password, redirect to login

Password requirements: 8+ chars, letters, numbers, symbols

---

## Task 1.2.7: Create First-Login Password Change

**Controller:** `app/Http/Controllers/Auth/ChangePasswordController.php`

**View:** `resources/views/auth/change-password.blade.php`

For users with `must_change_password = true`:
- Force redirect after login
- Require current password verification
- Set new password, update `must_change_password = false`

---

## Task 1.2.8: Define Routes

**File:** `routes/web.php`

```php
// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
    Route::get('forgot-password', [ForgotPasswordController::class, 'showForm'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');
    Route::get('reset-password/{token}', [ResetPasswordController::class, 'showForm'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

// Auth routes
Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('password/change', [ChangePasswordController::class, 'showForm'])->name('password.change');
    Route::post('password/change', [ChangePasswordController::class, 'change']);
});
```

---

## Testing Requirements

**File:** `tests/Feature/Auth/LoginTest.php`

Test cases:
- Guest can view login page
- User can login with correct credentials
- User cannot login with incorrect password
- Inactive user cannot login
- User is redirected based on role
- User with must_change_password is redirected to change password
- User can logout

```bash
php artisan test --filter=LoginTest
```

---

## Acceptance Criteria
- [ ] Assets copied to `public/assets/`
- [ ] Login page displays at `/login`
- [ ] Successful login redirects by role
- [ ] Inactive users see error message
- [ ] Logout works and redirects to login
- [ ] Forgot password sends reset email
- [ ] Reset password with valid token works
- [ ] First-login password change enforced
- [ ] All feature tests pass

---

**Next:** [Step 1.3 - Authorization](./step-1.3-authorization.md)
