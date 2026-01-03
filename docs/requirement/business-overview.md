# Meeting Room Booking System (MRBS)
## Business Overview Document

**Prepared For:** OIB Group  
**Document Version:** 1.0  
**Date:** December 4, 2025  
**Purpose:** High-level system overview for client verification

## 1. System Introduction

The Meeting Room Booking System (MRBS) is a centralized web-based platform designed for internal use by OIB Group staff. It streamlines the entire booking lifecycle—from room discovery to instant booking confirmation, usage reporting, and audit tracking.

Currently, OIB Group manages meeting room bookings through manual processes using Excel spreadsheets and informal communication channels. This approach leads to:

- **Double bookings** and scheduling conflicts
- **Time wastage** coordinating room availability manually
- **Lack of visibility** into room utilization across properties
- **No audit trail** for booking history and accountability
- **Difficulty in planning** and resource optimization
- **Manual reporting** that is time-consuming and prone to errors
- **No transparency** in booking history and accountability
- **Sabotage** of booking records

The MRBS solves these problems by providing real-time room availability, instant booking confirmation on a first-come-first-served basis, centralized booking management accessible from anywhere, comprehensive reporting for data-driven decision making, complete audit trails for transparency and accountability, and a user-friendly interface requiring minimal training. Importantly, this is an internal resource management system with no payment or billing features—all meeting rooms are company-owned resources available to staff at no charge.

## 2. User Roles and Permissions

The system supports four distinct user roles, each with specific permissions designed to balance self-service convenience with proper oversight and control.

### Regular User

Regular Users are all staff members of OIB Group and represent the majority of system users. This is the default role assigned upon account creation. They can search and browse meeting rooms by capacity, floor, and availability. They are able to view room details including capacity, location, amenities, and availability. Regular Users can create bookings for themselves (both one-time and recurring), view their own booking history and upcoming reservations, edit their own bookings before they occur, and cancel their own bookings. They receive email notifications for booking confirmations and reminders, and can update their personal profile information and change their password.

However, Regular Users cannot view other users' bookings, manage rooms, users, or system settings, access reports or audit logs, or create bookings on behalf of others.

### Administrator

Administrators are administrative staff or office managers who oversee meeting room inventory and resource allocation. In addition to all Regular User capabilities, Administrators can view all bookings from all users across the system, edit any user's booking details, cancel any booking on behalf of users or due to conflicts, and create bookings on behalf of other staff members.

For room management, Administrators can add new meeting room entries, edit room details (capacity, location, amenities, status), set room status to Active, Inactive, or Under Maintenance, schedule maintenance periods to block rooms for specific date ranges, and delete or deactivate rooms subject to certain constraints.

Administrators also have reporting capabilities: they can generate daily reports, monthly reports, user booking reports, and room utilization reports. They can export these reports in PDF, Excel, or CSV formats and access print-friendly layouts. However, Administrators cannot manage users (view, create, assign roles, deactivate), view audit trail logs, or configure system-wide settings.

### Director

Directors are department heads, senior management, or executives who have an oversight and governance role. They have all Administrator capabilities plus additional powers. For user management, Directors can view the complete list of registered users, create new user accounts manually if needed, assign or modify user roles (promote users to Administrator, or assign Director/System Admin roles with approval), deactivate or delete users (with data integrity constraints), reset user passwords, and view user statistics including counts by role and activity levels.

For audit and compliance, Directors can view the complete audit trail (an immutable log of all system activities), filter audit logs by date, user, event type, or entity, export audit logs for compliance or forensic analysis, and view detailed user activity history. Directors cannot configure system-wide settings such as booking limits, operating hours, or session timeouts.

### System Administrator

System Administrators are IT staff or technical administrators with the highest technical privilege level. They can book rooms for themselves and manage their own bookings. For user management, they have full control to create, edit, deactivate, and delete users, assign any role including Administrator, Director, and System Administrator roles, and reset passwords to help users with account access.

System Administrators control system configuration including session timeout periods, login attempt limits, notification settings, and maintenance mode. They can view all reports, access the complete audit trail, and export audit logs for backup or analysis.

However, System Administrators cannot manage rooms (add, edit, or delete rooms), or edit or cancel other users' bookings. Their role is focused on system administration, not operational booking management.

## 3. Meeting Rooms Module

