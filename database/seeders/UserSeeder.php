<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // System Admin - Full system access
        User::factory()->create([
            'staff_number' => 'SYS-001',
            'name' => 'System Administrator',
            'email' => 'sysadmin@mrbs.local',
            'password' => Hash::make('password123'),
            'must_change_password' => false,
            'department' => 'IT',
            'phone' => '+60 3-1234 5001',
            'role' => 'system_admin',
            'status' => 'active',
        ]);

        // Director - Executive oversight
        User::factory()->create([
            'staff_number' => 'DIR-001',
            'name' => 'Director User',
            'email' => 'director@mrbs.local',
            'password' => Hash::make('password123'),
            'must_change_password' => false,
            'department' => 'Management',
            'phone' => '+60 3-1234 5002',
            'role' => 'director',
            'status' => 'active',
        ]);

        // Administrator - Booking and room management
        User::factory()->create([
            'staff_number' => 'ADM-001',
            'name' => 'Admin User',
            'email' => 'admin@mrbs.local',
            'password' => Hash::make('password123'),
            'must_change_password' => false,
            'department' => 'Operations',
            'phone' => '+60 3-1234 5003',
            'role' => 'administrator',
            'status' => 'active',
        ]);

        // Regular User - Standard booking access
        User::factory()->create([
            'staff_number' => 'USR-001',
            'name' => 'Regular User',
            'email' => 'user@mrbs.local',
            'password' => Hash::make('password123'),
            'must_change_password' => false,
            'department' => 'Sales',
            'phone' => '+60 3-1234 5004',
            'role' => 'regular_user',
            'status' => 'active',
        ]);
    }
}
