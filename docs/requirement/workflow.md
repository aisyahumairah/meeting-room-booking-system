# MRBS Development Workflow & Roadmap

**Reference:** [Requirements Document](./requirements-document.md)  
**Created:** November 27, 2025  
**Purpose:** Step-by-step development guide for the Meeting Room Booking System

---

## Table of Contents

1. [Development Principles](#development-principles)
2. [Tech Stack](#tech-stack)
3. [Phase 1: Foundation & Core Layout](#phase-1-foundation--core-layout) - COMPLETED
4. [Phase 2: Meeting Rooms Management](#phase-2-meeting-rooms-management) - COMPLETED
5. [Phase 3: Booking Management](#phase-3-booking-management) - COMPLETED
6. [Phase 4: Administrative Management](#phase-4-administrative-management)
7. [Phase 5: System Quality & Deployment](#phase-5-system-quality--deployment)
8. [Development Checklist](#development-checklist)

---

## Development Principles

### Order of Development (Per Feature)
1. **Database Migration** → Define tables, relationships, indexes
2. **Model** → Eloquent models with relationships, scopes, accessors
3. **Controller** → Backend logic, validation, business rules
4. **Blade Views** → Convert mockup HTML to Blade templates
5. **Testing** → Feature tests for critical paths

### Dependencies Rule
- Never start a feature that depends on incomplete features
- Complete backend before frontend for each module
- Database schema should be finalized before heavy development

---

## Tech Stack

### Backend
| Component | Technology | Notes |
|-----------|------------|-------|
| Framework | Laravel 11.x | PHP 8.2+ |
| Database | PostgreSQL | Primary database |
| Authentication | Laravel Session Auth | Built-in, no Sanctum needed |
| Authorization | Simple Role Column | 4 roles in `users.role` field (no Spatie needed) |
| Excel Export | Laravel Excel | `maatwebsite/excel` |
| PDF Export | DomPDF | `barryvdh/laravel-dompdf` |

### Frontend
| Component | Technology | Notes |
|-----------|------------|-------|
| Template Engine | Blade | Laravel built-in |
| CSS Framework | Bootstrap 5 | Via Sneat Admin Template |
| JavaScript | Vanilla JS | No framework needed |
| Charts | ApexCharts | Already in mockup |
| Icons | Boxicons | Already in mockup |

### Why No Sanctum?
- System uses **session-based authentication** (web app, not API)
- Laravel's built-in session auth is sufficient
- No mobile app or SPA requiring token-based auth

### Why No Spatie Permission?
- Only **4 fixed roles**: Regular User, Administrator, Director, System Admin
- Roles stored directly in `users.role` column (enum)
- Simple `isAdmin()`, `isDirector()`, `isSysAdmin()` methods on User model
- No need for dynamic permissions or role management
- Middleware checks role directly from user record

### UI Mockup Reference
All frontend designs are pre-built in the mockup folder:
```
mrbs-mock-up/
├── pages/                    # All HTML page templates
│   ├── auth-login.html
│   ├── auth-register.html
│   ├── auth-forgot-password.html
│   ├── dashboard-admin.html
│   ├── dashboard-user.html
│   ├── rooms-*.html
│   ├── bookings-*.html
│   ├── users-*.html
│   ├── reports-*.html
│   ├── settings-system.html
│   └── system-audit-logs.html
├── assets/                   # JS, CSS, images
│   ├── js/interactions.js    # Toast, modals, etc.
│   └── vendor/               # Bootstrap, ApexCharts, etc.
└── styles/index.css          # Custom styles
```

### Package Installation
```bash
# Required packages
composer require maatwebsite/excel
composer require barryvdh/laravel-dompdf

# Already included in Laravel
# - Carbon (date handling)
# - Session auth
# - CSRF protection
```

### Frontend Setup
```
□ Copy mockup assets to Laravel public folder
□ Convert mockup HTML to Blade layout + components
□ Set up Blade component structure:
  ├── layouts/app.blade.php (main layout with sidebar)
  ├── layouts/auth.blade.php (auth pages layout)
  ├── components/sidebar.blade.php
  ├── components/navbar.blade.php
  └── components/toast.blade.php
```

---

## Phase 1: Foundation & Core Layout

**Goal:** Authentication, roles, dashboards, and navigation structure  
**Req Reference:** Section 4 (Phase 1: Foundation & Core Layout)

### Step 1.1: Database Schema - Users & Roles
**Priority: CRITICAL** | **Ref:** §4.2.1

```
□ Create migrations:
  ├── users table
  │   ├── id, staff_number (unique), name, email (unique)
  │   ├── password, department, phone
  │   ├── role (enum: regular_user, administrator, director, system_admin)
  │   ├── status (enum: active, inactive)
  │   ├── last_login_at, must_change_password
  │   ├── timestamps, soft_deletes
  │
  ├── password_reset_tokens table (Laravel default)
  │
  └── sessions table (for database sessions)

□ Create User model with:
  ├── Fillable fields
  ├── Hidden fields (password)
  ├── Casts (role, status as enums)
  ├── Role check methods: isAdmin(), isDirector(), isSysAdmin(), isRegularUser()
  └── Scopes: active(), byRole()
```

### Step 1.2: Authentication System
**Priority: CRITICAL** | **Ref:** §4.2.2, §4.2.3

```
□ Login functionality [§4.2.2]
  ├── POST /login - validate credentials
  ├── Session creation with 30-min timeout
  ├── Redirect based on role (see §4.2.2 redirect rules)
  ├── Last login timestamp update
  └── Failed login logging

□ Logout functionality [§4.2.2]
  ├── POST /logout
  └── Session invalidation

□ Password Reset [§4.2.3]
  ├── GET /forgot-password - form
  ├── POST /forgot-password - send reset email
  ├── GET /reset-password/{token} - reset form
  ├── POST /reset-password - update password
  └── 30-minute token expiry

□ First Login Password Change [§4.2.1]
  ├── Middleware to check must_change_password
  ├── Force redirect to password change page
  └── Update must_change_password after change

□ Session timeout middleware [§4.2.2]
  └── Auto-logout after 30 minutes inactivity

□ Mockup pages:
  ├── auth-login.html → resources/views/auth/login.blade.php
  ├── auth-forgot-password.html → resources/views/auth/forgot-password.blade.php
  └── auth-register.html → (admin-only create user form)
```

### Step 1.3: Authorization & Middleware
**Priority: CRITICAL** | **Ref:** §3.5, §3.6

```
□ Create middleware:
  ├── CheckRole - verify user has required role
  ├── CheckActive - verify user status is active
  └── MaintenanceMode - block non-admins when enabled [§7.3.3]

□ Define role permissions using Gates/Policies [§3.5]:
  ├── Regular User: own bookings only
  ├── Administrator: bookings + rooms + reports
  ├── Director: all admin + users + audit
  └── System Admin: users + config (no bookings/rooms)

□ Role helper methods on User model:
  ├── isRegularUser()
  ├── isAdmin()
  ├── isDirector()
  ├── isSysAdmin()
  ├── canManageBookings() → Admin, Director
  ├── canManageRooms() → Admin, Director
  ├── canManageUsers() → Director, SysAdmin
  └── canAccessAudit() → Director, SysAdmin
```

### Step 1.4: Global Layout & Navigation
**Priority: HIGH** | **Ref:** §4.4

```
□ Create base layout template [§4.4]:
  ├── Sidebar navigation (collapsible)
  ├── Top navbar (logo, search, user dropdown)
  └── Footer

□ Implement role-based menu visibility [§4.4 Table]:
  ├── Dashboard links per role
  ├── Meeting Rooms section
  ├── Bookings section
  ├── Reports section (Admin/Director/SysAdmin)
  ├── User Management (Director/SysAdmin)
  └── System Settings (SysAdmin only)

□ User profile dropdown:
  ├── My Profile link
  ├── Change Password link
  └── Logout link

□ Mockup reference:
  └── layout.html → resources/views/layouts/app.blade.php
```

### Step 1.5: User Profile Management
**Priority: MEDIUM** | **Ref:** §4.2.4

```
□ Profile view page [§4.2.4]
  ├── Display all user info (staff_number, name, email, dept, phone)
  └── Role badge (read-only)

□ Profile edit [§4.2.4]
  ├── Editable: name, department, phone
  ├── Read-only: staff_number, email, role
  └── Validation

□ Change password [§4.2.4]
  ├── Verify current password
  ├── Validate new password (8 chars, letters, numbers, symbols)
  └── Update password

□ Mockup reference:
  └── users-edit.html (adapt for self-profile)
```

### Step 1.6: Dashboards
**Priority: HIGH** | **Ref:** §4.3.1, §4.3.2

```
□ User Dashboard [§4.3.2] (Regular User, System Admin)
  ├── Upcoming bookings widget (next 5 or 7 days)
  ├── Past bookings widget (last 5)
  ├── Quick Book button
  ├── My bookings chart (last 6 months)
  └── Quick stats (total, this month, pending, cancelled)

□ Admin Dashboard [§4.3.1] (Administrator, Director)
  ├── Today's bookings widget
  ├── Booking statistics (week/month with comparison)
  ├── Room utilization chart (top 5 rooms)
  └── Quick actions panel

□ Mockup pages:
  ├── dashboard-user.html → resources/views/dashboard/user.blade.php
  └── dashboard-admin.html → resources/views/dashboard/admin.blade.php
```

### Step 1.7: Audit Trail Foundation
**Priority: HIGH** | **Ref:** §7.3.1

```
□ Create audit_logs migration [§7.3.1]:
  ├── id, event_type, actor_id, actor_name
  ├── target_type, target_id
  ├── details (JSON), ip_address, user_agent
  └── created_at (no updated_at - immutable)

□ Create AuditLog model (read-only)
  └── No update/delete methods (immutable)

□ Create AuditService or Trait:
  └── log(eventType, targetType, targetId, details)

□ Implement logging for Phase 1 events [§7.3.1]:
  ├── Login success/failure
  ├── Logout
  ├── Password reset request/completion
  └── Profile updates
```

---

## Phase 2: Meeting Rooms Management

**Goal:** Room CRUD, search, filtering, and availability display  
**Req Reference:** Section 5 (Phase 2: Meeting Rooms Management)  
**Dependency:** Phase 1 must be complete (auth, roles, layout)

### Step 2.1: Database Schema - Rooms
**Priority: CRITICAL** | **Ref:** §5.2, §5.5.1

```
□ Create migrations:
  ├── rooms table
  │   ├── id, name (unique), capacity, floor_location
  │   ├── description (500 chars max), status (enum: active, inactive, under_maintenance)
  │   ├── timestamps, soft_deletes
  │
  ├── amenities table [§5.3.1]
  │   ├── id, name, icon (optional)
  │
  ├── amenity_room pivot table
  │   └── room_id, amenity_id
  │
  └── room_images table [§5.5.1]
      ├── id, room_id, path, is_primary, sort_order
      └── timestamps (max 5 images, JPG/PNG, 5MB each)

□ Create Room model with:
  ├── Relationships: amenities(), images()
  ├── Scopes: active(), available(), byCapacity()
  ├── Accessors: primary_image, status_badge
  └── Methods: isAvailable(date, startTime, endTime)

□ Seed default amenities [§5.3.1]:
  └── Projector, Whiteboard, Video Conferencing, Teleconferencing Phone,
      Computer/Monitor, Flip Chart, Air Conditioning, Natural Light/Windows
```

### Step 2.2: Room Maintenance Scheduling
**Priority: MEDIUM** | **Ref:** §5.5.2

```
□ Create room_maintenance_schedules table [§5.5.2]:
  ├── id, room_id
  ├── start_datetime, end_datetime
  ├── reason (200 chars max)
  └── timestamps

□ Add logic to auto-update room status [§5.5.2]:
  ├── Scheduled job to set "under_maintenance" at start
  └── Scheduled job to revert to "active" at end
```

### Step 2.3: Room CRUD (Admin/Director)
**Priority: CRITICAL** | **Ref:** §5.2.2, §5.5.1, §5.5.2, §5.5.3

```
□ Room List (Admin View) [§5.2.2]
  ├── GET /admin/rooms
  ├── Table with all rooms (including inactive)
  ├── Columns: name, capacity, floor, status, amenities, actions
  ├── Sorting by any column
  ├── Search by name
  ├── Filter by status
  └── Show count breakdown (X Active, Y Inactive, Z Maintenance)

□ Create Room [§5.5.1]
  ├── GET /admin/rooms/create
  ├── POST /admin/rooms
  ├── Required: name (unique, 50 chars), capacity (1-500), floor_location
  ├── Optional: description (500 chars), amenities, photos (max 5)
  └── Set status = "Active" by default

□ Edit Room [§5.5.1]
  ├── GET /admin/rooms/{room}/edit
  ├── PUT /admin/rooms/{room}
  └── Same validations as create

□ Room Status Management [§5.5.2]
  ├── PUT /admin/rooms/{room}/status
  ├── Change to Active/Inactive/Under Maintenance
  ├── For maintenance: require start/end datetime + optional reason
  ├── Confirmation dialogs
  └── Notify users with affected future bookings

□ Delete Room [§5.5.3]
  ├── DELETE /admin/rooms/{room}
  ├── Block if ANY bookings exist (past, present, future)
  ├── Show error with booking count, suggest deactivate
  └── If no bookings: require typing room name to confirm

□ Mockup pages:
  ├── rooms-list.html → resources/views/admin/rooms/index.blade.php
  ├── rooms-add.html → resources/views/admin/rooms/create.blade.php
  └── rooms-edit.html → resources/views/admin/rooms/edit.blade.php
```

### Step 2.4: Room Browsing (User View)
**Priority: HIGH** | **Ref:** §5.2.1, §5.4.1

```
□ Room Grid View [§5.2.1]
  ├── GET /rooms
  ├── Grid of room cards (Active rooms only for users)
  ├── Card: photo, name, capacity, floor, status badge, amenities icons
  ├── View Details button
  └── Status indicators: Available (green), Booked (orange), Maintenance (red)

□ Room Detail Page [§5.4.1]
  ├── GET /rooms/{room}
  ├── Header: name, status badge, capacity icon, location
  ├── Photo gallery with lightbox
  ├── Amenities list with icons
  ├── Availability calendar (week view) [§5.3.2]
  └── "Book This Room" button (disabled if under maintenance)

□ Mockup pages:
  ├── rooms-list.html (grid view) → resources/views/rooms/index.blade.php
  └── rooms-detail.html → resources/views/rooms/show.blade.php
```

### Step 2.5: Room Search & Filtering
**Priority: HIGH** | **Ref:** §5.3.1

```
□ Implement filters [§5.3.1]:
  ├── Capacity (minimum): 2, 4, 6, 8, 10, 12, 15, 20, 25+
  ├── Date & time availability (real-time check)
  ├── Amenities (multi-select, AND logic)
  └── Status filter (Admin only, defaults to Active)

□ Real-time availability check [§5.3.2]:
  ├── Query existing Confirmed bookings for conflicts
  ├── Exclude rooms under maintenance
  └── Return available rooms only

□ Filter UI [§5.3.1]:
  ├── Filter sidebar or top bar
  ├── Applied filters as removable chips
  ├── Clear all filters button
  ├── Show result count (e.g., "15 rooms found")
  └── Empty state with suggestions if no results
```

### Step 2.6: Audit Logging for Rooms
**Priority: MEDIUM** | **Ref:** §7.3.1

```
□ Log room events [§7.3.1 - Room Management Events]:
  ├── Room created (admin, room_id, room_name)
  ├── Room edited (admin, room_id, field changed, old → new)
  ├── Room status changed (admin, room_id, old status → new status)
  ├── Maintenance scheduled (admin, room_id, dates)
  └── Room deleted/deactivated (admin, room_id)
```

---

## Phase 3: Booking Management

**Goal:** Complete booking lifecycle - create, view, edit, cancel, and oversight  
**Req Reference:** Section 6 (Phase 3: Booking Management)  
**Dependency:** Phase 2 must be complete (rooms exist)

### Step 3.1: Database Schema - Bookings
**Priority: CRITICAL** | **Ref:** §6.2.1, §6.2.2

```
□ Create migrations:
  ├── bookings table [§6.2.1]
  │   ├── id, reference_number (unique, e.g., BK-2025-00001)
  │   ├── user_id (FK), room_id (FK)
  │   ├── booking_date, start_time, end_time
  │   ├── purpose (500 chars max)
  │   ├── status (enum: confirmed, cancelled, completed)
  │   ├── series_id (nullable FK, for recurring)
  │   ├── cancellation_reason
  │   ├── cancelled_by (FK), cancelled_at
  │   └── timestamps, soft_deletes
  │
  └── booking_series table [§6.2.2]
      ├── id, reference_number (e.g., BK-SERIES-2025-00001)
      ├── user_id, room_id
      ├── recurrence_type (enum: daily, weekly, monthly)
      ├── recurrence_pattern (JSON)
      ├── start_date, end_date
      └── timestamps

□ Create Booking model:
  ├── Relationships: user(), room(), series(), canceller()
  ├── Scopes: confirmed(), forUser(), forRoom(), upcoming(), past()
  ├── Accessors: duration, status_badge, is_editable, is_cancellable
  └── Static: generateReferenceNumber()
```

### Step 3.2: Booking Creation
**Priority: CRITICAL** | **Ref:** §6.2.1

```
□ One-time Booking [§6.2.1]
  ├── GET /bookings/create
  ├── POST /bookings
  ├── Form: room (dropdown), date, start_time, end_time, purpose
  ├── Validation [§6.2.1]:
  │   ├── Date not in past
  │   ├── Start < End
  │   ├── Duration: min 30 min, max 8 hours
  │   ├── Within operating hours (8AM-6PM)
  │   ├── Same day only (no overnight)
  │   ├── Room is Active (not maintenance)
  │   └── Room available (no Confirmed booking conflicts - first-come-first-served)
  ├── PIC: auto-set to logged-in user (Admin can select other user)
  ├── Generate reference number (BK-2025-XXXXX)
  ├── Set status = "Confirmed" immediately (auto-approval)
  └── Send email confirmation to user

□ Real-time availability check [§6.2.1]
  ├── AJAX: GET /api/rooms/{room}/availability?date=&start=&end=
  └── Show visual calendar/timeline while selecting
```

### Step 3.3: Recurring Bookings
**Priority: HIGH** | **Ref:** §6.2.2

```
□ Add recurrence options to booking form [§6.2.2]:
  ├── Recurrence type: Daily / Weekly / Monthly
  ├── Daily: every X days
  ├── Weekly: select weekdays (Mon-Fri)
  ├── Monthly: specific date of month
  ├── End: by date OR after X occurrences
  └── Max 1 year from start date

□ Recurring booking creation [§6.2.2]:
  ├── Validate all occurrence dates for availability (first-come-first-served)
  ├── If ANY conflict: show error with conflicting dates
  ├── Create series record with pattern
  ├── Create individual booking records linked by series_id
  ├── Set all occurrences status = "Confirmed" immediately (auto-approval)
  ├── Single confirmation email for entire series
  └── Display summary before confirmation (total occurrences)

□ Series management [§6.2.2]:
  ├── View: group occurrences together in My Bookings
  ├── Edit: changes apply to ENTIRE series (re-check availability)
  └── Cancel: cancels ENTIRE series (cannot cancel individual)
```

### Step 3.4: My Bookings (User)
**Priority: HIGH** | **Ref:** §6.3.1

```
□ List View [§6.3.1]
  ├── GET /my-bookings
  ├── Table: reference, date, time, room, status badge, purpose, actions
  ├── Filters: All, Upcoming, Past, By Room, Date Range
  ├── Sort: Date (upcoming first), Room, Status
  ├── Pagination: 20 per page
  └── Export to CSV/Excel/PDF

□ Calendar View [§6.3.1]
  ├── Personal calendar showing own bookings only
  ├── Day/Week/Month views
  ├── Color-coded: Pending (yellow), Confirmed (green), Cancelled (red), Completed (gray)
  └── Click booking → view details

□ View Booking Details
  ├── GET /my-bookings/{booking}
  └── Full info: reference, room, date/time, duration, purpose, status, timestamps

□ Mockup page:
  └── bookings-my.html → resources/views/bookings/my.blade.php
```

### Step 3.5: Edit Own Booking
**Priority: HIGH** | **Ref:** §6.3.2

```
□ Edit permissions [§6.3.2]:
  ├── Regular User: Upcoming bookings
  ├── Admin/Director: Any upcoming booking
  └── Status remains unchanged after edit (Confirmed stays Confirmed)

□ Edit form [§6.3.2]
  ├── GET /my-bookings/{booking}/edit
  ├── PUT /my-bookings/{booking}
  ├── Editable: date, start_time, end_time, room, purpose
  ├── Non-editable: PIC/booker, reference_number
  ├── Same validations as create
  └── For series: edit ALL occurrences (re-check availability)

□ Notifications [§6.3.2]:
  ├── User edit: confirmation email
  └── Admin edit: notify original booker of changes
```

### Step 3.6: Cancel Own Booking
**Priority: HIGH** | **Ref:** §6.3.3

```
□ Cancel permissions [§6.3.3]:
  ├── Regular User: Pending or Confirmed (own bookings)
  ├── Admin/Director: Any Pending/Confirmed booking
  └── Cannot cancel: Already Cancelled, Completed

□ Cancel process [§6.3.3]:
  ├── DELETE /my-bookings/{booking}
  ├── Require cancellation reason (max 500 chars)
  ├── Confirmation dialog
  ├── For series: cancel ALL occurrences
  ├── Update status = "Cancelled"
  ├── Record cancelled_by, cancelled_at
  ├── Free up room availability immediately
  └── Send cancellation email

□ Admin cancel on behalf [§6.3.3]:
  └── Notify original booker when admin cancels
```

### Step 3.7: All Bookings View (Admin/Director)
**Priority: HIGH** | **Ref:** §6.4.1

```
□ Admin Bookings List [§6.4.1]
  ├── GET /admin/bookings
  ├── ALL bookings from all users
  ├── Columns: reference, user (PIC), dept, date, time, room, status, purpose, actions
  ├── Filters: user, room, date range, status, department, booking type (one-time/recurring)
  ├── Sort: date, user, room, status, submission date
  ├── Pagination: 50 per page
  ├── Summary stats: total confirmed, cancelled, completed
  └── Export filtered list to CSV/Excel/PDF

□ Admin Edit Any Booking [§6.3.2]
  ├── Can edit ANY status booking
  ├── Status unchanged after edit (Confirmed stays Confirmed)
  └── Notify original booker of changes

□ Admin Cancel Any Booking [§6.3.3]
  ├── Require cancellation reason
  └── Notify original booker

□ Mockup page:
  └── bookings-all.html → resources/views/admin/bookings/index.blade.php
```

### Step 3.8: Auto-Approval System (First-Come-First-Served)
**Priority: CRITICAL** | **Ref:** §6.4.2

```
□ Auto-Approval Logic [§6.4.2]
  ├── Real-time availability check at booking submission
  ├── Database-level locking to prevent race conditions
  ├── First valid submission for a time slot wins
  ├── Set status = "Confirmed" immediately (no Pending state)
  ├── If conflict detected: display error with conflict details
  └── Suggest alternative times/rooms if slot unavailable

□ Conflict Prevention [§6.4.2]
  ├── Query existing Confirmed bookings for overlapping time slots
  ├── Check room maintenance schedules
  ├── Atomic transaction for booking creation
  └── Rollback if conflict detected mid-transaction

□ Admin Oversight [§6.4.2]
  ├── View All Bookings: see all confirmed bookings
  ├── Edit Any Booking: modify details if needed
  ├── Cancel Any Booking: cancel with reason (user notified)
  └── Generate Reports: monitor patterns and utilization

□ Note: No approval queue mockup needed - bookings are auto-confirmed
```

### Step 3.9: Booking Calendar View
**Priority: HIGH** | **Ref:** §6.2.3

```
□ Global Calendar [§6.2.3]
  ├── GET /calendar
  ├── Day/Week (default)/Month views
  ├── Time slots: 30-min increments, 8AM-6PM
  ├── For Regular Users: show own bookings only (others appear as "available")
  ├── For Admin/Director: show ALL bookings with user names
  ├── Color-coded: Pending (yellow), Confirmed (green), Cancelled (red strikethrough), Completed (gray)
  ├── Click empty slot → create booking (pre-fill room/date/time)
  └── Click booking → view details / take action

□ Calendar features [§6.2.3]:
  ├── Navigation: Previous, Next, Today buttons
  ├── Week display: Mon-Fri with date range
  ├── Filter by room
  ├── Current time marker (red line)
  ├── Recurring indicator icon
  └── Hover tooltip with booking preview

□ Mockup page:
  └── bookings-calendar.html → resources/views/bookings/calendar.blade.php
```

### Step 3.10: Booking Status Auto-Update
**Priority: MEDIUM** | **Ref:** §6.2.1

```
□ Scheduled Job for status updates:
  ├── Query Confirmed bookings where booking_date + end_time < now
  └── Update status = "Completed"

□ Run job hourly or daily via Laravel scheduler
```

### Step 3.11: Audit Logging for Bookings
**Priority: MEDIUM** | **Ref:** §7.3.1

```
□ Log booking events [§7.3.1 - Booking Events]:
  ├── Booking created (user, booking_id, room, date/time)
  ├── Booking edited (user, booking_id, old → new values)
  ├── Booking cancelled (user, booking_id, reason)
  └── Series events (series_id, occurrences count)
```

---

## Phase 4: Administrative Management

**Goal:** User management, reports, audit viewer, system config  
**Req Reference:** Section 7 (Phase 4: Administrative Management)  
**Dependency:** Phases 1-3 should be complete for full functionality

### Step 4.1: User Management (Director/SysAdmin)
**Priority: HIGH** | **Ref:** §7.2.1

```
□ User List [§7.2.1]
  ├── GET /admin/users
  ├── Table: staff_number, name, email, department, role (badge), status, last_login, created_at
  ├── Search by name, email, staff_number
  ├── Filter by role, department, status
  ├── Pagination
  └── User count summary (e.g., "Showing 25 of 150 users")

□ Create User [§7.2.1, §4.2.1]
  ├── GET /admin/users/create
  ├── POST /admin/users
  ├── Form: staff_number (unique), name, email (unique), department, phone, role
  ├── Generate temp password OR set initial password
  ├── Set must_change_password = true
  └── Send welcome email with credentials

□ Edit User [§7.2.1]
  ├── GET /admin/users/{user}/edit
  ├── PUT /admin/users/{user}
  ├── Editable: name, department, phone, role
  ├── Non-editable: staff_number, email
  └── Confirmation dialog for role changes

□ Deactivate User [§7.2.1]
  ├── PUT /admin/users/{user}/deactivate
  ├── Set status = "inactive"
  ├── User cannot login, cannot create/edit/cancel bookings
  ├── Existing bookings preserved
  └── Log action in audit trail

□ Reactivate User [§7.2.1]
  ├── PUT /admin/users/{user}/activate
  └── Set status = "active"

□ Delete User [§7.2.1]
  ├── DELETE /admin/users/{user}
  ├── Block if ANY bookings or audit activities exist
  ├── Show error with counts, suggest deactivate
  ├── If no activity: require typing full name to confirm
  └── Permanently remove from database

□ Reset Password [§7.2.1]
  ├── POST /admin/users/{user}/reset-password
  ├── Option 1: Generate temp password (display to admin)
  ├── Option 2: Send reset link via email
  └── Set must_change_password = true

□ Mockup pages:
  ├── users-list.html → resources/views/admin/users/index.blade.php
  └── users-edit.html → resources/views/admin/users/edit.blade.php
```

### Step 4.2: Audit Trail Viewer (Director/SysAdmin)
**Priority: HIGH** | **Ref:** §7.3.1

```
□ Audit Log List [§7.3.1]
  ├── GET /admin/audit-logs
  ├── Reverse chronological order (most recent first)
  ├── Columns: timestamp, actor name, action type, target entity, summary
  ├── Expandable row or modal for full details
  ├── Pagination: 50 per page
  └── Immutable: no edit/delete options

□ Filters [§7.3.1]:
  ├── Date range: presets (Today, Last 7 days, This Month, etc.) + custom
  ├── Actor: user selector dropdown
  ├── Action type: dropdown (Login, Booking Created, Room Edited, etc.)
  ├── Target entity type: Booking, Room, User, System Setting
  └── Full-text search across actor, details, target

□ Export [§7.3.1]
  ├── GET /admin/audit-logs/export?format=csv|xlsx
  ├── CSV and Excel formats
  ├── Include all filtered results
  ├── Filename: AuditLog_[StartDate]_to_[EndDate]_[Timestamp].csv
  └── Log the export action itself

□ Mockup page:
  └── system-audit-logs.html → resources/views/admin/audit-logs/index.blade.php
```

### Step 4.3: User Activity History
**Priority: MEDIUM** | **Ref:** §7.3.2

```
□ User Activity Page [§7.3.2]
  ├── GET /admin/users/{user}/activity
  ├── Timeline of user's actions (filtered from audit_logs)
  ├── Filter by date range, action type
  ├── Statistics:
  │   ├── Total bookings created
  │   ├── Bookings cancelled (count + %)
  │   ├── Cancellation rate
  │   ├── Last login date
  │   ├── Account age
  │   └── Most frequently booked room
  └── Export user activity report
```

### Step 4.4: System Configuration (SysAdmin only)
**Priority: HIGH** | **Ref:** §7.3.3

```
□ Create system_settings table:
  ├── id, key (unique), value, type (string, int, bool, json)
  └── timestamps

□ Settings page [§7.3.3]
  ├── GET /admin/settings
  ├── PUT /admin/settings

□ Section 1: Session & Security (Configurable) [§7.3.3]
  ├── Session timeout: 15-120 min (default 30)
  ├── Password reset token expiry: 5-60 min (default 30)
  ├── Login attempt limit: 3-10 (default 5)
  └── Lockout duration: 5-60 min (default 15)

□ Section 2: Password Policy (Read-Only) [§7.3.3]
  └── Display: 8 chars min, letters, numbers, symbols required

□ Section 3: Booking Rules (Read-Only) [§7.3.3]
  └── Display: Operating hours 8AM-6PM, 30min increments, 30min-8hr duration, etc.

□ Section 4: Notification Settings (Configurable) [§7.3.3]
  ├── Master toggle: Enable/Disable all emails
  └── Individual toggles per notification type

□ Section 5: Maintenance Mode (Configurable) [§7.3.3]
  ├── Toggle on/off
  ├── When ON: block non-admin logins
  ├── Show "System Under Maintenance" page
  └── Require confirmation before enabling

□ Mockup page:
  └── settings-system.html → resources/views/admin/settings/index.blade.php
```

### Step 4.5: Notification System
**Priority: HIGH** | **Ref:** §7.4.1, §7.4.2

```
□ Email Templates (Blade views) [§7.4.1]:
  ├── emails/welcome.blade.php
  ├── emails/password-reset.blade.php
  ├── emails/booking-submitted.blade.php
  ├── emails/booking-approved.blade.php
  ├── emails/booking-rejected.blade.php
  ├── emails/booking-cancelled.blade.php
  └── emails/booking-reminder.blade.php (24hr before)

□ Notification Service [§7.4.1]:
  ├── Queue emails via Laravel jobs (async)
  ├── Retry failed up to 3 times with backoff
  ├── Send within 5 minutes of triggering event
  └── Log all attempts in notification_logs table

□ User notification preferences [§7.4.1]:
  ├── Profile page section for preferences
  ├── Opt-out for: booking reminder, digest emails
  └── Cannot opt-out: password reset, booking status changes

□ Notification Log (SysAdmin) [§7.4.2]:
  ├── GET /admin/notifications
  ├── Table: timestamp, recipient, subject, type, status
  ├── Status badges: Sent (green), Queued (blue), Failed (red), Bounced (orange)
  ├── Filters: recipient, status, type, date range
  ├── "View Email Content" link
  └── "Resend" button for failed
```

### Step 4.6: Reporting System
**Priority: HIGH** | **Ref:** §7.5

```
□ Reports Hub [§7.5]
  ├── GET /admin/reports
  └── Cards linking to each report type

□ Common Report Features [§7.5]:
  ├── On-demand generation (no scheduled)
  ├── Date range: custom + presets (Today, Last 7 days, This Month, etc.)
  ├── On-screen preview
  ├── Export: PDF, Excel (.xlsx), CSV
  ├── Print button with print-friendly CSS
  └── Generate within 30 seconds

□ Daily Bookings Report [§7.5.1]
  ├── GET /admin/reports/daily
  ├── Single date selector (default: today)
  ├── Room-wise breakdown table
  ├── Columns: time, reference, booker, dept, purpose, status
  └── Summary: total, confirmed, pending, cancelled, occupancy rate

□ Monthly Bookings Report [§7.5.2]
  ├── GET /admin/reports/monthly
  ├── Month/year selector (default: this month)
  ├── Summary stats: total, cancelled %, most active day, popular room
  ├── Bookings by day chart (bar chart)
  └── Bookings by room chart (horizontal bar)

□ User Bookings Report [§7.5.3]
  ├── GET /admin/reports/user
  ├── User selector + date range
  ├── User info + booking stats (total, confirmed, cancelled %, avg duration)
  └── Booking list table

□ Room Utilization Report [§7.5.4]
  ├── GET /admin/reports/utilization
  ├── Date range selector (default: this month)
  ├── Per-room table: room, capacity, bookings, hours, available hours, utilization %, peak hour
  ├── Utilization chart (horizontal bar, color-coded)
  ├── Peak hours heatmap (hours vs rooms)
  └── Recommendations text (underutilized <30%, overutilized >80%)

□ Mockup pages:
  ├── reports-hub.html → resources/views/admin/reports/index.blade.php
  └── reports-view.html → resources/views/admin/reports/show.blade.php
```

---

## Phase 5: System Quality & Deployment

**Goal:** Polish, security hardening, and production deployment  
**Req Reference:** Section 8 (Phase 5: System Quality & Deployment)  
**Dependency:** All features complete

### Step 5.1: Responsive Design
**Priority: HIGH** | **Ref:** §8.2

```
□ Test all pages on [§8.2]:
  ├── Desktop (1920px, 1440px, 1280px)
  ├── Tablet (768px, 1024px)
  └── Mobile (375px, 414px)

□ Fix layout issues [§8.2]:
  ├── Collapsible sidebar on mobile
  ├── Touch-friendly buttons (min 44px target)
  ├── Readable text sizes
  ├── Scrollable/adaptive tables
  ├── Mobile-friendly forms
  └── No hover-only interactions (touch alternatives)

□ Note: Mockup is already responsive (Sneat template)
  └── Verify Blade conversion maintains responsiveness
```

### Step 5.2: Data Validation Hardening
**Priority: HIGH** | **Ref:** §8.3.1

```
□ Review all Form Requests [§8.3.1]:
  ├── Required fields validated
  ├── Type validation (email, numbers, dates)
  ├── Length constraints (max 500 for purpose, etc.)
  ├── Format validation
  ├── Uniqueness checks (staff_number, email, room name)
  └── Business rules (start < end, duration limits, etc.)

□ Client-side validation [§8.3.1]:
  ├── Real-time feedback as user types
  ├── Clear error messages near fields
  ├── Field highlighting (red border, error icon)
  └── Prevent form submission if invalid

□ Server-side validation [§8.3.1]:
  ├── Re-validate ALL data (never trust client)
  ├── Return detailed validation errors
  └── Log validation failures for monitoring
```

### Step 5.3: Error Handling
**Priority: HIGH** | **Ref:** §8.3.2

```
□ Custom error pages [§8.3.2]:
  ├── 403 Forbidden - "You don't have permission..."
  ├── 404 Not Found - "Page doesn't exist..."
  ├── 500 Server Error - "Something went wrong..."
  └── 503 Maintenance - "System under maintenance..."

□ Error handling patterns [§8.3.2]:
  ├── Validation: inline near field + summary at top
  ├── Network: toast with "Retry" button
  ├── Permission (403): modal or dedicated page
  ├── Not Found (404): dedicated page with nav links
  ├── Server Error (500): modal with reference number
  ├── Session Expired: modal → redirect to login
  └── Conflicts (double booking): modal with alternatives

□ Error logging:
  ├── Log technical details server-side
  ├── Never expose stack traces to users
  └── Include context (user, action, timestamp)
```

### Step 5.4: Security Hardening
**Priority: CRITICAL** | **Ref:** §8.4

```
□ XSS Prevention [§8.4.2]:
  ├── Use Blade {{ }} for all output (auto-escapes)
  ├── Sanitize user input before storing
  ├── Configure CSP headers
  └── Never use {!! !!} with user data

□ CSRF Protection [§8.4.2]:
  ├── @csrf on all forms
  ├── Laravel verifies automatically
  └── SameSite cookie attribute

□ SQL Injection [§8.4.2]:
  ├── Use Eloquent/Query Builder (parameterized)
  ├── Never concatenate user input in raw queries
  └── Least privilege DB user

□ Session Security [§8.4.2, §8.4.3]:
  ├── Secure + HttpOnly cookie flags
  ├── Session regeneration after login
  ├── Proper invalidation on logout
  └── 30-minute timeout

□ Brute Force Protection [§8.4.2]:
  ├── Login attempt limiting (configurable, default 5)
  ├── Temporary lockout (configurable, default 15 min)
  ├── Generic error messages ("Invalid credentials")
  └── Log all failed attempts with IP

□ Authorization (IDOR Prevention) [§8.4.2]:
  ├── Verify permissions on EVERY request
  ├── Use Laravel Policies/Gates
  ├── Check user owns resource before edit/delete
  └── Return 403 (not 404) for permission errors

□ HTTPS [§8.4.4]:
  ├── Force HTTPS in production
  ├── HSTS header
  └── Redirect HTTP → HTTPS

□ Password Security [§8.4.3]:
  └── Passwords hashed with bcrypt (Laravel default)
```

### Step 5.5: Performance Optimization
**Priority: MEDIUM** | **Ref:** §8.5

```
□ Database:
  ├── Add indexes: bookings(user_id, room_id, booking_date, status)
  ├── Add indexes: audit_logs(actor_id, event_type, created_at)
  ├── Eager loading to avoid N+1 (bookings with rooms, users)
  └── Query optimization for reports

□ Caching:
  ├── Cache system_settings (invalidate on update)
  ├── Cache amenities list
  └── Route/config/view caching in production

□ Assets:
  ├── Minify CSS/JS via Vite
  ├── Optimize uploaded images
  └── Browser caching headers
```

### Step 5.6: Testing
**Priority: HIGH** | **Ref:** §8.5

```
□ Feature Tests:
  ├── Authentication (login, logout, password reset)
  ├── Booking CRUD + approval workflow
  ├── Role-based access (test each role's permissions)
  ├── Recurring booking creation + series management
  └── User management (create, deactivate, delete rules)

□ Unit Tests:
  ├── Room availability checking logic
  ├── Reference number generation
  ├── Business rule validation (duration, operating hours)
  └── Role permission helpers

□ Manual Testing:
  ├── Full user journey per role
  ├── Edge cases (booking conflicts, maintenance overlap)
  ├── Cross-browser (Chrome, Firefox, Safari, Edge)
  └── Mobile device testing
```

### Step 5.7: Deployment Preparation
**Priority: CRITICAL** | **Ref:** §8.5.1, §8.5.2, §8.5.3

```
□ Production environment [§8.5.1]:
  ├── Web server (Nginx/Apache)
  ├── PostgreSQL database
  ├── Laravel Horizon or queue worker for emails
  ├── SSL certificate (Let's Encrypt or commercial)
  ├── Environment configuration (.env)
  └── Security hardening (firewall, permissions)

□ Data migration [§8.5.2]:
  ├── Create initial System Administrator account
  ├── Create initial Director account
  ├── Create initial Administrator account
  ├── Import meeting rooms with photos
  └── Configure system settings

□ Go-live checklist [§8.5.3]:
  ├── Database backup plan
  ├── Rollback procedure documented
  ├── Error monitoring setup
  ├── Schedule deployment during off-peak
  ├── User communication (URL, instructions, support contact)
  └── Post-deployment monitoring plan
```

---

## Development Checklist

### Phase 1 Completion Criteria (Ref: §4)
- [ ] Users can be created by admin (§4.2.1)
- [ ] Login/logout works with session timeout (§4.2.2)
- [ ] Password reset flow works end-to-end (§4.2.3)
- [ ] First login forces password change (§4.2.1)
- [ ] Role-based menu visibility works (§4.4)
- [ ] User Dashboard displays correct data (§4.3.2)
- [ ] Admin Dashboard displays correct data (§4.3.1)
- [ ] User profile view/edit works (§4.2.4)
- [ ] Audit trail logs auth events (§7.3.1)

### Phase 2 Completion Criteria (Ref: §5)
- [ ] Admins can create rooms (§5.5.1)
- [ ] Admins can edit rooms (§5.5.1)
- [ ] Admins can change room status (§5.5.2)
- [ ] Admins can delete rooms (with constraints) (§5.5.3)
- [ ] Users can browse rooms in grid view (§5.2.1)
- [ ] Users can search/filter rooms (§5.3.1)
- [ ] Room detail page shows availability calendar (§5.4.1, §5.3.2)
- [ ] Maintenance scheduling works (§5.5.2)
- [ ] Audit trail logs room events (§7.3.1)

### Phase 3 Completion Criteria (Ref: §6)
- [ ] Users can create one-time bookings (§6.2.1)
- [ ] Users can create recurring bookings (§6.2.2)
- [ ] Booking validation rules enforced (§6.2.1)
- [ ] Users can view My Bookings (§6.3.1)
- [ ] Users can edit own pending bookings (§6.3.2)
- [ ] Users can cancel own bookings (§6.3.3)
- [ ] Admins can view all bookings (§6.4.1)
- [ ] Admins can approve/reject bookings (§6.4.2)
- [ ] Series approval works (all occurrences) (§6.4.2)
- [ ] Calendar view works (Day/Week/Month) (§6.2.3)
- [ ] Email notifications send successfully (§7.4.1)
- [ ] Booking status auto-completes (§6.2.1)
- [ ] Audit trail logs booking events (§7.3.1)

### Phase 4 Completion Criteria (Ref: §7)
- [ ] Directors/SysAdmins can create users (§7.2.1)
- [ ] Directors/SysAdmins can edit users (§7.2.1)
- [ ] Directors/SysAdmins can deactivate users (§7.2.1)
- [ ] Directors/SysAdmins can delete users (with constraints) (§7.2.1)
- [ ] Audit trail viewer works with filters (§7.3.1)
- [ ] Audit logs export to CSV/Excel (§7.3.1)
- [ ] User activity history viewable (§7.3.2)
- [ ] Daily Bookings Report works (§7.5.1)
- [ ] Monthly Bookings Report works (§7.5.2)
- [ ] User Bookings Report works (§7.5.3)
- [ ] Room Utilization Report works (§7.5.4)
- [ ] Reports export to PDF/Excel/CSV (§7.5)
- [ ] System settings configurable (§7.3.3)
- [ ] Notification toggles work (§7.3.3)
- [ ] Maintenance mode works (§7.3.3)
- [ ] Notification log viewable (§7.4.2)

### Phase 5 Completion Criteria (Ref: §8)
- [ ] All pages responsive on mobile (§8.2)
- [ ] Client-side validation in place (§8.3.1)
- [ ] Server-side validation in place (§8.3.1)
- [ ] Custom error pages (403, 404, 500, 503) (§8.3.2)
- [ ] XSS protection verified (§8.4.2)
- [ ] CSRF protection verified (§8.4.2)
- [ ] SQL injection protection verified (§8.4.2)
- [ ] Session security configured (§8.4.3)
- [ ] Brute force protection working (§8.4.2)
- [ ] HTTPS enforced (§8.4.4)
- [ ] Feature tests pass
- [ ] Production environment ready (§8.5.1)
- [ ] Initial data migrated (§8.5.2)
- [ ] Go-live successful (§8.5.3)

---

## Mockup to Blade Mapping

| Mockup Page | Blade View Path | Phase |
|-------------|-----------------|-------|
| `auth-login.html` | `views/auth/login.blade.php` | 1 |
| `auth-forgot-password.html` | `views/auth/forgot-password.blade.php` | 1 |
| `auth-register.html` | `views/admin/users/create.blade.php` | 4 |
| `layout.html` | `views/layouts/app.blade.php` | 1 |
| `dashboard-user.html` | `views/dashboard/user.blade.php` | 1 |
| `dashboard-admin.html` | `views/dashboard/admin.blade.php` | 1 |
| `rooms-list.html` | `views/rooms/index.blade.php` (user) | 2 |
| `rooms-list.html` | `views/admin/rooms/index.blade.php` (admin) | 2 |
| `rooms-detail.html` | `views/rooms/show.blade.php` | 2 |
| `rooms-add.html` | `views/admin/rooms/create.blade.php` | 2 |
| `rooms-edit.html` | `views/admin/rooms/edit.blade.php` | 2 |
| `bookings-calendar.html` | `views/bookings/calendar.blade.php` | 3 |
| `bookings-my.html` | `views/bookings/my.blade.php` | 3 |
| `bookings-all.html` | `views/admin/bookings/index.blade.php` | 3 |
| `bookings-approval.html` | `views/admin/bookings/approvals.blade.php` | 3 |
| `users-list.html` | `views/admin/users/index.blade.php` | 4 |
| `users-edit.html` | `views/admin/users/edit.blade.php` | 4 |
| `reports-hub.html` | `views/admin/reports/index.blade.php` | 4 |
| `reports-view.html` | `views/admin/reports/show.blade.php` | 4 |
| `settings-system.html` | `views/admin/settings/index.blade.php` | 4 |
| `system-audit-logs.html` | `views/admin/audit-logs/index.blade.php` | 4 |

---

## Estimated Timeline (Reference)

| Phase | Description | Duration | Cumulative |
|-------|-------------|----------|------------|
| Phase 1 | Foundation & Core Layout | 2-3 weeks | 2-3 weeks |
| Phase 2 | Meeting Rooms Management | 1-2 weeks | 3-5 weeks |
| Phase 3 | Booking Management | 2-3 weeks | 5-8 weeks |
| Phase 4 | Administrative Management | 2-3 weeks | 7-11 weeks |
| Phase 5 | System Quality & Deployment | 1-2 weeks | 8-13 weeks |

*Timeline varies based on team size and experience. Single developer estimate: 10-13 weeks.*

---

## Quick Reference: Requirement Document Sections

| Section | Content |
|---------|---------|
| §1 | Executive Summary |
| §2 | System Overview |
| §3 | User Roles and Responsibilities |
| §4 | Phase 1: Foundation & Core Layout |
| §5 | Phase 2: Meeting Rooms Management |
| §6 | Phase 3: Booking Management |
| §7 | Phase 4: Administrative Management |
| §8 | Phase 5: System Quality & Deployment |
| §9 | Glossary |

---

**END OF WORKFLOW DOCUMENT**

