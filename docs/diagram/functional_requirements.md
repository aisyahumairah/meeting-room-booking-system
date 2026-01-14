# Functional Requirements

This document outlines the functional requirements derived from the use case descriptions for the Meeting Room Booking System (MRBS).

---

## 1. Authentication & Authorization Module

### FR-AUTH-001: User Login
**Source**: UC001  
**Description**: The system SHALL authenticate users using email address and password.

**Requirements**:
- Accept email and password credentials
- Validate credentials against database
- Redirect authenticated users to role-specific dashboard
- Display error message for invalid credentials

---

### FR-AUTH-002: Failed Login Tracking
**Source**: UC001  
**Description**: The system SHALL track failed login attempts and temporarily lock accounts after 5 consecutive failures.

**Requirements**:
- Track failed login attempts per user account
- Lock account after 5 failed attempts
- Set lockout duration (configurable, default 15 minutes)
- Display lockout notification to user
- Reset failed attempt counter upon successful login

---

### FR-AUTH-003: Password Reset
**Source**: UC001  
**Description**: The system SHALL provide password reset functionality via email.

**Requirements**:
- Generate time-limited secure reset link (default 30 minutes)
- Send reset link to registered email address
- Validate reset token before allowing password change
- Update password securely in database
- Notify user of successful password change
- Redirect to login page after reset

---

### FR-AUTH-004: First-Time Login Password Change
**Source**: UC001  
**Description**: The system SHALL require users to change their password on first-time login or after admin password reset.

**Requirements**:
- Detect first-time login or temporary password status
- Auto-redirect to change password page
- Enforce password security policy
- Block access to system until password is changed
- Update password status after successful change

---

### FR-AUTH-005: Session Management
**Source**: UC001, UC012  
**Description**: The system SHALL manage user sessions with configurable timeout.

**Requirements**:
- Create session upon successful login
- Set session timeout (configurable, default 30 minutes)
- Extend session on user activity
- Terminate session on logout
- Redirect to login page after session expiry

---

## 2. Booking Management Module

### FR-BM-001: Create One-Time Booking
**Source**: UC002  
**Description**: The system SHALL allow users to create one-time meeting room bookings.

**Requirements**:
- Select meeting room from available list
- Select date and time within operating hours (8:00 AM - 6:00 PM)
- Enter booking purpose
- Validate room availability in real-time
- Prevent double bookings
- Generate unique booking reference number
- Record booking timestamp
- Associate booking with user (Person in Charge)
- Send booking confirmation email
- Log booking action in audit trail

---

### FR-BM-002: Create Recurring Booking
**Source**: UC002  
**Description**: The system SHALL allow users to create recurring meeting room bookings.

**Requirements**:
- Support recurrence patterns: Daily, Weekly, Monthly
- Set recurrence end condition (by date or number of occurrences)
- Generate booking preview with all proposed dates
- Display availability status for each occurrence
- Highlight conflicts if any occurrence is unavailable
- Create individual booking records for each occurrence
- Generate unique reference numbers for each booking
- Validate all occurrences against room availability
- Disable creation if any conflict exists
- Log recurring booking action in audit trail

---

### FR-BM-003: Book on Behalf of Another User
**Source**: UC002  
**Description**: The system SHALL allow Administrators and Directors to create bookings on behalf of other users.

**Requirements**:
- Enable User Selection dropdown for Admin/Director roles
- Select Person in Charge (PIC) from user list
- Associate booking with selected PIC, not creator
- Send confirmation email to PIC
- Log booking action with creator and PIC information

---

### FR-BM-004: Calendar View Booking
**Source**: UC002  
**Description**: The system SHALL allow users to create bookings directly from calendar view.

**Requirements**:
- Display calendar with Day, Week, Month views
- Show room availability with color-coded blocks
- Allow clicking on available time slot
- Pre-fill date and time from selected slot
- Redirect to booking form with pre-populated data

---

### FR-BM-005: Real-Time Availability Check
**Source**: UC002  
**Description**: The system SHALL validate room availability in real-time during booking creation.

