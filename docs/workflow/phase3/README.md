# Phase 3: Booking Management - Implementation Plan

**Reference Documents:**
- [Requirements Document](../../requirement/requirements-document.md) - Section 6
- [Development Workflow](../../requirement/workflow.md) - Phase 3 (Steps 3.1-3.11)
- [Mockup Reference](../../../mrbs-mock-up/docs/feature-dev-v2.md)

**Created:** December 10, 2025  
**Last Updated:** December 10, 2025  
**Status:** TODO  
**Laravel Version:** 12.x  
**Database:** PostgreSQL

---

## Phase Objective

Implement the complete booking lifecycle enabling users to reserve meeting rooms with instant confirmation on a first-come-first-served basis:

- Booking database schema (bookings, booking_series tables)
- One-time booking creation with auto-approval
- Recurring booking support (daily, weekly, monthly patterns)
- Personal booking management (My Bookings - view, edit, cancel)
- Administrative booking oversight (All Bookings - view, edit, cancel)
- Global booking calendar with FullCalendar (day/week/month views)
- Automatic status update (Confirmed → Completed)
- Audit logging for booking events

---

## ⚠️ Design Decision: Auto-Approval (No Pending Status)

This phase implements **auto-approval** with first-come-first-served booking:

| Aspect | Implementation |
|--------|----------------|
| **Booking Status on Create** | Immediately set to "Confirmed" (no Pending) |
| **Approval Process** | None - first valid submission wins the slot |
| **Conflict Prevention** | Database-level locking + real-time availability check |
| **Status Values** | `confirmed`, `cancelled`, `completed` (no `pending`, `rejected`) |
| **Approval Queue** | Not implemented (bookings-approval.html excluded) |

**See:** [Step 1.8 - Auto-Approval Refactor](../phase1/step-1.8-auto-approval-refactor.md) for background.

---

## Dependencies

**Requires Phase 1 Complete:**
- ✅ User authentication system
- ✅ Role-based authorization (middleware, Gates)
- ✅ Global layout with sidebar navigation
- ✅ Audit trail foundation (AuditLog model, AuditService)

**Requires Phase 2 Complete:**
- ✅ Room model with relationships
- ✅ Room availability checking (isAvailable method)
- ✅ FullCalendar integration for availability display
- ✅ Amenities and room images

---

## Step Files

| Step | File | Description | Priority | Status |
|------|------|-------------|----------|--------|
| 3.1 | [step-3.1-database-schema.md](./step-3.1-database-schema.md) | Bookings, booking_series tables | CRITICAL | COMPLETE |
| 3.2 | [step-3.2-booking-creation.md](./step-3.2-booking-creation.md) | One-time booking with auto-approval | CRITICAL | TODO |
| 3.3 | [step-3.3-recurring-bookings.md](./step-3.3-recurring-bookings.md) | Daily/weekly/monthly patterns | HIGH | TODO |
| 3.4 | [step-3.4-my-bookings.md](./step-3.4-my-bookings.md) | Personal booking list & calendar view | HIGH | TODO |
| 3.5 | [step-3.5-edit-booking.md](./step-3.5-edit-booking.md) | Edit own/any booking (role-based) | HIGH | TODO |
| 3.6 | [step-3.6-cancel-booking.md](./step-3.6-cancel-booking.md) | Cancel own/any booking with reason | HIGH | TODO |
| 3.7 | [step-3.7-all-bookings.md](./step-3.7-all-bookings.md) | Admin view of all bookings | HIGH | TODO |
| 3.8 | [step-3.8-auto-approval-system.md](./step-3.8-auto-approval-system.md) | Conflict prevention, database locking | CRITICAL | TODO |
| 3.9 | [step-3.9-booking-calendar.md](./step-3.9-booking-calendar.md) | Global calendar (day/week/month) | HIGH | TODO |
| 3.10 | [step-3.10-status-auto-update.md](./step-3.10-status-auto-update.md) | Scheduled job for Completed status | MEDIUM | TODO |
| 3.11 | [step-3.11-audit-logging.md](./step-3.11-audit-logging.md) | Booking event logging | MEDIUM | TODO |

---

## Dependency Graph

```
Step 3.1 (Database Schema - Bookings)
    │
    ├──► Step 3.2 (Booking Creation) ──► Step 3.8 (Auto-Approval System)
    │         │
    │         └──► Step 3.3 (Recurring Bookings)
    │
    ├──► Step 3.4 (My Bookings)
    │         │
    │         ├──► Step 3.5 (Edit Booking)
    │         │
    │         └──► Step 3.6 (Cancel Booking)
    │
    ├──► Step 3.7 (All Bookings - Admin)
    │
    ├──► Step 3.9 (Booking Calendar)
    │
    ├──► Step 3.10 (Status Auto-Update)
    │
    └──► Step 3.11 (Audit Logging)
```

**Execution Order:**
1. Step 3.1 - Database Schema (no Phase 3 dependencies)
2. Step 3.2 - Booking Creation (depends on 3.1)
3. Step 3.8 - Auto-Approval System (depends on 3.2, integrated into create flow)
4. Step 3.3 - Recurring Bookings (depends on 3.2)
5. Step 3.4 - My Bookings (depends on 3.1)
6. Step 3.5 - Edit Booking (depends on 3.4)
7. Step 3.6 - Cancel Booking (depends on 3.4)
8. Step 3.7 - All Bookings Admin (depends on 3.1)
9. Step 3.9 - Booking Calendar (depends on 3.1, 3.2)
10. Step 3.10 - Status Auto-Update (depends on 3.1)
11. Step 3.11 - Audit Logging (depends on Phase 1 AuditService)