### Room Information

Each meeting room in the system has a name/identifier, capacity (maximum number of people), floor and location details, a description, a list of amenities and equipment (such as projector, whiteboard, video conferencing, teleconferencing phone, computer/monitor, flip chart, air conditioning, and natural light/windows), photos, and a status indicator.

### Room Status Types

Rooms can have one of three status types. **Active** means the room is available for booking—it appears in user search results and can be booked by users immediately if available. **Inactive** means the room is not available for booking; it is hidden from user search results and only visible to Administrators/Directors in the management view, though existing future bookings remain valid. **Under Maintenance** means the room is temporarily unavailable; it is visible in search results but cannot be booked. The system shows "Under Maintenance" badge with dates and automatically reverts to Active when the maintenance period ends.

### Room Discovery

Users can find rooms through filtering by minimum capacity (rooms with capacity greater than or equal to the selected value), date and time availability (showing only rooms available during the selected period with real-time checks), and equipment/amenities (rooms must have all selected amenities). Administrators and Directors can also filter by status to include Inactive or Under Maintenance rooms.

The room availability calendar displays bookings for each room in a week view (Monday-Friday) with 30-minute time slots from 8:00 AM to 6:00 PM. Available slots appear in white, booked slots in blue/orange, the user's own bookings in green, maintenance periods with a red/gray striped pattern, and past time slots are grayed out. Regular Users see booked slots as simply "Booked" without user information, while Administrators and Directors see the booker name and booking purpose.

### Room Administration

Administrators and Directors can add new rooms by providing a room name (unique, max 50 characters), capacity (1-500 people), and floor/location. Optional fields include description (max 500 characters), amenities selection, and up to 5 photos (JPG/PNG, max 5MB each). New rooms are set to Active by default.

When changing a room's status, the system displays a warning if existing future bookings will be affected. Email notifications are sent to users with affected bookings. All status changes are logged in the audit trail. For Under Maintenance status, start and end dates are required, and the room automatically returns to Active when maintenance ends.

Rooms can only be permanently deleted if they have no booking history whatsoever—no past, present, or future bookings. If a room has any booking records, it can only be deactivated (soft delete), which hides it from users but preserves all booking data.

## 4. Booking Module

### Creating a Booking

To create a booking, users select an active room, choose a booking date (any future date—there is no maximum advance booking restriction), select start and end times in 30-minute increments within operating hours (8:00 AM - 6:00 PM), and provide a purpose/description (max 500 characters). For Regular Users, the Person in Charge is automatically set to themselves. Administrators and Directors can select any user to book on their behalf.

The system validates that the room is active and available, the date is not in the past, the duration is between 30 minutes and 8 hours, the booking is within a single day (no overnight bookings), there are no conflicts with existing Confirmed bookings, and the room is not under maintenance during the selected period.

Upon successful submission, the system generates a unique booking reference (e.g., BK-2025-00001), confirms the booking immediately, sends an email notification to the user, and logs the creation in the audit trail.

### Recurring Bookings

Users can create bookings that repeat on a regular schedule. Daily patterns repeat every X days. Weekly patterns repeat on specific days of the week. Monthly patterns repeat on a specific date of the month. The recurrence can end on a specific date or after a specified number of occurrences, with a maximum of 1 year worth of occurrences from the start date.

The system checks room availability for all occurrences before creation. Individual booking records are created for each occurrence, linked by a Series ID. All occurrences are confirmed immediately upon creation if no conflicts exist. Editing or cancelling one occurrence affects the entire series—individual occurrences cannot be managed separately.

### Booking Status Lifecycle

New bookings are automatically **Confirmed** upon submission. Users or administrators can cancel bookings, changing the status to **Cancelled** and requiring a cancellation reason. After a booking's end time passes, it automatically becomes **Completed**.

### Viewing Bookings

Regular Users see only their own bookings in both list and calendar views. They can filter by status (Confirmed, Cancelled, Completed), by room, and by date range. Administrators and Directors see all bookings across all users with additional filters for user, department, and booking type (one-time vs. recurring).

The calendar view provides day, week, and month perspectives. Day view shows a single day divided into 30-minute time slots across all rooms. Week view (the default) shows Monday through Friday with booking blocks visible. Month view shows a full calendar with booking counts per day. Clicking an empty slot initiates booking creation with date/time pre-filled.