**Requirements**:
- Check room availability against existing bookings
- Check room status (Active, Inactive, Under Maintenance)
- Validate against operating hours
- Display error message if room is unavailable
- Suggest alternative time slots or rooms

---

### FR-BM-006: Cancel Single Booking
**Source**: UC003  
**Description**: The system SHALL allow users to cancel their own bookings with Confirmed status.

**Requirements**:
- Display user's bookings with Confirmed status
- Require cancellation reason
- Update booking status to Cancelled
- Release meeting room time slot
- Record cancellation in audit trail
- Send cancellation notification email

---

### FR-BM-007: Cancel Recurring Booking (Single Date)
**Source**: UC003  
**Description**: The system SHALL allow users to cancel a single occurrence from a recurring booking series.

**Requirements**:
- Identify booking as part of recurring series
- Provide option to cancel single date only
- Require cancellation reason
- Update only selected occurrence to Cancelled status
- Preserve other bookings in series
- Log cancellation action

---

### FR-BM-008: Cancel Recurring Booking (All Remaining)
**Source**: UC003  
**Description**: The system SHALL allow users to cancel all remaining bookings in a recurring series.

**Requirements**:
- Identify all future bookings in series with Confirmed status
- Provide option to cancel all remaining bookings
- Require cancellation reason
- Update all future occurrences to Cancelled status
- Preserve past completed bookings
- Log cancellation action

---

### FR-BM-009: Admin Booking Management
**Source**: UC004  
**Description**: The system SHALL allow Administrators and Directors to view and manage all bookings.

**Requirements**:
- Display all bookings in system
- Filter by User, Room, Date Range, Status
- View detailed booking information
- Update booking details (date, time, room, purpose)
- Cancel any booking with reason
- View audit trail and update history
- Send notification to affected user on admin changes

---

### FR-BM-010: Booking Status Workflow
**Source**: UC002, UC003  
**Description**: The system SHALL manage booking status transitions.

**Requirements**:
- Set initial status to Confirmed upon creation
- Update to Cancelled when cancelled
- Update to Completed after meeting end time
- Prevent cancellation of Completed or Cancelled bookings

---

## 3. Room Management Module

### FR-RM-001: Browse Meeting Rooms
**Source**: UC005  
**Description**: The system SHALL allow users to browse meeting rooms based on their role.

**Requirements**:
- Display Active rooms only for Regular Users
- Display all rooms (Active, Inactive, Under Maintenance) for Admin/Director
- Show room name, capacity, floor, current status
- Support search and filtering

---

### FR-RM-002: View Room Details
**Source**: UC005  
**Description**: The system SHALL display detailed room information and availability.

**Requirements**:
- Display room amenities, floor, specifications
- Show room images and description
- Display availability calendar
- Color-code bookings by status (Booked, Your Booking, Completed, Maintenance)

---

### FR-RM-003: Create/Update Room
**Source**: UC005  
**Description**: The system SHALL allow Administrators and Directors to create and update meeting rooms.

**Requirements**:
- Enter/update room name, capacity, floor
- Select amenities from available list
- Upload room images
- Enter room description
- Validate input data
- Log room creation/update in audit trail

---

### FR-RM-004: Change Room Status
**Source**: UC005  
**Description**: The system SHALL allow Administrators and Directors to change room status.

**Requirements**:
- Support status options: Active, Inactive, Under Maintenance
- Require maintenance dates and reason for Under Maintenance status
- Update room availability calendar
- Prevent booking during maintenance periods
- Log status change in audit trail

---

### FR-RM-005: Schedule Room Maintenance
**Source**: UC005  
**Description**: The system SHALL allow scheduling of room maintenance periods.

**Requirements**:
- Enter maintenance start and end dates
- Enter maintenance reason
- Update room status to Under Maintenance
- Block room availability during maintenance
- Auto-revert to Active status after maintenance end date
- Log maintenance scheduling

---

### FR-RM-006: Delete Room
**Source**: UC005  
**Description**: The system SHALL allow deletion of meeting rooms.