---

## Mockup Files to Convert

| Mockup File | Target Blade File | Step |
|-------------|-------------------|------|
| `mrbs-mock-up/pages/bookings-my.html` | `resources/views/bookings/my.blade.php` | 3.4 |
| `mrbs-mock-up/pages/bookings-all.html` | `resources/views/admin/bookings/index.blade.php` | 3.7 |
| `mrbs-mock-up/pages/bookings-calendar.html` | `resources/views/bookings/calendar.blade.php` | 3.9 |
| ~~`mrbs-mock-up/pages/bookings-approval.html`~~ | ~~Not used~~ | ~~Excluded~~ |

**Note:** Booking creation form will be a modal or dedicated page created from scratch (no direct mockup).

---

## Database Tables (Phase 3)

| Table | Purpose | Step |
|-------|---------|------|
| `bookings` | Individual booking records | 3.1 |
| `booking_series` | Recurring booking series metadata | 3.1 |

---

## Booking Status Flow (Auto-Approval)

```
User Submits Booking
        │
        ▼
   ┌─────────────┐
   │ Availability │
   │    Check     │
   └─────────────┘
        │
   ┌────┴────┐
   │         │
Available  Conflict
   │         │
   ▼         ▼
┌──────────┐  ┌──────────────┐
│ CONFIRMED │  │ Show Error + │
│ (instant) │  │ Alternatives │
└──────────┘  └──────────────┘
   │
   │ (time passes, booking ends)
   ▼
┌──────────┐
│ COMPLETED │
└──────────┘

User/Admin Cancels
        │
        ▼
┌──────────┐
│ CANCELLED │
└──────────┘
```

---

## Technical Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Status Values | `confirmed`, `cancelled`, `completed` | No pending/rejected per auto-approval |
| Reference Number Format | `BK-YYYY-NNNNN` | e.g., BK-2025-00001 |
| Series Reference Format | `BK-SERIES-YYYY-NNNNN` | For recurring bookings |
| Calendar Library | FullCalendar.js | Already used in Phase 2 |
| Conflict Prevention | Database transaction + locking | Prevents race conditions |
| Operating Hours | 8:00 AM - 6:00 PM (hardcoded) | Per requirements |
| Time Increments | 30 minutes | Per requirements |
| Duration Limits | Min: 30 min, Max: 8 hours | Per requirements |
| Email Notifications | Stubbed for Phase 4 | Full implementation in Phase 4 |
| Export Functionality | Deferred to Phase 4 | Part of reporting system |

---

## Routes Overview

### User Routes (Authenticated)
```
GET  /bookings/create              → BookingController@create
POST /bookings                     → BookingController@store
GET  /my-bookings                  → BookingController@myBookings
GET  /my-bookings/{booking}        → BookingController@show
GET  /my-bookings/{booking}/edit   → BookingController@edit
PUT  /my-bookings/{booking}        → BookingController@update
DELETE /my-bookings/{booking}      → BookingController@destroy (cancel)
GET  /calendar                     → BookingController@calendar
```

### Admin Routes (Admin/Director)
```
GET  /admin/bookings               → Admin\BookingController@index
GET  /admin/bookings/{booking}     → Admin\BookingController@show
GET  /admin/bookings/{booking}/edit → Admin\BookingController@edit
PUT  /admin/bookings/{booking}     → Admin\BookingController@update
DELETE /admin/bookings/{booking}   → Admin\BookingController@destroy
```

### API Routes (AJAX)
```
GET  /api/rooms/{room}/availability → API\RoomController@availability
GET  /api/calendar/events          → API\CalendarController@events
POST /api/bookings/check-availability → API\BookingController@checkAvailability
```

---

## Form Validation Rules

| Field | Rules |
|-------|-------|
| `room_id` | required, exists:rooms,id, room must be active |
| `booking_date` | required, date, not in past |
| `start_time` | required, time format, within operating hours (08:00-18:00) |
| `end_time` | required, time format, after start_time, within operating hours |
| `purpose` | required, string, max:500 |
| Duration | 30 min minimum, 8 hours maximum |
| Same-day only | start and end must be same date |

---

## Test Accounts (Development)

| Email | Password | Role | Booking Permission |
|-------|----------|------|-------------------|
| sysadmin@mrbs.local | password123 | System Admin | Own bookings only |
| director@mrbs.local | password123 | Director | All bookings |
| admin@mrbs.local | password123 | Administrator | All bookings |
| user@mrbs.local | password123 | Regular User | Own bookings only |

---

## Getting Started

1. Ensure Phase 1 and Phase 2 are complete and all tests pass
2. Read each step file in order
3. Complete all tasks within a step before moving to the next
4. Run tests after completing each step
5. Check acceptance criteria before marking a step as complete

**Start Here:** [Step 3.1 - Database Schema](./step-3.1-database-schema.md)

---

## Changelog

| Date | Change |
|------|--------|
| Dec 10, 2025 | Initial Phase 3 plan created |
