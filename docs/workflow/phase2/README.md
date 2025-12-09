# Phase 2: Meeting Rooms Management - Implementation Plan

**Reference Documents:**
- [Requirements Document](../../requirement/requirements-document.md) - Section 5
- [Development Workflow](../../requirement/workflow.md) - Phase 2 (Steps 2.1-2.6)
- [Mockup Reference](../../../mrbs-mock-up/docs/feature-dev-v2.md)

**Created:** December 10, 2025  
**Last Updated:** December 10, 2025  
**Status:** TODO  
**Laravel Version:** 12.x  
**Database:** PostgreSQL

---

## Phase Objective

Enable users to discover and view meeting rooms, and provide administrators with tools to manage the complete room inventory including:
- Room database schema (rooms, amenities, images, maintenance schedules)
- Room CRUD operations for Admin/Director
- Room browsing for all users (grid view with cards)
- Room detail page with availability calendar (FullCalendar)
- Advanced search and filtering (capacity, amenities, date/time availability)
- Audit logging for room management events

---

## Dependencies

**Requires Phase 1 Complete:**
- ✅ User authentication system
- ✅ Role-based authorization (middleware, Gates)
- ✅ Global layout with sidebar navigation
- ✅ Audit trail foundation (AuditLog model, AuditService)

---

## Step Files

| Step | File | Description | Priority | Status |
|------|------|-------------|----------|--------|
| 2.1 | [step-2.1-database-schema.md](./step-2.1-database-schema.md) | Rooms, amenities, images tables | CRITICAL | COMPLETED |
| 2.2 | [step-2.2-room-maintenance.md](./step-2.2-room-maintenance.md) | Maintenance schedules, auto-status | MEDIUM | COMPLETED |
| 2.3 | [step-2.3-room-crud.md](./step-2.3-room-crud.md) | Admin room management (add/edit/delete) | CRITICAL | COMPLETED |
| 2.4 | [step-2.4-room-browsing.md](./step-2.4-room-browsing.md) | User room grid, detail page, calendar | HIGH | TODO |
| 2.5 | [step-2.5-room-search-filtering.md](./step-2.5-room-search-filtering.md) | Search, filters, availability check | HIGH | TODO |
| 2.6 | [step-2.6-audit-logging.md](./step-2.6-audit-logging.md) | Room event logging | MEDIUM | TODO |

---

## Dependency Graph

```
Step 2.1 (Database Schema - Rooms)
    │
    ├──► Step 2.2 (Maintenance Scheduling)
    │
    ├──► Step 2.3 (Room CRUD - Admin) ──► Step 2.6 (Audit Logging)
    │
    └──► Step 2.4 (Room Browsing - User)
              │
              └──► Step 2.5 (Search & Filtering)
```

**Execution Order:**
1. Step 2.1 - Database Schema (no Phase 2 dependencies)
2. Step 2.2 - Maintenance Scheduling (depends on 2.1)
3. Step 2.3 - Room CRUD Admin (depends on 2.1)
4. Step 2.4 - Room Browsing User (depends on 2.1, 2.3 for seeded data)
5. Step 2.5 - Search & Filtering (depends on 2.4)
6. Step 2.6 - Audit Logging (depends on 2.3, Phase 1 AuditService)

---

## Mockup Files to Convert

| Mockup File | Target Blade File | Step |
|-------------|-------------------|------|
| `mrbs-mock-up/pages/rooms-list.html` | `resources/views/rooms/index.blade.php` (user grid) | 2.4 |
| `mrbs-mock-up/pages/rooms-list.html` | `resources/views/admin/rooms/index.blade.php` (admin table) | 2.3 |
| `mrbs-mock-up/pages/rooms-detail.html` | `resources/views/rooms/show.blade.php` | 2.4 |
| `mrbs-mock-up/pages/rooms-add.html` | `resources/views/admin/rooms/create.blade.php` | 2.3 |
| `mrbs-mock-up/pages/rooms-edit.html` | `resources/views/admin/rooms/edit.blade.php` | 2.3 |

---

## Database Tables (Phase 2)

| Table | Purpose | Step |
|-------|---------|------|
| `rooms` | Meeting room records | 2.1 |
| `amenities` | Available room equipment/features | 2.1 |
| `amenity_room` | Pivot table for room-amenity relationship | 2.1 |
| `room_images` | Room photos (max 5 per room) | 2.1 |
| `room_maintenance_schedules` | Planned maintenance periods | 2.2 |

---

## Technical Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Image Storage | Local (`storage/app/public/rooms/`) | Simpler setup, symbolic link to public |
| Availability Calendar | FullCalendar.js | Already in mockup assets, feature-rich |
| Filter Approach | AJAX-based | User triggers filter, no WebSocket needed |
| Amenity Icons | Boxicons (existing) | Consistent with theme |
| Deletion Policy | Block if ANY bookings | Data integrity, audit compliance |
| Scheduler | Laravel Scheduler (cron) | Simple, sufficient for maintenance status |

---

## Default Amenities

Seed these amenities with Boxicons icons:

| Amenity | Icon |
|---------|------|
| Projector | bx-projector |
| Whiteboard | bx-chalkboard |
| Video Conferencing | bx-video |
| Teleconferencing Phone | bx-phone |
| Computer/Monitor | bx-desktop |
| Flip Chart | bx-note |
| Air Conditioning | bx-wind |
| Natural Light/Windows | bx-sun |

---

## Routes Overview

### Public Routes (Authenticated Users)
```
GET  /rooms                         → RoomController@index (grid view)
GET  /rooms/{room}                  → RoomController@show (detail page)
GET  /api/rooms/{room}/availability → RoomController@availability (AJAX)
```

### Admin Routes (Admin/Director)
```
GET    /admin/rooms                 → Admin\RoomController@index (table view)
GET    /admin/rooms/create          → Admin\RoomController@create
POST   /admin/rooms                 → Admin\RoomController@store
GET    /admin/rooms/{room}/edit     → Admin\RoomController@edit
PUT    /admin/rooms/{room}          → Admin\RoomController@update
DELETE /admin/rooms/{room}          → Admin\RoomController@destroy
PUT    /admin/rooms/{room}/status   → Admin\RoomController@updateStatus
```

---

## Getting Started

1. Ensure Phase 1 is complete and all tests pass
2. Read each step file in order
3. Complete all tasks within a step before moving to the next
4. Run tests after completing each step
5. Check acceptance criteria before marking a step as complete

**Start Here:** [Step 2.1 - Database Schema](./step-2.1-database-schema.md)

---

## Changelog

| Date | Change |
|------|--------|
| Dec 10, 2025 | Initial Phase 2 plan created |
