# Step 1.1: Database Schema - Users & Roles

**Priority:** CRITICAL | **Ref:** §4.2.1 | **Dependencies:** None  
**Status:** ✅ COMPLETED | **Completed:** December 7, 2025

---

## Objective
Create database tables for users, sessions, and password resets. Create User model with role/permission methods.

---

## Task 1.1.1: Users Migration

```bash
php artisan make:migration create_users_table
```

**Schema:**
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('staff_number', 20)->unique();
    $table->string('name', 100);
    $table->string('email', 255)->unique();
    $table->string('password');
    $table->boolean('must_change_password')->default(true);
    $table->timestamp('last_login_at')->nullable();
    $table->string('department', 100)->nullable();
    $table->string('phone', 20)->nullable();
    $table->enum('role', ['regular_user', 'administrator', 'director', 'system_admin'])->default('regular_user');
    $table->enum('status', ['active', 'inactive'])->default('active');
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes();
    $table->index(['role', 'status', 'department']);
});
```

---

## Task 1.1.2: Sessions Table

```bash
php artisan session:table
```

Update `.env`:
```env
SESSION_DRIVER=database
SESSION_LIFETIME=30
```

---

## Task 1.1.3: User Model

**File:** `app/Models/User.php` - Add these methods:

```php
// Role checks
public function isRegularUser(): bool { return $this->role === 'regular_user'; }
public function isAdmin(): bool { return $this->role === 'administrator'; }
public function isDirector(): bool { return $this->role === 'director'; }
public function isSysAdmin(): bool { return $this->role === 'system_admin'; }

// Permissions
public function canManageBookings(): bool { return in_array($this->role, ['administrator', 'director']); }
public function canManageRooms(): bool { return in_array($this->role, ['administrator', 'director']); }
public function canManageUsers(): bool { return in_array($this->role, ['director', 'system_admin']); }
public function canAccessAudit(): bool { return in_array($this->role, ['director', 'system_admin']); }
public function canAccessReports(): bool { return in_array($this->role, ['administrator', 'director', 'system_admin']); }
public function canConfigureSystem(): bool { return $this->role === 'system_admin'; }

// Scopes
public function scopeActive($query) { return $query->where('status', 'active'); }
public function scopeByRole($query, string $role) { return $query->where('role', $role); }
```

---

## Task 1.1.4: User Factory & Seeder

Create `UserSeeder` with 4 test accounts:
| Email | Password | Role |
|-------|----------|------|
| sysadmin@mrbs.local | password123 | System Admin |
| director@mrbs.local | password123 | Director |
| admin@mrbs.local | password123 | Administrator |
| user@mrbs.local | password123 | Regular User |

---

## Task 1.1.5: Run Migrations

```bash
php artisan migrate:fresh --seed
```

---

## Acceptance Criteria
- [x] `users`, `password_reset_tokens`, `sessions` tables exist
- [x] User model has 4 role check methods + 6 permission methods
- [x] Seeder creates 4 test accounts
- [x] `php artisan test --filter=UserTest` passes (31 tests)

---

## Implementation Notes

**Files Modified/Created:**
- `database/migrations/0001_01_01_000000_create_users_table.php`
- `app/Models/User.php`
- `database/factories/UserFactory.php`
- `database/seeders/UserSeeder.php`
- `database/seeders/DatabaseSeeder.php`
- `tests/Unit/UserTest.php`
- `phpunit.xml` (configured for PostgreSQL tests)

---

**Next:** [Step 1.2 - Authentication](./step-1.2-authentication.md)

