# User Acceptance Test Cases - Error Handling

**Project:** Meeting Room Booking System (MRBS)  
**Module:** Error Handling & User Feedback  
**Document ID:** UAT-ERR-HANDLING  
**Version:** 1.0  
**Created:** 2026-01-02  
**Phase:** 5.3 - Error Handling Implementation

---

## 1. Introduction

This document outlines the test cases for verifying the error handling implementation in the MRBS application. It covers custom error pages, toast notifications, session management, and error logging functionality.

---

## 2. Test Environment Setup

### 2.1 Required Test Data

#### Users (from UserSeeder)
| Email | Password | Role | Status | Purpose |
|-------|----------|------|--------|---------|
| sysadmin@mrbs.local | password123 | System Admin | Active | Full access testing |
| admin@mrbs.local | password123 | Administrator | Active | Admin access testing |
| user@mrbs.local | password123 | Regular User | Active | Regular user testing |
| inactive@mrbs.local | password123 | Regular User | Inactive | Inactive user testing |

**Seeder Reference:** `database/seeders/UserSeeder.php`

#### Rooms (from RoomSeeder)
| Name | Capacity | Status | Purpose |
|------|----------|--------|---------|
| Conference Room A | 10 | Active | Booking testing |
| Meeting Room B | 6 | Active | Booking testing |

**Seeder Reference:** `database/seeders/RoomSeeder.php`

#### System Settings (from SystemSettingSeeder)
| Key | Value | Purpose |
|-----|-------|---------|
| session_timeout | 30 | Session timeout in minutes |
| maintenance_mode | false | Maintenance mode toggle |

**Seeder Reference:** `database/seeders/SystemSettingSeeder.php`

### 2.2 Database Setup

```bash
# Reset and seed database
php artisan migrate:fresh --seed

# Or run specific seeders
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=RoomSeeder
php artisan db:seed --class=SystemSettingSeeder
```

### 2.3 Test URLs

| Error Type | Test URL | Expected Status |
|------------|----------|-----------------|
| 404 Not Found | `/nonexistent-page` | 404 |
| 403 Forbidden | `/admin/users` (as regular user) | 403 |
| 500 Server Error | Trigger via code | 500 |
| Session Expired | Wait for timeout or clear session | 401/419 |

---

## 3. Test Cases

### 3.1 Custom Error Pages

| Test Case ID | Test Item | Preconditions | Test Steps | Expected Result | Actual Result | Status |
|:-------------|:----------|:--------------|:-----------|:----------------|:--------------|:-------|
| **TC-ERR-001** | 404 Not Found Page Display | User logged in or guest | 1. Navigate to `/nonexistent-page`<br>2. Verify page content | • Custom 404 page displayed<br>• "Page Not Found" heading shown<br>• No emojis present<br>• "Go Back" button visible<br>• Dashboard/Home button visible<br>• Error code and timestamp shown<br>• Sneat template styling applied | | |
| **TC-ERR-002** | 404 Page Navigation (Authenticated) | User logged in | 1. Navigate to invalid URL<br>2. Click "Dashboard" button | User redirected to dashboard (`/dashboard`) | | |
| **TC-ERR-003** | 404 Page Navigation (Guest) | User not logged in | 1. Navigate to invalid URL<br>2. Click "Home" button | User redirected to login page | | |
| **TC-ERR-004** | 403 Forbidden Page Display | Regular user logged in | 1. Attempt to access `/admin/users`<br>2. Verify page content | • Custom 403 page displayed<br>• "Access Denied!" heading shown<br>• No emojis present<br>• Permission message shown<br>• "Go Back" and "Dashboard" buttons visible<br>• Error code and timestamp shown | | |
| **TC-ERR-005** | 403 Page Navigation | Regular user on 403 page | 1. Click "Go Back" button | User navigated to previous page | | |
| **TC-ERR-006** | 500 Server Error Page Display | User logged in | 1. Trigger server error (via test route)<br>2. Verify page content | • Custom 500 page displayed<br>• "Something Went Wrong!" heading shown<br>• No emojis present<br>• Error reference ID displayed<br>• "Return Home" and "Try Again" buttons visible<br>• Error code and timestamp shown | | |
| **TC-ERR-007** | 500 Error Reference ID | User on 500 page | 1. Note the reference ID<br>2. Check application logs | • Reference ID is a valid UUID<br>• Error logged with same reference ID<br>• Log contains user context (user_id, URL, IP) | | |
| **TC-ERR-008** | Error Page Responsiveness | Any error page displayed | 1. Resize browser to mobile width (<768px)<br>2. Verify layout | • Page remains readable<br>• Buttons stack vertically<br>• Image scales appropriately<br>• No horizontal scrolling | | |
| **TC-ERR-009** | Guest Layout Consistency | Access error page as guest | 1. Navigate to 404 page<br>2. Verify layout | • Guest layout applied<br>• Sneat template styling consistent<br>• No sidebar/navbar shown<br>• Proper fonts and icons loaded | | |

