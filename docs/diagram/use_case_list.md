# Use Case List

This document provides a comprehensive list of all use cases for the Meeting Room Booking System (MRBS).

## Overview

The MRBS system consists of 12 primary use cases organized by functional area:

---

## 1. Authentication & Authorization

### UC001: Login into System
**Actor**: User (Staff, Administrator, System Admin, Director)

**Description**: This function allows users to securely access the system using their registered email and password.

**Key Features**:
- Email and password authentication
- Failed login attempt tracking with account lockout (5 attempts)
- Password reset functionality via email
- First-time login password change requirement

---

## 2. Booking Management

### UC002: Book Meeting Room
**Actor**: User (Staff, Administrator, System Admin, Director)

**Description**: This function allows users to book an available meeting room by selecting a date, time, and room. The system validates availability, operating hours, and booking rules before confirming and recording the booking.

**Key Features**:
- Meeting room selection with availability checking
- Date and time selection
- Purpose of booking entry
- Booking confirmation with unique reference number
- Email notification upon successful booking
- Calendar view booking creation
- Booking on behalf of another user (Administrator & Director)
- Recurring booking creation (Daily, Weekly, Monthly)
- Audit trail logging

---

### UC003: Cancel Booking
**Actor**: User (Staff, Administrator, System Admin, Director)

**Description**: This use case allows users to cancel an existing meeting room booking with Confirmed status.

**Key Features**:
- Cancellation of bookings with Confirmed status
- Cancellation reason requirement
- Meeting room time slot release
- Single date cancellation for recurring bookings
- Cancellation of all remaining bookings in a series
- Audit trail logging

---

### UC004: Manage Booking
**Actor**: Administrator, Director

**Description**: This use case allows Administrators and Directors to manage existing meeting room bookings, including viewing details, updating information, and canceling bookings.

**Key Features**:
- View all bookings with filtering options
- View detailed booking information
- Update booking details (date, time, room, purpose)
- Cancel bookings with reason
- View audit trail and update history

---

## 3. Room Management

### UC005: Manage Meeting Room
**Actor**: Administrator, Director

**Description**: This use case allows Administrators and Directors to manage meeting rooms in the system, including browsing, viewing, updating, changing status, scheduling maintenance, and deleting rooms.

**Key Features**:
- Browse all meeting rooms
- View room details and availability calendar
- Update room details (amenities, capacity, images, description)
- Change room status (Active, Inactive, Under Maintenance)
- Schedule maintenance periods
- Delete rooms
- Role-based room visibility (Regular Users see Active only; Admins/Directors see all)

---

### UC006: Manage Amenity Room
**Actor**: Administrator, Director

**Description**: This function allows Administrators and Directors to manage meeting room amenities, including creating, viewing, updating, activating/deactivating, and deleting amenities.

**Key Features**:
- Create new amenities with name, icon, and description
- View amenity details and associated rooms
- Edit amenity information
- Activate/deactivate amenities
- Delete amenities (disabled if rooms are using it)

---

## 4. User Management

### UC007: Manage User
**Actor**: System Admin, Director

**Description**: This function allows System Admins and Directors to manage user accounts.

**Key Features**:
- Create new user accounts with role assignment
- Edit user details (name, email, staff number, department, phone, role)
- Activate/deactivate user accounts
- Reset user passwords (temporary password or email link)
- View user activity history with filtering and export options (CSV, Excel)
- Delete user accounts

---

## 5. System Configuration

### UC008: Manage Configuration System
**Actor**: System Admin

**Description**: This function allows System Admins to manage global system settings, including session security, notification triggers, and maintenance modes.

**Key Features**:
- Session & Security settings (timeout, password reset token expiry, login attempts, lockout duration)
- View read-only policies (password policy, booking rules)
- Enable/disable maintenance mode
- Configure email notifications (master toggle, specific triggers)

---

### UC009: Manage Audit Trail
**Actor**: System Admin

**Description**: This function allows System Administrators to view and manage audit trail records for monitoring, accountability, and security compliance.

**Key Features**:
- View audit trail records (date/time, user, role, module, action)
- View detailed activity information (affected data, IP address)
- Filter audit trail by date range, user, role, module, or action type
- Export audit trail (CSV, Excel)

---

## 6. Reporting & Analytics

### UC010: Manage Reporting
**Actor**: System Admin, Administrator, Director

**Description**: This function allows authorized users to generate and view system reports.

**Key Features**:
- Room Utilization reports (booking counts, hours used, utilization rate)
- Booking Statistics (trends, status distribution, peak hours, top rooms)
- User Activity reports (booking activity, cancellation rates, department breakdown)
- Date range filtering
- Export options (PDF, Excel, CSV)

---

## 7. Profile Management

### UC011: Manage Profile
**Actor**: User (Staff, Administrator, System Admin, Director)

**Description**: This function allows users to view and edit their profile information and change their password.

**Key Features**:
- View profile information
- Edit profile (name, department, phone number)
- Change password with security validation
- Password security checklist enforcement

---

## 8. Session Management

### UC012: Logout
**Actor**: System Admin, Administrator, Director

**Description**: This function allows users to log out from the system.

**Key Features**:
- Session termination
- Redirect to login page

---

## Use Case Summary Matrix

| Use Case ID | Use Case Name | Primary Actors | Complexity |
|-------------|---------------|----------------|------------|
| UC001 | Login into System | All Users | Medium |
| UC002 | Book Meeting Room | All Users | High |
| UC003 | Cancel Booking | All Users | Medium |
| UC004 | Manage Booking | Admin, Director | High |
| UC005 | Manage Meeting Room | Admin, Director | High |
| UC006 | Manage Amenity Room | Admin, Director | Medium |
| UC007 | Manage User | System Admin, Director | High |
| UC008 | Manage Configuration System | System Admin | Medium |
| UC009 | Manage Audit Trail | System Admin | Medium |
| UC010 | Manage Reporting | System Admin, Admin, Director | Medium |
| UC011 | Manage Profile | All Users | Low |
| UC012 | Logout | All Users | Low |

---

## Actor Roles Summary

| Role | Description | Access Level |
|------|-------------|--------------|
| Staff (Regular User) | Standard employees who can book rooms and manage their own bookings | Basic |
| Administrator | Office managers who can manage rooms, bookings, and view reports | Advanced |
| Director | Department heads with administrative privileges and oversight | Advanced |
| System Admin | IT staff with full system configuration and user management access | Full |