### Editing Bookings

Regular Users can edit their own upcoming bookings. Editable fields include date, time, room, and purpose. For recurring series, changes apply to all occurrences.

Administrators and Directors can edit bookings of any status including Confirmed bookings. When they edit a Confirmed booking, the status remains Confirmed. The original booker receives notification of changes made by the administrator. All edits must still pass validation for availability, duration, and operating hours.

### Cancelling Bookings

Regular Users can cancel their own bookings at any time before the booking end time with no notice period required. A cancellation reason (max 500 characters) is required. Administrators and Directors can cancel any user's booking. For recurring series, cancelling one occurrence cancels the entire series. Cancelled bookings cannot be "uncancelled"—a new booking must be created instead. The room availability is freed immediately upon cancellation.

All bookings are processed on a first-come-first-served basis—there is no manual approval queue. Administrators and Directors provide oversight by monitoring the booking schedule, resolving resource conflicts if they arise, and generating utilization reports. They can cancel or reschedule bookings as needed to accommodate high-priority organizational needs, with automated notifications sent to affected users.

## 5. Administrative Management Module

### User Management

System Administrators and Directors can view all registered users in a paginated list showing staff number, name, email, department, role, status, last login, and account creation date. They can search by name, email, or staff number and filter by role, department, or status.

When creating a new user, the administrator provides staff number (unique), full name, email address (unique), and optionally department and phone number. The system generates a temporary password that the user must change on first login. A welcome email is automatically sent. Self-registration is not supported—all accounts must be provisioned by administrators.

User records can be edited to update name, department, phone number, and role. Staff number and email address cannot be changed. Role changes are logged in the audit trail.

Users with system activity (bookings, room management) cannot be deleted—they can only be deactivated. Deactivated users cannot log in or manage bookings, but their existing bookings and historical data are preserved. Users with no activity can be permanently deleted after confirmation.

### Audit Trail

The system maintains an immutable log of all significant events including authentication (login, logout, failed attempts, password resets), booking actions (created, edited, cancelled), room management (created, edited, status changed, maintenance scheduled), user management (created, edited, role changed, deactivated, deleted), and system configuration changes.

Each log entry records a unique event ID, timestamp, actor (who performed the action), action type, target entity, detailed changes (old and new values), and IP address. Audit logs cannot be edited or deleted—not even by System Administrators.

Directors and System Administrators can view and filter the audit trail by date range, actor, action type, and entity type. Logs can be exported to CSV or Excel and are retained for a minimum of 2 years.

### System Configuration

System Administrators can configure session timeout duration (default 30 minutes, range 15-120 minutes), password reset token expiration (default 30 minutes, range 5-60 minutes), login attempt limits (default 5 attempts, range 3-10) with lockout duration, and notification toggles for each email notification type.

Certain settings are hardcoded and cannot be changed through the interface: password policy (minimum 8 characters with letters, numbers, and symbols), operating hours (8:00 AM - 6:00 PM), booking time increments (30 minutes), minimum booking duration (30 minutes), maximum booking duration (8 hours), single-day only bookings (no overnight), and recurring booking maximum period (1 year).

System Administrators can enable Maintenance Mode, which allows only Administrators and System Administrators to log in while regular users see a "System Under Maintenance" page.

### Notifications

The system sends email notifications for account creation (welcome email with credentials), password reset links, booking created (confirmation to user), booking cancelled, booking reminders (24 hours before start time), and room status changes affecting upcoming bookings.

Users can opt out of non-critical notifications (reminders, digest notifications) but cannot opt out of critical notifications (password reset, booking status changes, account changes). System Administrators can configure SMTP settings and toggle notification types system-wide.

### Reporting

Four report types are available to Administrators, Directors, and System Administrators. All reports are generated on-demand, support custom date ranges with preset options (Today, Last 7 Days, This Month, etc.), and can be exported to CSV, Excel, or PDF formats.

The **Daily Bookings Report** shows all bookings for a selected date grouped by room, with summary statistics including total bookings, breakdown by status, and room occupancy rate.

The **Monthly Bookings Report** analyzes patterns over a month with summary statistics (total bookings, cancellation rate, most active day, most popular room), bookings by day chart, and bookings by room chart.