### 3.2 Toast Notifications

| Test Case ID | Test Item | Preconditions | Test Steps | Expected Result | Actual Result | Status |
|:-------------|:----------|:--------------|:-----------|:----------------|:--------------|:-------|
| **TC-ERR-010** | Success Toast Display | User logged in | 1. Create a new booking<br>2. Observe notification | • Green success toast appears top-right<br>• Check icon displayed<br>• "Success" label shown<br>• Success message displayed<br>• Auto-dismisses after 5 seconds<br>• Close button functional | | |
| **TC-ERR-011** | Error Toast Display | User logged in | 1. Submit invalid form data<br>2. Observe notification | • Red error toast appears top-right<br>• Error icon displayed<br>• "Error" label shown<br>• Error message displayed<br>• Auto-dismisses after 8 seconds<br>• Close button functional | | |
| **TC-ERR-012** | Warning Toast Display | User logged in | 1. Trigger warning condition<br>2. Observe notification | • Yellow warning toast appears top-right<br>• Warning icon displayed<br>• "Warning" label shown<br>• Warning message displayed<br>• Auto-dismisses after 6 seconds<br>• Close button functional | | |
| **TC-ERR-013** | Info Toast Display | User logged in | 1. Trigger info notification<br>2. Observe notification | • Blue info toast appears top-right<br>• Info icon displayed<br>• "Info" label shown<br>• Info message displayed<br>• Auto-dismisses after 5 seconds<br>• Close button functional | | |
| **TC-ERR-014** | Toast Component Reusability | User logged in | 1. Navigate to different pages<br>2. Trigger various actions with notifications | • Toast component works consistently across all pages<br>• Multiple toasts can stack<br>• No JavaScript errors in console | | |
| **TC-ERR-015** | Toast Manual Dismissal | User logged in | 1. Trigger any toast notification<br>2. Click close button before auto-dismiss | Toast immediately disappears | | |

### 3.3 Session Management

| Test Case ID | Test Item | Preconditions | Test Steps | Expected Result | Actual Result | Status |
|:-------------|:----------|:--------------|:-----------|:----------------|:--------------|:-------|
| **TC-ERR-016** | Session Expired Modal Display | User logged in | 1. Wait for session timeout (30 min) OR clear session manually<br>2. Make AJAX request | • Session expired modal appears<br>• Modal has warning background<br>• "Session Expired" heading shown<br>• No emojis present<br>• Lock icon displayed<br>• Explanation message shown<br>• "Log In Again" button visible<br>• Modal cannot be dismissed (static backdrop) | | |
| **TC-ERR-017** | Session Expired Modal on 401 | User logged in | 1. Simulate 401 AJAX response<br>2. Observe modal | Modal appears automatically | | |
| **TC-ERR-018** | Session Expired Modal on 419 | User logged in | 1. Simulate 419 AJAX response (CSRF token mismatch)<br>2. Observe modal | Modal appears automatically | | |
| **TC-ERR-019** | Session Expired Modal Navigation | Session expired modal shown | 1. Click "Log In Again" button | User redirected to login page (`/login`) | | |
| **TC-ERR-020** | Session Expired Modal Integration | User logged in | 1. Navigate to various pages<br>2. Verify modal inclusion | • Modal included in main layout<br>• Modal works on all authenticated pages<br>• No duplicate modals rendered | | |

