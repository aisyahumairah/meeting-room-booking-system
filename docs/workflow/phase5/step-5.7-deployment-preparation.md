# Step 5.7: Deployment Preparation

**Priority:** CRITICAL | **Ref:** §8.5.1, §8.5.2, §8.5.3 | **Dependencies:** Steps 5.1-5.6  
**Status:** TODO

---

## Objective

Prepare the MRBS application for production deployment on a local server environment, including configuration, initial data, and go-live procedures.

**Note:** SSL/HTTPS setup is out of scope. Focus is on application configuration only.

---

## Task 5.7.1: Production Environment Configuration

### Environment File

**File:** `.env.production` (template)

```env
APP_NAME="Meeting Room Booking System"
APP_ENV=production
APP_KEY=base64:GENERATE_NEW_KEY_HERE
APP_DEBUG=false
APP_URL=http://your-server-url

LOG_CHANNEL=stack
LOG_LEVEL=error

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=mrbs_production
DB_USERNAME=mrbs_user
DB_PASSWORD=secure_password_here

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=30
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax

# Cache
CACHE_DRIVER=file

# Queue (for email notifications)
QUEUE_CONNECTION=database

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourcompany.com
MAIL_FROM_NAME="${APP_NAME}"

# Timezone
APP_TIMEZONE=Asia/Kuala_Lumpur
```

### Generate Application Key

```bash
# On production server
php artisan key:generate
```

---

## Task 5.7.2: Database Preparation

### Create Production Database

```sql
-- PostgreSQL
CREATE DATABASE mrbs_production;
CREATE USER mrbs_user WITH ENCRYPTED PASSWORD 'secure_password_here';
GRANT ALL PRIVILEGES ON DATABASE mrbs_production TO mrbs_user;
```

### Run Migrations

```bash
php artisan migrate --force
```

### Run Seeders (Initial Data)

```bash
# Run all seeders
php artisan db:seed --force

# Or run specific seeders
php artisan db:seed --class=AmenitySeeder --force
php artisan db:seed --class=SystemSettingSeeder --force
```

---

## Task 5.7.3: Create Initial Admin Accounts

**File:** `database/seeders/ProductionUserSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductionUserSeeder extends Seeder
{
    public function run(): void
    {
        // System Administrator
        User::firstOrCreate(
            ['email' => 'sysadmin@yourcompany.com'],
            [
                'staff_number' => 'SYSADMIN001',
                'name' => 'System Administrator',
                'password' => Hash::make('ChangeThisPassword!'),
                'role' => 'system_admin',
                'status' => 'active',
                'must_change_password' => true,
                'department' => 'IT Department',
            ]
        );
        
        // Director
        User::firstOrCreate(
            ['email' => 'director@yourcompany.com'],
            [
                'staff_number' => 'DIR001',
                'name' => 'Director Account',
                'password' => Hash::make('ChangeThisPassword!'),
                'role' => 'director',
                'status' => 'active',
                'must_change_password' => true,
                'department' => 'Management',
            ]
        );
        
        // Administrator
        User::firstOrCreate(
            ['email' => 'admin@yourcompany.com'],
            [
                'staff_number' => 'ADMIN001',
                'name' => 'Administrator Account',
                'password' => Hash::make('ChangeThisPassword!'),
                'role' => 'administrator',
                'status' => 'active',
                'must_change_password' => true,
                'department' => 'Administration',
            ]
        );
        
        $this->command->info('Production user accounts created successfully.');
        $this->command->warn('IMPORTANT: All accounts have must_change_password = true');
        $this->command->warn('Users will be required to change password on first login.');
    }
}
```

Run the seeder:
```bash
php artisan db:seed --class=ProductionUserSeeder --force
```

---

## Task 5.7.4: Create Initial Meeting Rooms

**File:** `database/seeders/ProductionRoomSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\Amenity;
use Illuminate\Database\Seeder;

class ProductionRoomSeeder extends Seeder
{
    public function run(): void
    {
        // Get amenities
        $amenities = Amenity::active()->pluck('id', 'name');
        
        $rooms = [
            [
                'name' => 'Conference Room A',
                'capacity' => 20,
                'floor_location' => 'Floor 1',
                'description' => 'Large conference room suitable for board meetings.',
                'status' => 'active',
                'amenities' => ['Projector', 'Video Conferencing', 'Whiteboard', 'Air Conditioning'],
            ],
            [
                'name' => 'Meeting Room B',
                'capacity' => 10,
                'floor_location' => 'Floor 1',
                'description' => 'Medium meeting room for team discussions.',
                'status' => 'active',
                'amenities' => ['Projector', 'Whiteboard', 'Air Conditioning'],
            ],
            [
                'name' => 'Discussion Room C',
                'capacity' => 6,
                'floor_location' => 'Floor 2',
                'description' => 'Small discussion room for quick meetings.',
                'status' => 'active',
                'amenities' => ['Whiteboard', 'Air Conditioning'],
            ],
            // Add more rooms as needed
        ];
        
        foreach ($rooms as $roomData) {
            $roomAmenities = $roomData['amenities'] ?? [];
            unset($roomData['amenities']);
            
            $room = Room::firstOrCreate(
                ['name' => $roomData['name']],
                $roomData
            );
            
            // Attach amenities
            $amenityIds = [];
            foreach ($roomAmenities as $amenityName) {
                if (isset($amenities[$amenityName])) {
                    $amenityIds[] = $amenities[$amenityName];
                }
            }
            $room->amenities()->sync($amenityIds);
        }
        
        $this->command->info('Production rooms created successfully.');
    }
}
```

