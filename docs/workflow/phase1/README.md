# Phase 1: Foundation & Core Layout - Implementation Plan

**Reference Documents:**
- [Requirements Document](../../requirement/requirements-document.md) - Section 4
- [Development Workflow](../../requirement/workflow.md) - Phase 1 (Steps 1.1-1.7)
- [Mockup Reference](../../../mrbs-mock-up/docs/feature-dev-v2.md)

**Created:** December 6, 2025  
**Status:** Planning  
**Laravel Version:** 12.x  
**Database:** PostgreSQL (configured, empty)

---

## Phase Objective

Establish the foundational elements of the MRBS including:
- User authentication system (login, logout, password reset)
- Role-based authorization (4 roles: Regular User, Administrator, Director, System Admin)
- Global layout with navigation (sidebar, navbar, footer)
- User profile management
- Role-specific dashboards
- Audit trail foundation

---

## Step Files

| Step | File | Description | Priority | Status |
|------|------|-------------|----------|--------|
| 1.1 | [step-1.1-database-schema.md](./step-1.1-database-schema.md) | Database migrations, User model | CRITICAL | COMPLETED |
| 1.2 | [step-1.2-authentication.md](./step-1.2-authentication.md) | Login, logout, password reset | CRITICAL | COMPLETED |
| 1.3 | [step-1.3-authorization.md](./step-1.3-authorization.md) | Middleware, Gates, permissions | CRITICAL | COMPLETED |
| 1.4 | [step-1.4-global-layout.md](./step-1.4-global-layout.md) | Blade layouts, sidebar, navbar | HIGH | TODO |
| 1.5 | [step-1.5-user-profile.md](./step-1.5-user-profile.md) | Profile view, edit, password change | MEDIUM | TODO |
| 1.6 | [step-1.6-dashboards.md](./step-1.6-dashboards.md) | User and Admin dashboards | HIGH | TODO |
| 1.7 | [step-1.7-audit-trail.md](./step-1.7-audit-trail.md) | Audit log table and service | HIGH | TODO |

---

## Dependency Graph

```
Step 1.1 (Database Schema)
    │
    ├──► Step 1.2 (Authentication) ──► Step 1.7 (Audit Trail)
    │         │
    │         └──► Step 1.5 (User Profile)
    │
    └──► Step 1.3 (Authorization)
              │
              └──► Step 1.4 (Global Layout) ──► Step 1.6 (Dashboards)
```

**Execution Order:**
1. Step 1.1 - Database Schema (no dependencies)
2. Step 1.2 - Authentication (depends on 1.1)
3. Step 1.3 - Authorization (depends on 1.1)
4. Step 1.4 - Global Layout (depends on 1.3)
5. Step 1.5 - User Profile (depends on 1.2, 1.4)
6. Step 1.6 - Dashboards (depends on 1.4, 1.3)
7. Step 1.7 - Audit Trail (depends on 1.2)

---

## Mockup Assets Reference

Copy from mockup to Laravel public folder:
```
mrbs-mock-up/assets/ → public/assets/
mrbs-mock-up/styles/index.css → public/assets/css/custom.css
```

### Mockup Files to Convert

| Mockup File | Target Blade File | Step |
|-------------|-------------------|------|
| `mrbs-mock-up/pages/auth-login.html` | `resources/views/auth/login.blade.php` | 1.2 |
| `mrbs-mock-up/pages/auth-forgot-password.html` | `resources/views/auth/forgot-password.blade.php` | 1.2 |
| `mrbs-mock-up/pages/layout.html` | `resources/views/layouts/app.blade.php` | 1.4 |
| `mrbs-mock-up/pages/users-edit.html` | `resources/views/profile/edit.blade.php` | 1.5 |
| `mrbs-mock-up/pages/dashboard-user.html` | `resources/views/dashboard/user.blade.php` | 1.6 |
| `mrbs-mock-up/pages/dashboard-admin.html` | `resources/views/dashboard/admin.blade.php` | 1.6 |

---

## Database Tables (Phase 1)

| Table | Purpose | Step |
|-------|---------|------|
| `users` | Staff accounts with roles | 1.1 |
| `password_reset_tokens` | Password reset tokens | 1.1 |
| `sessions` | Database sessions | 1.1 |
| `audit_logs` | System activity logging | 1.7 |

---

## Test Accounts (Development)

| Email | Password | Role |
|-------|----------|------|
| sysadmin@mrbs.local | password123 | System Admin |
| director@mrbs.local | password123 | Director |
| admin@mrbs.local | password123 | Administrator |
| user@mrbs.local | password123 | Regular User |

---

## Getting Started

1. Read each step file in order
2. Complete all tasks within a step before moving to the next
3. Run tests after completing each step
4. Check acceptance criteria before marking a step as complete

**Start Here:** [Step 1.1 - Database Schema](./step-1.1-database-schema.md)