The **User Bookings Report** shows individual user booking history with statistics (total bookings, cancellation rate, favorite room) or an all-users summary showing the top bookers.

The **Room Utilization Report** calculates utilization rates for each room (booked hours / available hours), identifies peak hours through a heatmap, and provides recommendations for underutilized (<30%) and overutilized (>80%) rooms.

## 6. Business Rules Summary

### Booking Rules

- Bookings can be made for any future date (no maximum advance booking limit)
- Operating hours are 8:00 AM to 6:00 PM
- Time increments are 30 minutes
- Minimum booking duration is 30 minutes
- Maximum booking duration is 8 hours
- Bookings must be single-day only (no overnight bookings)
- Recurring bookings can span a maximum of 1 year from start date
- Purpose/description has a maximum length of 500 characters
- All bookings are automatically confirmed if the room is available—no manual approval required

### Access Control Rules

- Users can edit and cancel their own upcoming bookings
- Administrators/Directors can edit and cancel any booking
- Administrators/Directors can create bookings on behalf of others
- Only Directors and System Administrators can view audit logs
- Only System Administrators can configure system settings
- Users cannot change their own role

### Room Management Rules

- Rooms with any booking history cannot be permanently deleted
- Setting a room to Under Maintenance blocks new bookings during that period
- Users with bookings in a room set to Under Maintenance are notified automatically

### User Management Rules

- All user accounts must be created by administrators (no self-registration)
- Users must change temporary password on first login
- Users with system activity cannot be deleted—only deactivated
- Sessions expire after 30 minutes of inactivity
- Accounts are temporarily locked after 5 failed login attempts

### Recurring Booking Rules

- Recurring events are linked and managed as a series
- Editing affects all occurrences in the series
- Cancellation cancels the entire series
- Individual occurrences cannot be managed separately

## 7. Module Flow Summary

### Booking Flow for Regular Users

The user searches for an available room by filtering on capacity, date/time, and amenities. They select a room and view its details and availability calendar. They click "Book This Room" and fill in the booking form with date, time, and purpose. After reviewing the details, they submit the booking. The system validates availability and instantly confirms the booking if no conflicts exist. The user receives email confirmation. The booking appears as Confirmed in the user's dashboard and the user receives a reminder 24 hours before the meeting.

### Approval Flow for Administrators

The Administrator logs in and views the Admin Dashboard showing daily utilization stats and upcoming bookings. They can monitor all bookings through the "All Bookings" view or the global calendar. If a scheduling conflict or special requirement arises, the Administrator can contact users or modify/cancel bookings as necessary. All such actions are automatically notified to the impacted users and logged in the audit trail.

### Room Management Flow

The Administrator accesses Room Inventory (Admin View) to see all rooms including inactive ones. To add a room, they fill in details (name, capacity, location, amenities, photos) and save. The room becomes active and visible to users. To change status, they select the room and choose new status, entering maintenance dates if applicable. The system warns about affected bookings and notifies impacted users. To deactivate, they choose Deactivate for rooms with booking history, keeping data preserved.

### User Management Flow

The Director or System Administrator accesses User Management. They can search or filter the user list as needed. To create a user, they fill in details (staff number, name, email, department, role). The system generates a temporary password and sends a welcome email. The new user logs in, changes password, and begins using the system. To modify a user, the administrator edits details or changes role with confirmation logged. To remove access, they deactivate the user, which prevents login but preserves history.

### Reporting Flow

The Administrator, Director, or System Administrator navigates to the Reports section. They select report type (Daily, Monthly, User, or Utilization). They configure parameters including date range and filters. They click "Generate Report" and view the on-screen preview. They can export as PDF/Excel/CSV or print directly.

## 8. Key System Benefits

**Operational Efficiency:** Reduces booking time from hours to seconds, eliminates double bookings, and removes manual approval overhead.

**Cost Optimization:** Identifies underutilized rooms, optimizes space allocation, and reduces administrative overhead.

**User Experience:** Provides self-service booking, streamlined confirmation, and mobile-friendly access from any device.

**Management Insights:** Delivers real-time utilization reports, trend analysis, and resource planning data through export-ready reports.

**Governance & Compliance:** Ensures complete activity logs, instant first-come-first-served fairness, role-based access control, and audit-ready records that address the sabotage concern through comprehensive tracking.
