<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| User Model - Role Check Tests
|--------------------------------------------------------------------------
*/

describe('Role Checks', function () {
    it('identifies a regular user correctly', function () {
        $user = User::factory()->regularUser()->create();

        expect($user->isRegularUser())->toBeTrue()
            ->and($user->isAdmin())->toBeFalse()
            ->and($user->isDirector())->toBeFalse()
            ->and($user->isSysAdmin())->toBeFalse();
    });

    it('identifies an administrator correctly', function () {
        $user = User::factory()->administrator()->create();

        expect($user->isAdmin())->toBeTrue()
            ->and($user->isRegularUser())->toBeFalse()
            ->and($user->isDirector())->toBeFalse()
            ->and($user->isSysAdmin())->toBeFalse();
    });

    it('identifies a director correctly', function () {
        $user = User::factory()->director()->create();

        expect($user->isDirector())->toBeTrue()
            ->and($user->isRegularUser())->toBeFalse()
            ->and($user->isAdmin())->toBeFalse()
            ->and($user->isSysAdmin())->toBeFalse();
    });

    it('identifies a system admin correctly', function () {
        $user = User::factory()->systemAdmin()->create();

        expect($user->isSysAdmin())->toBeTrue()
            ->and($user->isRegularUser())->toBeFalse()
            ->and($user->isAdmin())->toBeFalse()
            ->and($user->isDirector())->toBeFalse();
    });
});

/*
|--------------------------------------------------------------------------
| User Model - Permission Check Tests
|--------------------------------------------------------------------------
*/

describe('Permission Checks', function () {
    describe('canManageBookings', function () {
        it('allows administrators to manage bookings', function () {
            $user = User::factory()->administrator()->create();
            expect($user->canManageBookings())->toBeTrue();
        });

        it('allows directors to manage bookings', function () {
            $user = User::factory()->director()->create();
            expect($user->canManageBookings())->toBeTrue();
        });

        it('denies regular users from managing bookings', function () {
            $user = User::factory()->regularUser()->create();
            expect($user->canManageBookings())->toBeFalse();
        });

        it('denies system admins from managing bookings', function () {
            $user = User::factory()->systemAdmin()->create();
            expect($user->canManageBookings())->toBeFalse();
        });
    });

    describe('canManageRooms', function () {
        it('allows administrators to manage rooms', function () {
            $user = User::factory()->administrator()->create();
            expect($user->canManageRooms())->toBeTrue();
        });

        it('allows directors to manage rooms', function () {
            $user = User::factory()->director()->create();
            expect($user->canManageRooms())->toBeTrue();
        });

        it('denies regular users from managing rooms', function () {
            $user = User::factory()->regularUser()->create();
            expect($user->canManageRooms())->toBeFalse();
        });
    });

    describe('canManageUsers', function () {
        it('allows directors to manage users', function () {
            $user = User::factory()->director()->create();
            expect($user->canManageUsers())->toBeTrue();
        });

        it('allows system admins to manage users', function () {
            $user = User::factory()->systemAdmin()->create();
            expect($user->canManageUsers())->toBeTrue();
        });

        it('denies administrators from managing users', function () {
            $user = User::factory()->administrator()->create();
            expect($user->canManageUsers())->toBeFalse();
        });

        it('denies regular users from managing users', function () {
            $user = User::factory()->regularUser()->create();
            expect($user->canManageUsers())->toBeFalse();
        });
    });

    describe('canAccessAudit', function () {
        it('allows directors to access audit trail', function () {
            $user = User::factory()->director()->create();
            expect($user->canAccessAudit())->toBeTrue();
        });

        it('allows system admins to access audit trail', function () {
            $user = User::factory()->systemAdmin()->create();
            expect($user->canAccessAudit())->toBeTrue();
        });

        it('denies administrators from accessing audit trail', function () {
            $user = User::factory()->administrator()->create();
            expect($user->canAccessAudit())->toBeFalse();
        });

        it('denies regular users from accessing audit trail', function () {
            $user = User::factory()->regularUser()->create();
            expect($user->canAccessAudit())->toBeFalse();
        });
    });

    describe('canAccessReports', function () {
        it('allows administrators to access reports', function () {
            $user = User::factory()->administrator()->create();
            expect($user->canAccessReports())->toBeTrue();
        });

        it('allows directors to access reports', function () {
            $user = User::factory()->director()->create();
            expect($user->canAccessReports())->toBeTrue();
        });

        it('allows system admins to access reports', function () {
            $user = User::factory()->systemAdmin()->create();
            expect($user->canAccessReports())->toBeTrue();
        });

        it('denies regular users from accessing reports', function () {
            $user = User::factory()->regularUser()->create();
            expect($user->canAccessReports())->toBeFalse();
        });
    });

    describe('canConfigureSystem', function () {
        it('allows only system admins to configure system', function () {
            $user = User::factory()->systemAdmin()->create();
            expect($user->canConfigureSystem())->toBeTrue();
        });

        it('denies directors from configuring system', function () {
            $user = User::factory()->director()->create();
            expect($user->canConfigureSystem())->toBeFalse();
        });

        it('denies administrators from configuring system', function () {
            $user = User::factory()->administrator()->create();
            expect($user->canConfigureSystem())->toBeFalse();
        });

        it('denies regular users from configuring system', function () {
            $user = User::factory()->regularUser()->create();
            expect($user->canConfigureSystem())->toBeFalse();
        });
    });
});

