# Step 5.2: Data Validation Hardening

**Priority:** HIGH | **Ref:** §8.3.1 | **Dependencies:** None  
**Status:** TODO

---

## Objective

Review and enhance all form validation to ensure comprehensive client-side and server-side validation with clear, actionable error messages.

---

## Task 5.2.1: Audit Existing Form Request Classes

List all Form Request classes and verify validation rules:

```bash
# List all request classes
Get-ChildItem -Path "app/Http/Requests" -Recurse -Name
```

**Expected Files:**
```
Admin/
├── StoreUserRequest.php
├── UpdateUserRequest.php
StoreAmenityRequest.php
UpdateAmenityRequest.php
StoreBookingRequest.php
UpdateBookingRequest.php
StoreRoomRequest.php
UpdateRoomRequest.php
UpdateProfileRequest.php
```

---

## Task 5.2.2: User Management Validation

### StoreUserRequest

**File:** `app/Http/Requests/Admin/StoreUserRequest.php`

```php
public function rules(): array
{
    return [
        'staff_number' => [
            'required',
            'string',
            'max:20',
            'unique:users,staff_number',
            'regex:/^[A-Za-z0-9-]+$/', // Alphanumeric + dash only
        ],
        'name' => [
            'required',
            'string',
            'max:100',
        ],
        'email' => [
            'required',
            'email:rfc,dns', // Strict email validation
            'max:255',
            'unique:users,email',
        ],
        'department' => [
            'nullable',
            'string',
            'max:100',
        ],
        'phone' => [
            'nullable',
            'string',
            'max:20',
            'regex:/^[0-9+\-\s()]+$/', // Phone number format
        ],
        'role' => [
            'required',
            'in:regular_user,administrator,director,system_admin',
        ],
        'password' => [
            'nullable',
            'string',
            'min:8',
            'regex:/[a-zA-Z]/', // At least one letter
            'regex:/[0-9]/',    // At least one number
            'confirmed',
        ],
    ];
}

public function messages(): array
{
    return [
        'staff_number.required' => 'Staff number is required.',
        'staff_number.unique' => 'This staff number is already registered.',
        'staff_number.regex' => 'Staff number can only contain letters, numbers, and dashes.',
        'email.unique' => 'This email address is already registered.',
        'email.email' => 'Please enter a valid email address.',
        'phone.regex' => 'Please enter a valid phone number.',
        'password.min' => 'Password must be at least 8 characters.',
        'password.regex' => 'Password must contain letters and numbers.',
    ];
}
```

### UpdateUserRequest

**File:** `app/Http/Requests/Admin/UpdateUserRequest.php`

```php
public function rules(): array
{
    $userId = $this->route('user')->id;
    
    return [
        'name' => ['required', 'string', 'max:100'],
        'email' => [
            'required',
            'email:rfc,dns',
            'max:255',
            Rule::unique('users')->ignore($userId),
        ],
        'department' => ['nullable', 'string', 'max:100'],
        'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
        'role' => ['required', 'in:regular_user,administrator,director,system_admin'],
    ];
}
```

---

## Task 5.2.3: Room Management Validation

### StoreRoomRequest

**File:** `app/Http/Requests/StoreRoomRequest.php`

```php
public function rules(): array
{
    return [
        'name' => [
            'required',
            'string',
            'max:50',
            'unique:rooms,name',
        ],
        'capacity' => [
            'required',
            'integer',
            'min:1',
            'max:500',
        ],
        'floor_location' => [
            'required',
            'string',
            'max:100',
        ],
        'description' => [
            'nullable',
            'string',
            'max:500',
        ],
        'status' => [
            'required',
            'in:active,inactive,under_maintenance',
        ],
        'amenities' => [
            'nullable',
            'array',
        ],
        'amenities.*' => [
            'exists:amenities,id',
        ],
        'images' => [
            'nullable',
            'array',
            'max:5', // Maximum 5 images
        ],
        'images.*' => [
            'image',
            'mimes:jpg,jpeg,png',
            'max:5120', // 5MB max per image
        ],
    ];
}

public function messages(): array
{
    return [
        'name.unique' => 'A room with this name already exists.',
        'capacity.min' => 'Capacity must be at least 1.',
        'capacity.max' => 'Capacity cannot exceed 500.',
        'images.max' => 'You can upload a maximum of 5 images.',
        'images.*.max' => 'Each image must be less than 5MB.',
        'images.*.mimes' => 'Images must be JPG or PNG format.',
    ];
}
```

---

## Task 5.2.4: Booking Management Validation

### StoreBookingRequest

**File:** `app/Http/Requests/StoreBookingRequest.php`

