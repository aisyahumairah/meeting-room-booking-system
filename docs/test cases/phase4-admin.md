# User Acceptance Test Cases - Phase 4: Administration & Analytics

**Project:** Meeting Room Booking System (MRBS)
**Module:** Admin Panel & Reports
**Document ID:** UAT-PH4
**Version:** 1.0

## 1. Introduction
This section verifies the administrative capabilities, including user management, system oversight via audit logs, and data reporting.

## 2. Test Cases

| Test Case ID | Test Item | Preconditions | Input Data | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-PH4-001** | User Management (List) | Admin logged in. | 1. Navigate to `/admin/users` | List of all system users displayed with Status, Role, and Department. | | |
| **TC-PH4-002** | Deactivate User | Active User exists. | 1. Navigate to `/admin/users`<br>2. Click "Deactivate" on User<br>3. Confirm | User status changes to "Inactive". User can no longer login. | | |
| **TC-PH4-003** | Activate User | Inactive User exists. | 1. Navigate to `/admin/users`<br>2. Click "Activate" on User | User status changes to "Active". User can login again. | | |
| **TC-PH4-004** | Admin Password Reset | User exists. | 1. Click "Action" > "Reset Password" | Success message. User receives email or temporary password (depending on config). | | |
| **TC-PH4-005** | View Audit Logs | Events have occurred. | 1. Navigate to `/admin/audit-logs` | Table shows chronological list of actions (Who, What, When). | | |
| **TC-PH4-006** | Filter Audit Logs | Specific events exist. | 1. Filter by Event Type: "User Login"<br>2. Click "Filter" | Table only shows login events. | | |
| **TC-PH4-007** | Export Audit Logs | Logs exist. | 1. Click "Export CSV" | Browser downloads a `.csv` file containing the visible audit data. | | |
| **TC-PH4-008** | Room Utilization Report | Bookings exist. | 1. Navigate to `/admin/reports`<br>2. Select "Room Utilization"<br>3. Range: "Last Month" | Graph/Table displays percentage usage per room. | | |
| **TC-PH4-009** | System Configuration | Admin logged in. | 1. Navigate to `Settings`<br>2. Toggle "Maintenance Mode" ON<br>3. Click "Save" | System displays Maintenance page to non-admins. Admin sees warning banner. | | |
| **TC-PH4-010** | Email Settings | Admin logged in. | 1. Navigate to `Settings`<br>2. Update Support Email<br>3. Click "Save" | New email is saved. Test email (if available) is received at new address. | | |