### 3.4 Exception Handling & Logging

| Test Case ID | Test Item | Preconditions | Test Steps | Expected Result | Actual Result | Status |
|:-------------|:----------|:--------------|:-----------|:----------------|:--------------|:-------|
| **TC-ERR-021** | Server Error Logging | User logged in | 1. Trigger server error<br>2. Check `storage/logs/laravel.log` | • Error logged with "Server Error" message<br>• Log includes: message, file, line, user_id, URL, IP<br>• Timestamp recorded | | |
| **TC-ERR-022** | Query Exception Logging | User logged in | 1. Trigger database error<br>2. Check logs | • QueryException logged<br>• Database error details captured<br>• User context included | | |
| **TC-ERR-023** | AJAX Validation Error Response | User logged in | 1. Submit invalid data via AJAX<br>2. Inspect response | • HTTP 422 status returned<br>• JSON response format:<br>`{"message": "Validation failed", "errors": {...}}`<br>• Errors object contains field-specific messages | | |
| **TC-ERR-024** | AJAX Authentication Error Response | Session expired | 1. Make AJAX request with expired session<br>2. Inspect response | • HTTP 401 status returned<br>• JSON response: `{"message": "Session expired"}` | | |
| **TC-ERR-025** | AJAX Not Found Error Response | User logged in | 1. Make AJAX request to invalid endpoint<br>2. Inspect response | • HTTP 404 status returned<br>• JSON response: `{"message": "Resource not found"}` | | |
| **TC-ERR-026** | AJAX Access Denied Error Response | Regular user logged in | 1. Make AJAX request to admin endpoint<br>2. Inspect response | • HTTP 403 status returned<br>• JSON response: `{"message": "Access denied"}` | | |
| **TC-ERR-027** | ErrorService::logError() | Developer testing | 1. Call `ErrorService::logError($exception)`<br>2. Check logs and return value | • Returns valid UUID string<br>• Error logged with error_id<br>• All context fields populated<br>• Trace included | | |
| **TC-ERR-028** | ErrorService::logValidationError() | Developer testing | 1. Call `ErrorService::logValidationError($errors, 'FormName')`<br>2. Check logs | • Warning level log created<br>• Form name included<br>• Errors array logged<br>• Password fields excluded from input log | | |

### 3.5 Error Page Assets & Styling

| Test Case ID | Test Item | Preconditions | Test Steps | Expected Result | Actual Result | Status |
|:-------------|:----------|:--------------|:-----------|:----------------|:--------------|:-------|
| **TC-ERR-029** | Error Page Images | Access any error page | 1. Verify image loads<br>2. Check browser console | • Error illustration image loads successfully<br>• Image path: `/assets/img/illustrations/page-misc-error-light.png`<br>• No 404 errors for image | | |
| **TC-ERR-030** | Error Page Icons | Access any error page | 1. Verify Boxicons load<br>2. Check button icons | • All icons render correctly<br>• Boxicons CSS loaded<br>• Icons: bx-arrow-back, bx-home, bx-log-in, bx-refresh, etc. | | |
| **TC-ERR-031** | Error Page Fonts | Access any error page | 1. Verify font rendering<br>2. Check network tab | • Public Sans font loaded<br>• Typography consistent with Sneat template<br>• No font loading errors | | |
| **TC-ERR-032** | Error Page CSS | Access any error page | 1. Inspect element styles<br>2. Verify classes | • Bootstrap 5 classes applied<br>• Sneat core CSS loaded<br>• Theme colors consistent<br>• Layout centered properly | | |

