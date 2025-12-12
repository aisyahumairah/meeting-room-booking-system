# Phase 4: Administrative Management - Implementation Plan

**Reference Documents:**
- [Requirements Document](../../requirement/requirements-document.md) - Section 7
- [Development Workflow](../../requirement/workflow.md) - Phase 4 (Steps 4.1-4.6)
- [Mockup Reference](../../../mrbs-mock-up/docs/feature-dev-v2.md)

**Created:** December 13, 2025  
**Last Updated:** December 13, 2025  
**Status:** TODO  
**Laravel Version:** 12.x  
**Database:** PostgreSQL

---

## Phase Objective

Implement comprehensive backend management tools for system oversight, governance, and analytics:

- Full user management (CRUD, role assignment, password reset)
- Audit trail viewer with filtering, search, and export
- User activity history with statistics
- System configuration management (key-value settings)
- Email notification system with templates and logging
- Reporting suite (Daily, Monthly, User, Utilization reports)

---

## ⚠️ Phase 3 Changes Impact

This phase takes into account the **auto-approval** booking system implemented in Phase 3:

| Aspect | Impact on Phase 4 |
|--------|-------------------|
| **No Pending/Rejected Status** | Report queries use only `confirmed`, `cancelled`, `completed` |
| **No Approval Events** | Audit trail excludes `booking_approved`, `booking_rejected` events |
| **Booking Status Flow** | Created → Confirmed (instant) → Completed/Cancelled |
| **Email Notifications** | No approval request emails, only confirmation emails |

---

## Dependencies

**Requires Phase 1 Complete:**
- ✅ User authentication system
- ✅ Role-based authorization (middleware, Gates)
- ✅ Global layout with sidebar navigation
- ✅ Audit trail foundation (AuditLog model, AuditService)

**Requires Phase 2 Complete:**
- ✅ Room model with relationships
- ✅ Room images and amenities

**Requires Phase 3 Complete:**
- ✅ Booking model with relationships and scopes
- ✅ Booking creation and management
- ✅ Auto-approval system (first-come-first-served)

---

## Step Files

| Step | File | Description | Priority | Status |
|------|------|-------------|----------|--------|
| 4.1 | [step-4.1-user-management.md](./step-4.1-user-management.md) | User CRUD, role assignment, deactivate/reset | HIGH | TODO |
| 4.2 | [step-4.2-audit-trail-viewer.md](./step-4.2-audit-trail-viewer.md) | Audit log viewer with filters and export | HIGH | TODO |
| 4.3 | [step-4.3-user-activity-history.md](./step-4.3-user-activity-history.md) | Per-user activity timeline with stats | MEDIUM | TODO |
| 4.4 | [step-4.4-system-configuration.md](./step-4.4-system-configuration.md) | System settings (key-value), maintenance mode | HIGH | TODO |
| 4.5 | [step-4.5-notification-system.md](./step-4.5-notification-system.md) | Email templates, queue, preferences, logging | HIGH | TODO |
| 4.6 | [step-4.6-reporting-system.md](./step-4.6-reporting-system.md) | Daily, Monthly, User, Utilization reports | HIGH | TODO |

---

## Dependency Graph

```
Step 4.1 (User Management)
    │
    └──► Step 4.3 (User Activity History)

Step 4.2 (Audit Trail Viewer) ──────────────┐
                                             │
                                             ├──► Step 4.3 (depends on both)
                                             │
Step 4.4 (System Configuration) ─────────────┤
                                             │
                                             └──► Step 4.5 (Notification System)
                                                       │
                                                       └──► Step 4.6 (Reporting System)
```

**Execution Order:**
1. Step 4.1 - User Management (foundation for user admin)
2. Step 4.2 - Audit Trail Viewer (builds on existing AuditLog)
3. Step 4.3 - User Activity History (depends on 4.1 + 4.2)
4. Step 4.4 - System Configuration (settings foundation)
5. Step 4.5 - Notification System (depends on 4.4 for settings)
6. Step 4.6 - Reporting System (can run parallel with 4.5)

---

## Mockup Files to Convert

| Mockup File | Target Blade File | Step |
|-------------|-------------------|------|
| `mrbs-mock-up/pages/users-list.html` | `resources/views/admin/users/index.blade.php` | 4.1 |
| `mrbs-mock-up/pages/users-edit.html` | `resources/views/admin/users/edit.blade.php` | 4.1 |
| `mrbs-mock-up/pages/system-audit-logs.html` | `resources/views/admin/audit-logs/index.blade.php` | 4.2 |
| `mrbs-mock-up/pages/settings-system.html` | `resources/views/admin/settings/index.blade.php` | 4.4 |
| `mrbs-mock-up/pages/reports-hub.html` | `resources/views/admin/reports/index.blade.php` | 4.6 |
| `mrbs-mock-up/pages/reports-view.html` | `resources/views/admin/reports/show.blade.php` | 4.6 |