---

## Task 5.7.5: System Settings Initialization

**File:** `database/seeders/ProductionSettingsSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class ProductionSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Session & Security
            ['key' => 'session_timeout', 'value' => '30', 'type' => 'integer'],
            ['key' => 'password_reset_expiry', 'value' => '30', 'type' => 'integer'],
            ['key' => 'login_attempt_limit', 'value' => '5', 'type' => 'integer'],
            ['key' => 'lockout_duration', 'value' => '15', 'type' => 'integer'],
            
            // Booking Rules (read-only display)
            ['key' => 'operating_hours_start', 'value' => '08:00', 'type' => 'string'],
            ['key' => 'operating_hours_end', 'value' => '18:00', 'type' => 'string'],
            ['key' => 'min_booking_duration', 'value' => '30', 'type' => 'integer'],
            ['key' => 'max_booking_duration', 'value' => '480', 'type' => 'integer'],
            
            // Notifications
            ['key' => 'notifications_enabled', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'email_booking_confirmation', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'email_booking_reminder', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'email_booking_cancellation', 'value' => 'true', 'type' => 'boolean'],
            
            // Maintenance Mode
            ['key' => 'maintenance_mode', 'value' => 'false', 'type' => 'boolean'],
        ];
        
        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'type' => $setting['type']]
            );
        }
        
        $this->command->info('System settings initialized successfully.');
    }
}
```

---

## Task 5.7.6: Production Optimization Script

**File:** `scripts/deploy.ps1` (PowerShell)

```powershell
# MRBS Production Deployment Script
# Run from project root directory

Write-Host "=== MRBS Production Deployment ===" -ForegroundColor Cyan
Write-Host ""

# Step 1: Maintenance mode ON
Write-Host "[1/8] Enabling maintenance mode..." -ForegroundColor Yellow
php artisan down --render="errors::maintenance" --retry=60

# Step 2: Pull latest code (if using Git)
# Write-Host "[2/8] Pulling latest code..." -ForegroundColor Yellow
# git pull origin main

# Step 3: Install dependencies
Write-Host "[2/8] Installing dependencies..." -ForegroundColor Yellow
composer install --optimize-autoloader --no-dev

# Step 4: Run migrations
Write-Host "[3/8] Running database migrations..." -ForegroundColor Yellow
php artisan migrate --force

# Step 5: Clear and rebuild caches
Write-Host "[4/8] Clearing caches..." -ForegroundColor Yellow
php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear

Write-Host "[5/8] Building optimized caches..." -ForegroundColor Yellow
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Step 6: Restart queue workers (if applicable)
# Write-Host "[6/8] Restarting queue workers..." -ForegroundColor Yellow
# php artisan queue:restart

# Step 7: Run tests (optional in production)
# Write-Host "[7/8] Running tests..." -ForegroundColor Yellow
# php artisan test

# Step 8: Maintenance mode OFF
Write-Host "[8/8] Disabling maintenance mode..." -ForegroundColor Yellow
php artisan up

Write-Host ""
Write-Host "=== Deployment Complete ===" -ForegroundColor Green
Write-Host "Application is now live." -ForegroundColor Green
```

**Usage:**
```powershell
.\scripts\deploy.ps1
```

---

## Task 5.7.7: Backup Procedures

### Database Backup Script

**File:** `scripts/backup-db.ps1`

```powershell
# Database Backup Script
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupDir = "storage/backups"
$backupFile = "$backupDir/mrbs_backup_$timestamp.sql"

# Create backup directory if not exists
if (!(Test-Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir
}

# PostgreSQL backup
$env:PGPASSWORD = "your_password"
pg_dump -h localhost -U mrbs_user mrbs_production > $backupFile

Write-Host "Backup created: $backupFile" -ForegroundColor Green
```

### Scheduled Backup (Windows Task Scheduler)

Create a scheduled task to run the backup script daily:

```powershell
# Create scheduled task (run as Administrator)
$action = New-ScheduledTaskAction -Execute "PowerShell.exe" -Argument "-File C:\path\to\mrbs\scripts\backup-db.ps1"
$trigger = New-ScheduledTaskTrigger -Daily -At "02:00"
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM"

Register-ScheduledTask -TaskName "MRBS_Database_Backup" -Action $action -Trigger $trigger -Principal $principal
```

---

## Task 5.7.8: Go-Live Checklist

### Pre-Deployment