**Requirements**:
- Display delete confirmation popup
- Soft delete rooms with booking history
- Log deletion action in audit trail
- Prevent permanent deletion of rooms with bookings

---

## 4. Amenity Management Module

### FR-AM-001: Create Amenity
**Source**: UC006  
**Description**: The system SHALL allow Administrators and Directors to create amenities.

**Requirements**:
- Enter amenity name, icon, description
- Set initial status to Active
- Validate input data
- Save amenity record in database

---

### FR-AM-002: View Amenity Details
**Source**: UC006  
**Description**: The system SHALL display amenity details and associated rooms.

**Requirements**:
- Display amenity name, icon, description, status
- List all meeting rooms using the amenity
- Show usage count

---

### FR-AM-003: Update Amenity
**Source**: UC006  
**Description**: The system SHALL allow editing of amenity information.

**Requirements**:
- Update amenity name, icon, description
- Validate changes
- Preserve room associations

---

### FR-AM-004: Activate/Deactivate Amenity
**Source**: UC006  
**Description**: The system SHALL allow activation and deactivation of amenities.

**Requirements**:
- Toggle amenity status between Active and Inactive
- Preserve existing room-amenity associations
- Prevent assignment of inactive amenities to new rooms
- Display status confirmation message

---

### FR-AM-005: Delete Amenity
**Source**: UC006  
**Description**: The system SHALL allow deletion of amenities not in use.

**Requirements**:
- Check if amenity is assigned to any rooms
- Disable delete button if amenity is in use
- Display delete confirmation popup
- Log deletion action in audit trail

---

## 5. User Management Module

### FR-UM-001: Create User Account
**Source**: UC007  
**Description**: The system SHALL allow System Admins and Directors to create user accounts.

**Requirements**:
- Enter user details (name, email, staff number, department, phone)
- Assign user role (Regular User, Administrator, System Admin, Director)
- Generate temporary password (manual entry or auto-generate "abc123")
- Send welcome email with login credentials
- Log user creation in audit trail

---

### FR-UM-002: Edit User Account
**Source**: UC007  
**Description**: The system SHALL allow editing of user account information.

**Requirements**:
- Update name, email, staff number, department, phone, role
- Validate updated information
- Log update action in audit trail

---

### FR-UM-003: Activate/Deactivate User
**Source**: UC007  
**Description**: The system SHALL allow activation and deactivation of user accounts.

**Requirements**:
- Toggle user status between Active and Inactive
- Require confirmation before status change
- Block login for inactive users
- Log status change in audit trail

---

### FR-UM-004: Reset User Password
**Source**: UC007  
**Description**: The system SHALL allow password reset for user accounts.

**Requirements**:
- Support two reset methods: temporary password or email link
- Generate temporary password "abc123" if selected
- Send password reset email if email option selected
- Force password change on next login
- Log password reset action

---

### FR-UM-005: View User Activity History
**Source**: UC007  
**Description**: The system SHALL display user activity history.

**Requirements**:
- Show all actions performed by user
- Filter by date range and event type
- Export to CSV or Excel format

---

### FR-UM-006: Delete User Account
**Source**: UC007  
**Description**: The system SHALL allow deletion of user accounts.

**Requirements**:
- Display delete confirmation popup
- Soft delete users with booking history
- Log deletion action in audit trail

---

## 6. System Configuration Module

### FR-SC-001: Manage Session & Security Settings
**Source**: UC008  
**Description**: The system SHALL allow configuration of session and security parameters.

**Requirements**:
- Configure Session Timeout (15-120 minutes, default 30)
- Configure Password Reset Token Expiry (5-60 minutes, default 30)
- Configure Login Attempt Limit (3-10 attempts, default 5)
- Configure Lockout Duration (5-60 minutes, default 15)
- Validate input ranges
- Apply changes globally

---

### FR-SC-002: View Read-Only Policies
**Source**: UC008  
**Description**: The system SHALL display read-only organizational policies.

**Requirements**:
- Display Password Policy (min 8 chars, caps, numbers, symbols)
- Display Booking Rules (operating hours, max duration)
- Prevent editing of policy fields

---