```php
public function rules(): array
{
    return [
        'room_id' => [
            'required',
            'exists:rooms,id',
        ],
        'booking_date' => [
            'required',
            'date',
            'after_or_equal:today',
        ],
        'start_time' => [
            'required',
            'date_format:H:i',
            'after_or_equal:08:00',
            'before:18:00',
        ],
        'end_time' => [
            'required',
            'date_format:H:i',
            'after:start_time',
            'before_or_equal:18:00',
        ],
        'purpose' => [
            'required',
            'string',
            'max:500',
        ],
    ];
}

public function withValidator($validator)
{
    $validator->after(function ($validator) {
        // Duration validation (30 min - 8 hours)
        if ($this->start_time && $this->end_time) {
            $start = \Carbon\Carbon::parse($this->start_time);
            $end = \Carbon\Carbon::parse($this->end_time);
            $durationMinutes = $start->diffInMinutes($end);
            
            if ($durationMinutes < 30) {
                $validator->errors()->add('end_time', 'Booking must be at least 30 minutes.');
            }
            
            if ($durationMinutes > 480) { // 8 hours
                $validator->errors()->add('end_time', 'Booking cannot exceed 8 hours.');
            }
        }
        
        // Room active status check
        if ($this->room_id) {
            $room = \App\Models\Room::find($this->room_id);
            if ($room && $room->status !== 'active') {
                $validator->errors()->add('room_id', 'This room is not available for booking.');
            }
        }
        
        // Availability check
        if (!$validator->errors()->any() && $this->room_id && $this->booking_date) {
            $conflict = \App\Models\Booking::where('room_id', $this->room_id)
                ->where('booking_date', $this->booking_date)
                ->where('status', 'confirmed')
                ->where(function ($query) {
                    $query->whereBetween('start_time', [$this->start_time, $this->end_time])
                          ->orWhereBetween('end_time', [$this->start_time, $this->end_time])
                          ->orWhere(function ($q) {
                              $q->where('start_time', '<=', $this->start_time)
                                ->where('end_time', '>=', $this->end_time);
                          });
                })
                ->exists();
            
            if ($conflict) {
                $validator->errors()->add('room_id', 'This room is not available at the selected time.');
            }
        }
    });
}

public function messages(): array
{
    return [
        'booking_date.after_or_equal' => 'Booking date cannot be in the past.',
        'start_time.after_or_equal' => 'Booking must start at or after 8:00 AM.',
        'start_time.before' => 'Booking must start before 6:00 PM.',
        'end_time.after' => 'End time must be after start time.',
        'end_time.before_or_equal' => 'Booking must end by 6:00 PM.',
        'purpose.max' => 'Purpose cannot exceed 500 characters.',
    ];
}
```

---

## Task 5.2.5: Amenity Management Validation

### StoreAmenityRequest

**File:** `app/Http/Requests/StoreAmenityRequest.php`

```php
public function rules(): array
{
    return [
        'name' => [
            'required',
            'string',
            'max:50',
            'unique:amenities,name',
        ],
        'icon' => [
            'nullable',
            'string',
            'max:50',
            'regex:/^bx-[a-z-]+$/', // Boxicons format
        ],
        'description' => [
            'nullable',
            'string',
            'max:255',
        ],
    ];
}

public function messages(): array
{
    return [
        'name.unique' => 'An amenity with this name already exists.',
        'icon.regex' => 'Please select a valid icon.',
    ];
}
```

### UpdateAmenityRequest

**File:** `app/Http/Requests/UpdateAmenityRequest.php`

```php
public function rules(): array
{
    $amenityId = $this->route('amenity')->id;
    
    return [
        'name' => [
            'required',
            'string',
            'max:50',
            Rule::unique('amenities')->ignore($amenityId),
        ],
        'icon' => [
            'nullable',
            'string',
            'max:50',
        ],
        'description' => [
            'nullable',
            'string',
            'max:255',
        ],
    ];
}
```

---

## Task 5.2.6: Profile Management Validation

### UpdateProfileRequest

**File:** `app/Http/Requests/UpdateProfileRequest.php`

```php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:100'],
        'department' => ['nullable', 'string', 'max:100'],
        'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
    ];
}
```

---

## Task 5.2.7: Client-Side Validation Enhancement

Add real-time validation feedback using JavaScript:

**File:** `resources/js/validation.js` (or inline in Blade)