/*
|--------------------------------------------------------------------------
| User Model - Query Scope Tests
|--------------------------------------------------------------------------
*/

describe('Query Scopes', function () {
    beforeEach(function () {
        // Create a mix of users
        User::factory()->regularUser()->create(['status' => 'active', 'department' => 'IT']);
        User::factory()->administrator()->create(['status' => 'active', 'department' => 'HR']);
        User::factory()->director()->create(['status' => 'inactive', 'department' => 'IT']);
        User::factory()->systemAdmin()->create(['status' => 'active', 'department' => 'IT']);
    });

    it('filters active users correctly', function () {
        $activeUsers = User::active()->get();

        expect($activeUsers)->toHaveCount(3)
            ->and($activeUsers->every(fn($u) => $u->status === 'active'))->toBeTrue();
    });

    it('filters users by role correctly', function () {
        $admins = User::byRole('administrator')->get();

        expect($admins)->toHaveCount(1)
            ->and($admins->first()->role)->toBe('administrator');
    });

    it('filters users by department correctly', function () {
        $itUsers = User::byDepartment('IT')->get();

        expect($itUsers)->toHaveCount(3)
            ->and($itUsers->every(fn($u) => $u->department === 'IT'))->toBeTrue();
    });

    it('chains multiple scopes correctly', function () {
        $activeITUsers = User::active()->byDepartment('IT')->get();

        expect($activeITUsers)->toHaveCount(2)
            ->and($activeITUsers->every(fn($u) => $u->status === 'active' && $u->department === 'IT'))->toBeTrue();
    });
});

/*
|--------------------------------------------------------------------------
| User Model - Soft Delete Tests
|--------------------------------------------------------------------------
*/

describe('Soft Deletes', function () {
    it('soft deletes a user instead of permanently deleting', function () {
        $user = User::factory()->create();
        $userId = $user->id;

        $user->delete();

        expect(User::find($userId))->toBeNull()
            ->and(User::withTrashed()->find($userId))->not->toBeNull();
    });

    it('can restore a soft deleted user', function () {
        $user = User::factory()->create();
        $userId = $user->id;

        $user->delete();
        User::withTrashed()->find($userId)->restore();

        expect(User::find($userId))->not->toBeNull();
    });
});

/*
|--------------------------------------------------------------------------
| User Model - Attribute Casting Tests
|--------------------------------------------------------------------------
*/

describe('Attribute Casting', function () {
    it('casts must_change_password to boolean', function () {
        $user = User::factory()->create(['must_change_password' => 1]);

        expect($user->must_change_password)->toBeBool()
            ->and($user->must_change_password)->toBeTrue();
    });

    it('casts last_login_at to datetime', function () {
        $user = User::factory()->create(['last_login_at' => now()]);

        expect($user->last_login_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    });

    it('hashes password automatically', function () {
        $user = User::factory()->create(['password' => 'plaintext']);

        expect($user->password)->not->toBe('plaintext')
            ->and(strlen($user->password))->toBeGreaterThan(50); // bcrypt hashes are ~60 chars
    });
});