---

## Database Tables (Phase 4)

| Table | Purpose | Step |
|-------|---------|------|
| `system_settings` | Key-value configuration storage | 4.4 |
| `notification_logs` | Email/notification delivery tracking | 4.5 |
| `user_notification_preferences` | Per-user notification opt-outs | 4.5 |

---

## Required Packages

| Package | Purpose | Command |
|---------|---------|---------|
| `maatwebsite/excel` | Excel/CSV export | `composer require maatwebsite/excel` |
| `barryvdh/laravel-dompdf` | PDF export | `composer require barryvdh/laravel-dompdf` |

---

## Routes Overview

### User Management Routes (Director/SysAdmin)
```
GET    /admin/users                    → Admin\UserController@index
GET    /admin/users/create             → Admin\UserController@create
POST   /admin/users                    → Admin\UserController@store
GET    /admin/users/{user}/edit        → Admin\UserController@edit
PUT    /admin/users/{user}             → Admin\UserController@update
DELETE /admin/users/{user}             → Admin\UserController@destroy
PUT    /admin/users/{user}/deactivate  → Admin\UserController@deactivate
PUT    /admin/users/{user}/activate    → Admin\UserController@activate
POST   /admin/users/{user}/reset-password → Admin\UserController@resetPassword
GET    /admin/users/{user}/activity    → Admin\UserController@activity
```

### Audit Trail Routes (Director/SysAdmin)
```
GET    /admin/audit-logs               → Admin\AuditLogController@index
GET    /admin/audit-logs/export        → Admin\AuditLogController@export
```

### System Settings Routes (SysAdmin only)
```
GET    /admin/settings                 → Admin\SettingsController@index
PUT    /admin/settings                 → Admin\SettingsController@update
```

### Notification Routes (SysAdmin)
```
GET    /admin/notifications            → Admin\NotificationController@index
POST   /admin/notifications/{id}/resend → Admin\NotificationController@resend
```

### Reporting Routes (Admin/Director/SysAdmin)
```
GET    /admin/reports                  → Admin\ReportController@index
GET    /admin/reports/daily            → Admin\ReportController@daily
GET    /admin/reports/monthly          → Admin\ReportController@monthly
GET    /admin/reports/user             → Admin\ReportController@user
GET    /admin/reports/utilization      → Admin\ReportController@utilization
GET    /admin/reports/{type}/export    → Admin\ReportController@export
```

---

## Technical Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Settings Storage | `system_settings` key-value table | Allows dynamic config without .env |
| Email Queue | Laravel Jobs (async) | Non-blocking email sending |
| Report Generation | On-demand only | No scheduled reports per requirements |
| Export Formats | CSV, XLSX, PDF | Using maatwebsite/excel + dompdf |
| Notification Retention | 90 days | Balance storage vs history needs |
| Audit Log Retention | 2 years (immutable) | Per requirements §7.3.1 |

---

## Audit Event Types (Phase 4 Additions)

| Event Type | Description |
|------------|-------------|
| `user.created` | New user account created |
| `user.updated` | User details modified |
| `user.role_changed` | User role upgraded/downgraded |
| `user.deactivated` | User account deactivated |
| `user.reactivated` | User account reactivated |
| `user.deleted` | User account permanently deleted |
| `user.password_reset_by_admin` | Admin reset user's password |
| `settings.updated` | System setting changed |
| `settings.maintenance_mode_toggled` | Maintenance mode on/off |
| `report.generated` | Report generated and/or exported |
| `audit_log.exported` | Audit log exported |

**Note:** The following approval events are **NOT implemented** due to auto-approval:
- ~~`booking.approved`~~
- ~~`booking.rejected`~~
- ~~`series.approved`~~
- ~~`series.rejected`~~

---

## Test Accounts (Development)

| Email | Password | Role | Phase 4 Permissions |
|-------|----------|------|---------------------|
| sysadmin@mrbs.local | password123 | System Admin | All (Users, Settings, Reports, Audit) |
| director@mrbs.local | password123 | Director | Users, Reports, Audit (no Settings) |
| admin@mrbs.local | password123 | Administrator | Reports only |
| user@mrbs.local | password123 | Regular User | None (no admin access) |

---

## Getting Started

1. Ensure **Phase 1, Phase 2, and Phase 3** are complete and all tests pass
2. Install required packages:
   ```bash
   composer require maatwebsite/excel barryvdh/laravel-dompdf
   ```
3. Read each step file in order
4. Complete all tasks within a step before moving to the next
5. Run tests after completing each step
6. Check acceptance criteria before marking a step as complete

**Start Here:** [Step 4.1 - User Management](./step-4.1-user-management.md)

---

## Changelog

| Date | Change |
|------|--------|
| Dec 13, 2025 | Initial Phase 4 plan created |