```javascript
// Real-time validation feedback
document.addEventListener('DOMContentLoaded', function() {
    // Required field validation
    document.querySelectorAll('input[required], select[required], textarea[required]').forEach(function(field) {
        field.addEventListener('blur', function() {
            validateField(this);
        });
        
        field.addEventListener('input', function() {
            // Clear error on input
            this.classList.remove('is-invalid');
            const feedback = this.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.textContent = '';
            }
        });
    });
    
    // Email validation
    document.querySelectorAll('input[type="email"]').forEach(function(field) {
        field.addEventListener('blur', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (this.value && !emailRegex.test(this.value)) {
                showFieldError(this, 'Please enter a valid email address.');
            }
        });
    });
    
    // Password strength indicator
    const passwordField = document.querySelector('input[name="password"]');
    if (passwordField) {
        passwordField.addEventListener('input', function() {
            updatePasswordStrength(this.value);
        });
    }
});

function validateField(field) {
    if (field.required && !field.value.trim()) {
        showFieldError(field, 'This field is required.');
        return false;
    }
    
    if (field.maxLength && field.value.length > field.maxLength) {
        showFieldError(field, `Maximum ${field.maxLength} characters allowed.`);
        return false;
    }
    
    field.classList.remove('is-invalid');
    field.classList.add('is-valid');
    return true;
}

function showFieldError(field, message) {
    field.classList.remove('is-valid');
    field.classList.add('is-invalid');
    
    let feedback = field.nextElementSibling;
    if (!feedback || !feedback.classList.contains('invalid-feedback')) {
        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        field.parentNode.appendChild(feedback);
    }
    feedback.textContent = message;
}

function updatePasswordStrength(password) {
    const indicator = document.querySelector('.password-strength');
    if (!indicator) return;
    
    let strength = 0;
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    const levels = ['', 'Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
    const colors = ['', 'danger', 'warning', 'info', 'success', 'success'];
    
    indicator.textContent = levels[strength];
    indicator.className = `password-strength text-${colors[strength]}`;
}
```

---

## Task 5.2.8: Blade Error Display Component

Create a reusable error display component:

**File:** `resources/views/components/form-error.blade.php`

```blade
@props(['field'])

@error($field)
    <div class="invalid-feedback d-block">
        {{ $message }}
    </div>
@enderror
```

**Usage:**
```blade
<input type="text" 
       name="name" 
       class="form-control @error('name') is-invalid @enderror"
       value="{{ old('name', $user->name ?? '') }}"
       required>
<x-form-error field="name" />
```

---

## Task 5.2.9: Form Validation Summary

Create a validation summary component for forms with multiple errors:

**File:** `resources/views/components/validation-summary.blade.php`

```blade
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h6 class="alert-heading mb-2">
            <i class="bx bx-error-circle me-2"></i>
            Please correct the following errors:
        </h6>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
```

**Usage at top of forms:**
```blade
<form method="POST" action="{{ route('admin.users.store') }}">
    @csrf
    <x-validation-summary />
    
    <!-- Form fields -->
</form>
```

---

## Task 5.2.10: Server-Side Validation Audit Checklist

Review each controller for proper validation:

```
□ LoginController
  ├── Email: required, email format
  ├── Password: required
  └── Failed attempts logged

□ ForgotPasswordController
  ├── Email: required, email format, exists in DB (don't reveal)
  └── Generic success message (security)

□ Admin\UserController
  ├── StoreUserRequest applied
  ├── UpdateUserRequest applied
  └── Unique constraints working

□ Admin\RoomController
  ├── StoreRoomRequest applied
  ├── UpdateRoomRequest applied
  ├── Image validation working
  └── Amenity IDs validated

□ BookingController
  ├── StoreBookingRequest applied
  ├── UpdateBookingRequest applied
  ├── Date/time business rules enforced
  └── Availability check working

□ Admin\AmenityController
  ├── StoreAmenityRequest applied
  ├── UpdateAmenityRequest applied
  └── Unique name constraint working

□ ProfileController
  ├── UpdateProfileRequest applied
  └── Password change validation

□ Admin\SettingsController
  ├── Setting key validation
  ├── Value type validation
  └── Range constraints (session timeout, etc.)
```

---

## Acceptance Criteria

- [ ] All Form Request classes have comprehensive validation rules
- [ ] Custom error messages are clear and actionable
- [ ] Client-side validation provides immediate feedback
- [ ] Server-side validation catches all edge cases
- [ ] Password strength indicator implemented
- [ ] Form validation summary component created
- [ ] All forms display errors properly with `is-invalid` class
- [ ] Required fields are clearly marked with asterisks
- [ ] Unique constraints validated before submission (where possible)
- [ ] Business rules (booking duration, operating hours) enforced

---

**Next:** [Step 5.3 - Error Handling](./step-5.3-error-handling.md)