```
□ Environment Configuration
  ├── .env file configured for production
  ├── APP_DEBUG=false
  ├── APP_KEY generated
  ├── Database credentials set
  └── Mail settings configured

□ Database
  ├── Production database created
  ├── Database user created with appropriate permissions
  ├── Migrations run successfully
  └── Initial data seeded

□ Initial Accounts
  ├── System Administrator account created
  ├── Director account created
  ├── Administrator account created
  └── All accounts set to must_change_password=true

□ Initial Data
  ├── Amenities seeded
  ├── Meeting rooms created
  └── System settings configured

□ Testing
  ├── All tests passing
  ├── Manual testing completed
  ├── User acceptance testing completed
  └── Performance verified
```

### Deployment Day

```
□ Pre-Deployment
  ├── Notify users of scheduled downtime (if any)
  ├── Backup existing data (if upgrading)
  └── Schedule deployment during off-peak hours

□ Deployment Steps
  ├── Enable maintenance mode
  ├── Deploy code changes
  ├── Run migrations
  ├── Clear and rebuild caches
  ├── Verify application loads
  ├── Test login with admin account
  ├── Disable maintenance mode
  └── Verify public access

□ Post-Deployment Verification
  ├── Login works for all roles
  ├── Dashboard loads correctly
  ├── Room browsing works
  ├── Booking creation works
  ├── Admin functions accessible
  └── Reports generate correctly
```

### Post-Deployment (First Week)

```
□ Monitoring
  ├── Check error logs daily: storage/logs/laravel.log
  ├── Monitor server resources (CPU, memory, disk)
  ├── Track user activity (logins, bookings)
  └── Review audit logs for issues

□ User Support
  ├── Distribute login credentials
  ├── Send user guide/quick start documentation
  ├── Provide support contact information
  └── Collect user feedback

□ Issue Response
  ├── Document any issues encountered
  ├── Prioritize and fix critical bugs
  └── Communicate fixes to users
```

---

## Task 5.7.9: User Communication Templates

### Go-Live Announcement Email

```markdown
Subject: New Meeting Room Booking System Now Available

Dear Team,

We are pleased to announce that the new Meeting Room Booking System (MRBS) 
is now live and available for use.

**How to Access:**
- URL: [YOUR_SYSTEM_URL]
- Login: Use your email address and the temporary password provided

**First Login:**
- You will be required to change your password on first login
- Passwords must be at least 8 characters with letters and numbers

**Quick Start:**
1. Log in with your credentials
2. Browse available meeting rooms
3. Click "Book" to create a new booking
4. View your bookings in "My Bookings"

**Need Help?**
- Contact: [SUPPORT_EMAIL]
- Phone: [SUPPORT_PHONE]

Best regards,
IT Department
```

### User Credentials Distribution

```markdown
Subject: Your MRBS Login Credentials

Dear [USER_NAME],

Your account for the Meeting Room Booking System has been created.

**Login Details:**
- URL: [YOUR_SYSTEM_URL]
- Email: [USER_EMAIL]
- Temporary Password: [TEMP_PASSWORD]

**Important:**
- You will be required to change your password on first login
- Do not share your credentials with anyone

If you have any questions, please contact [SUPPORT_EMAIL].

Best regards,
IT Department
```

---

## Task 5.7.10: Rollback Procedure

In case of critical issues after deployment:

### Quick Rollback Steps

```powershell
# 1. Enable maintenance mode
php artisan down

# 2. Restore database from backup
$env:PGPASSWORD = "your_password"
psql -h localhost -U mrbs_user mrbs_production < storage/backups/mrbs_backup_YYYYMMDD_HHMMSS.sql

# 3. Revert code changes (if using Git)
git checkout previous-release-tag

# 4. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 5. Disable maintenance mode
php artisan up

# 6. Notify users
```

### Rollback Decision Criteria

| Issue | Action |
|-------|--------|
| Minor UI bugs | Hotfix, no rollback |
| Feature not working | Disable feature, schedule fix |
| Data corruption | Immediate rollback |
| Security vulnerability | Immediate rollback |
| Complete system failure | Immediate rollback |

---

## Acceptance Criteria

- [ ] Production environment file configured
- [ ] Database created and migrations run
- [ ] Initial admin accounts created with must_change_password=true
- [ ] Initial meeting rooms seeded
- [ ] System settings initialized
- [ ] Deployment script created and tested
- [ ] Backup script created and tested
- [ ] Go-live checklist completed
- [ ] User communication templates prepared
- [ ] Rollback procedure documented
- [ ] Application accessible and functional

---

## Final Verification

After deployment, verify:

```bash
# Check application status
php artisan about

# Check route list
php artisan route:list

# Check database connection
php artisan db:monitor

# Check queue status (if using database queue)
php artisan queue:work --once

# Check logs for errors
tail -f storage/logs/laravel.log
```

---

## Congratulations! 🎉

Phase 5 is complete. The MRBS application is now:

- ✅ Responsive across all devices
- ✅ Thoroughly validated (client + server)
- ✅ Error handling with custom pages
- ✅ Security hardened
- ✅ Performance optimized
- ✅ Fully tested
- ✅ Production ready

**The Meeting Room Booking System is ready for deployment!**