### FR-SC-003: Manage Maintenance Mode
**Source**: UC008  
**Description**: The system SHALL support maintenance mode activation.

**Requirements**:
- Enable/disable maintenance mode checkbox
- Require confirmation before activation
- Log out Regular Users upon activation
- Display "System Under Maintenance" page to public
- Restrict login to Admin and System Admin only

---

### FR-SC-004: Configure Email Notifications
**Source**: UC008  
**Description**: The system SHALL allow configuration of email notification triggers.

**Requirements**:
- Master Email Enable/Disable toggle
- Individual toggles for: Welcome Emails, Password Resets, Booking Confirmations, Cancellations, 24h Reminders, Room Status Changes
- Apply changes immediately

---

## 7. Audit Trail Module

### FR-AT-001: View Audit Trail
**Source**: UC009  
**Description**: The system SHALL maintain and display audit trail records.

**Requirements**:
- Record date/time, user, role, module, action for all system activities
- Display audit trail in chronological order
- Show detailed activity information (affected data, IP address)
- Ensure immutability of audit records

---

### FR-AT-002: Filter Audit Trail
**Source**: UC009  
**Description**: The system SHALL allow filtering of audit trail records.

**Requirements**:
- Filter by date/time range
- Filter by user
- Filter by user role
- Filter by module or action type
- Display filtered results

---

### FR-AT-003: Export Audit Trail
**Source**: UC009  
**Description**: The system SHALL allow export of audit trail records.

**Requirements**:
- Export to CSV format
- Export to Excel format
- Include all filtered records in export

---

## 8. Reporting Module

### FR-RP-001: Room Utilization Report
**Source**: UC010  
**Description**: The system SHALL generate room utilization reports.

**Requirements**:
- Calculate booking counts per room
- Calculate total hours used per room
- Calculate utilization rate (% of available time used)
- Support date range filtering
- Export to PDF, Excel, CSV

---

### FR-RP-002: Booking Statistics Report
**Source**: UC010  
**Description**: The system SHALL generate booking statistics reports.

**Requirements**:
- Display booking volume trends over time
- Show status distribution (Confirmed, Cancelled, Completed)
- Identify peak hours and time slots
- Rank top booked rooms
- Support date range filtering
- Export to PDF, Excel, CSV

---

### FR-RP-003: User Activity Report
**Source**: UC010  
**Description**: The system SHALL generate user activity reports.

**Requirements**:
- List bookings per user
- Calculate cancellation rates per user
- Group usage by department
- Support date range filtering
- Export to PDF, Excel, CSV

---

## 9. Profile Management Module

### FR-PM-001: View Profile
**Source**: UC011  
**Description**: The system SHALL allow users to view their profile information.

**Requirements**:
- Display name, email, staff number, department, phone, role

---

### FR-PM-002: Edit Profile
**Source**: UC011  
**Description**: The system SHALL allow users to edit their profile information.

**Requirements**:
- Update name, department, phone number
- Validate changes
- Display success message

---

### FR-PM-003: Change Password
**Source**: UC011  
**Description**: The system SHALL allow users to change their password.

**Requirements**:
- Require current password for verification
- Enforce password security policy (min 8 chars, caps, numbers, symbols)
- Display password security checklist
- Update password in database
- Display error for incorrect current password
- Display success message on successful change

---

## 10. System Exceptions

### FR-EX-001: Server Unavailability
**Source**: All Use Cases  
**Description**: The system SHALL handle server downtime gracefully.

**Requirements**:
- Display "System temporarily unavailable" message
- Suggest trying again later
- Log system errors

---

### FR-EX-002: Invalid Input Handling
**Source**: UC008  
**Description**: The system SHALL validate user input and display appropriate error messages.

**Requirements**:
- Validate input ranges for configuration settings
- Display specific error messages with valid ranges
- Prevent saving invalid data

---

### FR-EX-003: No Data Found
**Source**: UC010  
**Description**: The system SHALL handle empty result sets appropriately.

**Requirements**:
- Display "No data available for the selected criteria" message
- Allow users to adjust filters and retry