---

## 4. Test Data Preparation

### 4.1 Create Test Route for 500 Error (Temporary)

Add to `routes/web.php` for testing purposes:

```php
// TEMPORARY: Remove after testing
Route::get('/test-500-error', function () {
    throw new \Exception('Test server error for error page verification');
})->name('test.500');
```

### 4.2 Simulate Session Timeout

**Option 1: Manual Session Clear**
```bash
# In browser console
document.cookie.split(";").forEach(function(c) { 
    document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
});
```

**Option 2: Adjust Session Timeout**
```php
// In config/session.php (for testing only)
'lifetime' => 1, // 1 minute instead of 120
```

### 4.3 Simulate AJAX Errors

**JavaScript Console Commands:**
```javascript
// Test 401 response
$.ajax({
    url: '/api/test-401',
    method: 'GET',
    error: function(xhr) {
        console.log('401 error triggered');
    }
});

// Test 419 CSRF error
$.ajax({
    url: '/any-post-route',
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': 'invalid-token' },
    error: function(xhr) {
        console.log('419 error triggered');
    }
});
```

---

## 5. Acceptance Criteria Verification

| Criteria | Test Case(s) | Status |
|:---------|:-------------|:-------|
| 403 error page created with Sneat styling (no emojis) | TC-ERR-004, TC-ERR-005 | |
| 404 error page created with Sneat styling (no emojis) | TC-ERR-001, TC-ERR-002, TC-ERR-003 | |
| 500 error page created with Sneat styling and error reference (no emojis) | TC-ERR-006, TC-ERR-007 | |
| Guest layout exists for error pages | TC-ERR-009 | |
| Toast notifications work for success/error/warning/info | TC-ERR-010 to TC-ERR-015 | |
| Session expired modal shows on AJAX 401/419 | TC-ERR-016 to TC-ERR-020 | |
| Error pages have navigation back to dashboard/home | TC-ERR-002, TC-ERR-003, TC-ERR-005 | |
| Error pages are responsive (mobile-friendly) | TC-ERR-008 | |
| Server errors are logged with context and reference ID | TC-ERR-021, TC-ERR-022, TC-ERR-027 | |
| Technical details never exposed to users | TC-ERR-006, TC-ERR-023 to TC-ERR-026 | |
| Reusable components utilized across all views | TC-ERR-014, TC-ERR-020 | |

---

## 6. Test Execution Notes

### 6.1 Prerequisites
- [ ] Database seeded with test data
- [ ] Application running on local server
- [ ] Browser developer tools available
- [ ] Access to application logs

### 6.2 Test Sequence
1. Execute Section 3.1 (Custom Error Pages) first
2. Execute Section 3.2 (Toast Notifications)
3. Execute Section 3.3 (Session Management)
4. Execute Section 3.4 (Exception Handling)
5. Execute Section 3.5 (Assets & Styling)

### 6.3 Pass/Fail Criteria
- **Pass:** All expected results match actual results
- **Fail:** Any deviation from expected results
- **Blocked:** Cannot execute due to dependency failure

---

## 7. Defect Tracking

| Defect ID | Test Case | Description | Severity | Status |
|:----------|:----------|:------------|:---------|:-------|
| | | | | |

---

## 8. Sign-off

| Role | Name | Signature | Date |
|:-----|:-----|:----------|:-----|
| Test Lead | | | |
| Developer | | | |
| Product Owner | | | |

---

## 9. References

- **Step Document:** `docs/workflow/phase5/step-5.3-error-handling.md`
- **Requirements:** `docs/requirement/requirements-document.md` - Section 8.3.2
- **Seeders:** `database/seeders/`
- **Migrations:** `database/migrations/`
- **Error Pages:** `resources/views/errors/`
- **Components:** `resources/views/components/`
- **Exception Handler:** `bootstrap/app.php`
- **Error Service:** `app/Services/ErrorService.php`
