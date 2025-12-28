# Meeting Room Booking System (MRBS)
## System Requirements and Design Document (SRDD)

**Prepared For:** OIB Group  
**Document Version:** 1.0  
**Date:** December 28, 2025  
**Status:** Official Release

---

## Table of Contents

1. [Purpose of the System](#1-purpose-of-the-system)
2. [Project Scope and Product Features](#2-project-scope-and-product-features)
3. [Background](#3-background)
4. [Features (MoSCoW Analysis)](#4-features-moscow-analysis)
5. [Functional Requirements](#5-functional-requirements)
6. [Non-Functional Requirements](#6-non-functional-requirements)
7. [System Requirements](#7-system-requirements)

---

## 1. Purpose of the System

### 1.1 System Introduction

The **Meeting Room Booking System (MRBS)** is a centralized, web-based platform designed specifically for internal use by OIB Group staff members. The system streamlines and automates the entire meeting room booking lifecycle—from room discovery and real-time availability checking to instant booking confirmation, usage reporting, and audit tracking.

### 1.2 Primary Objectives

The MRBS is designed to achieve the following strategic objectives:

1. **Eliminate Scheduling Conflicts**: Provide centralized, real-time availability tracking to prevent double bookings and scheduling conflicts across all meeting rooms.

2. **Reduce Administrative Burden**: Automate manual booking processes, confirmation workflows, and reporting tasks that currently consume significant administrative time.

3. **Improve Resource Utilization**: Enable data-driven insights through comprehensive analytics and reporting to optimize meeting room allocation and identify underutilized resources.

4. **Enhance User Experience**: Deliver intuitive, self-service booking capabilities accessible from any device, reducing the time required to reserve meeting rooms from hours to minutes.

5. **Ensure Accountability and Transparency**: Implement complete audit trails and activity logging to address sabotage concerns, maintain booking integrity, and support governance requirements.

6. **Support Strategic Decision-Making**: Provide actionable reports on room usage patterns, trends, and utilization rates to inform facility planning and resource allocation decisions.

### 1.3 Target Users

The system serves four distinct user groups within OIB Group:

- **Regular Users** (80-90% of staff): All employees who need to book meeting rooms for their meetings
- **Administrators** (2-5 office managers): Administrative staff responsible for room management and daily operations
- **Directors** (1-3 senior management): Department heads and executives with oversight and governance responsibilities
- **System Administrators** (1-2 IT staff): Technical administrators responsible for system configuration and user management

---

## 2. Project Scope and Product Features

### 2.1 In-Scope Features

The MRBS encompasses three major subsystems with 27 integrated feature modules:

#### **Subsystem 1: Meeting Room Management**
- Room inventory management (add, edit, delete, deactivate)
- Room capacity and location tracking
- Amenity and equipment cataloging
- Room status management (Active, Inactive, Under Maintenance)
- Maintenance scheduling and automatic status updates
- Room photo gallery management
- Real-time availability calendar

#### **Subsystem 2: Booking Management**
- One-time booking creation with real-time conflict detection
- Recurring booking patterns (daily, weekly, monthly)
- Booking modification and cancellation workflows
- Instant confirmation system with notifications
- Personal booking history and management
- Multi-view calendar (day, week, month)
- Automatic booking status lifecycle management
- Email notifications and reminders

#### **Subsystem 3: Administrative Management**
- User account provisioning and management
- Role-based access control (4 distinct roles)
- Password management and security controls
- Comprehensive audit trail (immutable logs)
- Four report types (Daily, Monthly, User, Utilization)
- Export capabilities (PDF, Excel, CSV)
- System configuration settings
- Session management and security controls

### 2.2 Out-of-Scope Features

The following features are explicitly **excluded** from the current scope:

- Payment processing or billing systems (all rooms are free internal resources)
- Integration with external calendar systems (Outlook, Google Calendar)
- Mobile native applications (iOS, Android)
- Video conferencing integration
- Equipment checkout management
- Visitor management or external guest booking
- Multi-property/multi-building support (current version: single location only)
- Real-time chat or messaging features
- Automatic room release for no-shows
- Catering or service requests

### 2.3 Product Boundaries

**Technology Stack:**
- Web-based application (PHP Laravel framework)
- Responsive design (works on desktop, tablet, mobile browsers)
- PostgreSQL database
- Email notifications (SMTP)

**Business Constraints:**
- Internal use only (not public-facing)
- Single property/building deployment
- Operating hours: 8:00 AM - 6:00 PM, Monday-Friday
- Maximum booking duration: 8 hours per reservation
- No overnight bookings permitted

---

## 3. Background

### 3.1 System Design Philosophy

This system is designed to **replace manual, Excel-based meeting room booking processes** that have proven inefficient, error-prone, and lack accountability. The MRBS addresses critical business problems including double bookings, scheduling conflicts, time wastage in coordination, lack of utilization visibility, absence of audit trails, manual reporting burden, and most critically, **the sabotage of booking records** due to unrestricted Excel file access.

The system architecture follows **role-based access control (RBAC)** principles, ensuring users can only access features and data appropriate to their organizational responsibilities. Built on the **Laravel MVC framework**, the system ensures separation of concerns, maintainability, and adherence to industry best practices.

### 3.2 Techniques Which MRBS Supports

The Meeting Room Booking System incorporates the following technical capabilities and methodologies:

**1. Real-Time Availability Checking**
- Instant validation of room availability during booking creation
- Conflict detection before booking submission
- Live calendar updates across all user sessions

**2. First-Come-First-Served Auto-Approval**
- Real-time availability check at booking submission
- Automated status transition to "Confirmed" upon successful validation
- Instant email confirmation and calendar blocking

**3. Audit Trail and Activity Logging**
- Immutable, tamper-proof logs of all system activities
- Comprehensive tracking of who did what, when, and from where
- Forensic analysis capabilities for compliance and security

**4. Advanced Reporting and Analytics**
- On-demand report generation with flexible date ranges
- Visual charts (bar, line, pie) for data visualization
- Export to multiple formats (PDF, Excel, CSV)
- Room utilization calculations and peak hour analysis

**5. Responsive Web Design**
- Mobile-first approach ensuring usability on all devices
- Adaptive layouts for desktop, tablet, and smartphone browsers
- Touch-friendly interfaces for tablet and mobile users

**6. Email Notification Engine**
- Automated email triggers for all booking lifecycle events
- Customizable notification templates
- User preference management for non-critical notifications

**7. Security and Session Management**
- Secure password hashing (bcrypt)
- Session timeout and automatic logout (30 minutes inactivity)
- Failed login attempt tracking and temporary account lockdown
- Password reset token expiration and single-use enforcement
- CSRF protection on all forms

**8. Database Optimization**
- Indexed columns for fast search and retrieval
- Soft deletes to preserve historical data
- Database transaction support for data integrity
- Efficient query optimization for large datasets

### 3.3 Future Versions of MRBS Will Incorporate

The following enhancements are planned for subsequent releases:

**Phase 2 Enhancements (Version 2.0):**
- **Multi-Building Support**: Extend system to manage meeting rooms across multiple OIB Group properties
- **External Calendar Integration**: Two-way sync with Outlook and Google Calendar
- **Mobile Applications**: Native iOS and Android apps with offline capabilities
- **Advanced Analytics**: Machine learning-based predictions for room demand and utilization optimization

**Phase 3 Enhancements (Version 3.0):**
- **Video Conferencing Integration**: Direct integration with Zoom, Microsoft Teams
- **Visitor Management**: External guest booking with approval and check-in workflows
- **Equipment Checkout**: Track and manage projectors, laptops, and other portable equipment
- **Catering Integration**: Request refreshments and services as part of booking process

**Long-Term Roadmap (Version 4.0+):**
- **IoT Integration**: Smart room sensors for automatic check-in/check-out and no-show detection
- **AI-Powered Scheduling**: Intelligent room recommendations based on meeting type, attendee count, and historical patterns
- **Advanced Space Analytics**: Heatmaps, occupancy sensors, and environmental data tracking
- **API Platform**: Public API for third-party integrations and custom applications

---

## 4. Features (MoSCoW Analysis)

### 4.1 Must Have (M) - Critical Features

These requirements are **essential** and **non-negotiable**. Stakeholder needs will not be satisfied if these are not delivered.

#### Authentication & Security
- ✅ User login with email and password
- ✅ Secure session management (30-minute timeout)
- ✅ Password reset functionality
- ✅ Role-based access control (4 roles: User, Admin, Director, System Admin)
- ✅ Failed login attempt tracking and lockout

#### Room Management
- ✅ Add, edit, delete meeting rooms
- ✅ Room capacity, location, and amenity tracking
- ✅ Room status management (Active, Inactive, Under Maintenance)
- ✅ Real-time room availability calendar
- ✅ Room photo upload and management

#### Booking Management
- ✅ One-time booking creation
- ✅ Recurring bookings (daily, weekly, monthly patterns)
- ✅ Real-time conflict detection
- ✅ Booking modification and cancellation
- ✅ Instant confirmation with real-time conflict detection
- ✅ Personal booking history view

#### Notifications
- ✅ Email notifications for booking confirmations
- ✅ Email notifications for booking status changes
- ✅ Email notifications for booking changes

#### Reporting
- ✅ Daily bookings report
- ✅ Monthly bookings report
- ✅ Room utilization report
- ✅ Export to PDF and Excel

#### Audit & Compliance
- ✅ Immutable audit trail for all activities
- ✅ Track user actions (who, what, when, where)
- ✅ Audit log filtering and search

### 4.2 Should Have (S) - Important but Not Critical

These requirements are important, but if not delivered, there are acceptable workarounds.

#### Enhanced User Experience
- 🔶 Multi-view calendar (day, week, month)
- 🔶 Drag-and-drop booking creation from calendar
- 🔶 Booking reminders (24 hours before meeting)
- 🔶 User profile picture upload
- 🔶 Dashboard widgets with statistics

#### Advanced Reporting
- 🔶 User booking history report
- 🔶 Export to CSV format
- 🔶 Print-friendly report layouts
- 🔶 Visual charts and graphs
- 🔶 Booking trend analysis

#### Room Management Enhancements
- 🔶 Maintenance scheduling with automatic status updates
- 🔶 Room utilization heatmaps
- 🔶 Peak hour analysis
- 🔶 Multiple room photo uploads (gallery)

#### Administrative Features
- 🔶 Bulk user import
- 🔶 User activity history view
- 🔶 System configuration UI
- 🔶 Maintenance mode toggle

### 4.3 Could Have (C) - Nice to Have

These are desirable features that can be de-scoped if time constraints arise.

#### User Convenience
- 🔵 In-app notifications (in addition to email)
- 🔵 Favorite rooms list
- 🔵 Quick re-book feature
- 🔵 Booking templates for recurring meetings
- 🔵 "Book similar room" suggestions

#### Reporting Enhancements
- 🔵 Customizable report templates
- 🔵 Scheduled report generation and delivery
- 🔵 Dashboard customization
- 🔵 Advanced filtering options

#### Administrative Tools
- 🔵 Bulk booking operations
- 🔵 Room clone feature
- 🔵 Import/export room data
- 🔵 System health monitoring dashboard

### 4.4 Won't Have (W) - Explicitly Out of Scope

These requirements will **not** be delivered in the current time box.

#### External Integrations
- ⛔ Outlook Calendar integration
- ⛔ Google Calendar sync
- ⛔ Zoom/Teams API integration
- ⛔ Active Directory SSO

#### Mobile Applications
- ⛔ iOS native app
- ⛔ Android native app
- ⛔ Progressive Web App (PWA)

#### Advanced Features
- ⛔ Equipment checkout management
- ⛔ Visitor/external guest booking
- ⛔ Catering integration
- ⛔ Payment processing
- ⛔ Multi-property support
- ⛔ Real-time chat/messaging
- ⛔ Video conferencing within app
- ⛔ Automatic no-show detection

---

## 5. Functional Requirements

### 5.1 User Management Module

**FR-UM-001**: The system SHALL allow System Administrators to create new user accounts with unique staff numbers and email addresses.

**FR-UM-002**: The system SHALL generate temporary passwords for new users and require password change on first login.

**FR-UM-003**: The system SHALL support four distinct user roles: Regular User, Administrator, Director, and System Administrator.

**FR-UM-004**: The system SHALL allow Directors and System Administrators to view, edit, and deactivate user accounts.

**FR-UM-005**: The system SHALL prevent deletion of users with associated booking history (soft delete only).

**FR-UM-006**: The system SHALL allow users to update their own profile information (name, department, phone).

**FR-UM-007**: The system SHALL allow users to change their own password after validating current password.

### 5.2 Authentication Module

**FR-AUTH-001**: The system SHALL authenticate users using email address and password.

**FR-AUTH-002**: The system SHALL create secure sessions with 30-minute inactivity timeout.

**FR-AUTH-003**: The system SHALL track failed login attempts and lock accounts temporarily after 5 consecutive failures.

**FR-AUTH-004**: The system SHALL provide password reset functionality via email with time-limited tokens (30 minutes).

**FR-AUTH-005**: The system SHALL log all login attempts (successful and failed) to the audit trail.

**FR-AUTH-006**: The system SHALL redirect users to role-appropriate dashboards after successful login.

### 5.3 Room Management Module

**FR-RM-001**: The system SHALL allow Administrators to add new meeting rooms with name, capacity, location, and amenities.

**FR-RM-002**: The system SHALL support three room statuses: Active, Inactive, and Under Maintenance.

**FR-RM-003**: The system SHALL allow Administrators to upload up to 5 photos per room (JPG/PNG, max 5MB each).

**FR-RM-004**: The system SHALL allow Administrators to schedule maintenance periods with start and end dates.

**FR-RM-005**: The system SHALL automatically update room status to Active when maintenance period ends.

**FR-RM-006**: The system SHALL prevent deletion of rooms with booking history (soft delete only).

**FR-RM-007**: The system SHALL display only Active rooms to Regular Users in search results.

**FR-RM-008**: The system SHALL show all rooms (including Inactive) to Administrators and Directors.

**FR-RM-009**: The system SHALL support amenity management (add, edit, delete, activate/deactivate).

**FR-RM-010**: The system SHALL display only active amenities in room creation/editing forms.

### 5.4 Booking Management Module

**FR-BM-001**: The system SHALL allow users to create one-time bookings with date, time, room, and purpose.

**FR-BM-002**: The system SHALL allow users to create recurring bookings with daily, weekly, or monthly patterns.

**FR-BM-003**: The system SHALL validate room availability in real-time before confirming bookings.

**FR-BM-004**: The system SHALL enforce booking duration limits (minimum 30 minutes, maximum 8 hours).

**FR-BM-005**: The system SHALL enforce operating hours restrictions (8:00 AM - 6:00 PM).

**FR-BM-006**: The system SHALL allow users to edit their own upcoming bookings before they occur.

**FR-BM-007**: The system SHALL allow users to cancel their own confirmed bookings.

**FR-BM-008**: The system SHALL allow Administrators to edit and cancel any booking.

**FR-BM-009**: The system SHALL confirm bookings immediately upon successful validation of room availability.

**FR-BM-010**: The system SHALL allow Administrators and Directors to provide oversight and resolve resource conflicts by canceling or rescheduling bookings.

**FR-BM-011**: The system SHALL automatically update booking status to Completed after end time passes.

**FR-BM-012**: The system SHALL generate unique booking reference numbers (e.g., BK-2025-00001).

**FR-BM-013**: The system SHALL link all occurrences of recurring bookings with a Series ID.

**FR-BM-014**: The system SHALL apply oversight actions (like cancellation) to all occurrences in a recurring series.

### 5.5 Calendar and Availability Module

**FR-CAL-001**: The system SHALL display room availability in calendar format with day, week, and month views.

**FR-CAL-002**: The system SHALL show available time slots in white and booked slots in color-coded blocks.

**FR-CAL-003**: The system SHALL display user's own bookings in distinct color (green).

**FR-CAL-004**: The system SHALL show maintenance periods with striped pattern indicator.

**FR-CAL-005**: The system SHALL display booking details (room, time, user) when Administrators hover over booking blocks.

**FR-CAL-006**: The system SHALL show only "Booked" status to Regular Users (no user information).

### 5.6 Notification Module

**FR-NOT-001**: The system SHALL send email notifications for new user account creation.

**FR-NOT-002**: The system SHALL send email notifications for password reset requests.

**FR-NOT-003**: The system SHALL send email notifications for booking confirmation.

**FR-NOT-004**: The system SHALL send email notifications for administrator-initiated booking changes or cancellations.

**FR-NOT-005**: The system SHALL send email notifications for booking cancellation.

**FR-NOT-006**: The system SHALL send email reminders 24 hours before booking start time.

**FR-NOT-007**: The system SHALL allow users to opt out of non-critical notifications (reminders only).

### 5.7 Reporting Module

**FR-REP-001**: The system SHALL generate Daily Bookings Reports showing all bookings for a selected date.

**FR-REP-002**: The system SHALL generate Monthly Bookings Reports with statistics and trend analysis.

**FR-REP-003**: The system SHALL generate User Bookings Reports showing individual or all-user booking history.

**FR-REP-004**: The system SHALL generate Room Utilization Reports with usage percentages and peak hours.

**FR-REP-005**: The system SHALL allow report export in PDF, Excel, and CSV formats.

**FR-REP-006**: The system SHALL support custom date range selection for all reports.

**FR-REP-007**: The system SHALL calculate utilization rates as (booked hours / available hours).

### 5.8 Audit Trail Module

**FR-AUD-001**: The system SHALL log all authentication events (login, logout, failed attempts, password resets).

**FR-AUD-002**: The system SHALL log all booking actions (created, edited, cancelled).

**FR-AUD-003**: The system SHALL log all room management actions (created, edited, status changed).

**FR-AUD-004**: The system SHALL log all user management actions (created, edited, role changed, deactivated).

**FR-AUD-005**: The system SHALL record timestamp, actor, action type, target entity, and IP address for all logs.

**FR-AUD-006**: The system SHALL ensure audit logs are immutable (cannot be edited or deleted).

**FR-AUD-007**: The system SHALL allow Directors and System Administrators to filter audit logs by date, actor, action type, and entity.

**FR-AUD-008**: The system SHALL allow audit log export to CSV and Excel formats.

**FR-AUD-009**: The system SHALL retain audit logs for minimum 2 years.

---

## 6. Non-Functional Requirements

### 6.1 Usability

**NFR-USA-001**: The system SHALL provide intuitive navigation requiring no more than 3 clicks to reach any feature.

**NFR-USA-002**: The system SHALL display clear validation error messages for all form inputs.

**NFR-USA-003**: The system SHALL provide consistent UI design across all pages using a unified theme.

**NFR-USA-004**: The system SHALL support responsive design for desktop (1920x1080), tablet (768px), and mobile (375px) viewports.

**NFR-USA-005**: The system SHALL provide loading indicators for all asynchronous operations exceeding 500ms.

**NFR-USA-006**: The system SHALL use consistent terminology throughout the application.

**NFR-USA-007**: The system SHALL provide help text and tooltips for complex features.

### 6.2 Reliability

**NFR-REL-001**: The system SHALL maintain 99.5% uptime during business hours (8:00 AM - 6:00 PM).

**NFR-REL-002**: The system SHALL perform automated database backups daily at 2:00 AM.

**NFR-REL-003**: The system SHALL implement database transactions to ensure data integrity during concurrent operations.

**NFR-REL-004**: The system SHALL gracefully handle and log all exceptions without exposing stack traces to users.

**NFR-REL-005**: The system SHALL recover from session failures without data loss.

**NFR-REL-006**: The system SHALL validate all user inputs server-side even if client-side validation exists.

### 6.3 Performance

**NFR-PERF-001**: The system SHALL load the dashboard within 2 seconds under normal network conditions.

**NFR-PERF-002**: The system SHALL return search results within 1 second for queries with up to 1000 rooms.

**NFR-PERF-003**: The system SHALL complete booking creation within 3 seconds.

**NFR-PERF-004**: The system SHALL generate reports within 5 seconds for date ranges up to 1 year.

**NFR-PERF-005**: The system SHALL support concurrent usage by up to 100 simultaneous users without degradation.

**NFR-PERF-006**: The system SHALL optimize database queries using indexes on frequently searched columns.

**NFR-PERF-007**: The system SHALL implement pagination for lists exceeding 50 items.

### 6.4 Supportability

**NFR-SUP-001**: The system SHALL implement comprehensive error logging to application log files.

**NFR-SUP-002**: The system SHALL include timestamp, user, action, and context in all log entries.

**NFR-SUP-003**: The system SHALL provide environment-specific configuration files (development, staging, production).

**NFR-SUP-004**: The system SHALL follow Laravel coding standards and best practices.

**NFR-SUP-005**: The system SHALL include inline code comments for complex business logic.

**NFR-SUP-006**: The system SHALL maintain separate logs for application errors, audit events, and system activities.

### 6.5 Design Constraints

**NFR-DES-001**: The system MUST be developed using PHP Laravel 10+ framework.

**NFR-DES-002**: The system MUST use PostgreSQL as the primary database.

**NFR-DES-003**: The system MUST implement MVC (Model-View-Controller) architecture pattern.

**NFR-DES-004**: The system MUST use Blade templating engine for views.

**NFR-DES-005**: The system MUST use Laravel's built-in authentication system.

**NFR-DES-006**: The system MUST follow RESTful API principles for all AJAX endpoints.

**NFR-DES-007**: The system MUST use Eloquent ORM for all database interactions (no raw SQL except for complex analytics).

### 6.6 Implementation Requirements

**NFR-IMP-001**: The system SHALL use Git for version control with feature branch workflow.

**NFR-IMP-002**: The system SHALL implement automated testing with minimum 70% code coverage.

**NFR-IMP-003**: The system SHALL use Composer for PHP dependency management.

**NFR-IMP-004**: The system SHALL use NPM for front-end asset management.

**NFR-IMP-005**: The system SHALL implement database migrations for schema changes.

**NFR-IMP-006**: The system SHALL use seeders for test data generation.

### 6.7 Interface Requirements

**NFR-INT-001**: The system SHALL provide RESTful API endpoints for all booking operations.

**NFR-INT-002**: The system SHALL return JSON responses for all AJAX requests.

**NFR-INT-003**: The system SHALL use SMTP for email delivery.

**NFR-INT-004**: The system SHALL support email configuration through environment variables.

**NFR-INT-005**: The system SHALL use HTTP/HTTPS protocols only.

### 6.8 Operation Requirements

**NFR-OPR-001**: The system SHALL run on Linux-based servers (Ubuntu 20.04+).

**NFR-OPR-002**: The system SHALL support deployment using standard web server configurations (Nginx/Apache).

**NFR-OPR-003**: The system SHALL use environment-based configuration for database credentials.

**NFR-OPR-004**: The system SHALL implement graceful shutdown procedures.

**NFR-OPR-005**: The system SHALL provide maintenance mode capability for system updates.

### 6.9 Business Rules

**NFR-BUS-001**: Bookings can be made for any future date (no maximum advance limit).

**NFR-BUS-002**: Operating hours are strictly 8:00 AM to 6:00 PM, Monday-Friday.

**NFR-BUS-003**: Minimum booking duration is 30 minutes.

**NFR-BUS-004**: Maximum booking duration is 8 hours.

**NFR-BUS-005**: Bookings must be within a single day (no overnight bookings).

**NFR-BUS-006**: Recurring bookings can span maximum 1 year from start date.

**NFR-BUS-007**: All bookings SHALL be automatically confirmed on a first-come-first-served basis (no manual approval required).

**NFR-BUS-008**: Users SHALL be allowed to edit or cancel their own upcoming bookings.

**NFR-BUS-009**: Users with system activity cannot be deleted (soft delete only).

**NFR-BUS-010**: Rooms with booking history cannot be permanently deleted.

### 6.10 Documentation Requirements

**NFR-DOC-001**: The system SHALL include comprehensive user manual for all user roles.

**NFR-DOC-002**: The system SHALL include administrator guide for system configuration.

**NFR-DOC-003**: The system SHALL include API documentation for all endpoints.

**NFR-DOC-004**: The system SHALL include database schema documentation with ER diagrams.

**NFR-DOC-005**: The system SHALL include deployment guide with step-by-step instructions.

### 6.11 Licensing and Legal Requirements

**NFR-LEG-001**: The system SHALL comply with OIB Group's data privacy policies.

**NFR-LEG-002**: The system SHALL store all personal data in accordance with data protection regulations.

**NFR-LEG-003**: The system SHALL use open-source software with permissive licenses (MIT, BSD).

**NFR-LEG-004**: The system SHALL not collect or store sensitive personal information beyond business requirements.

**NFR-LEG-005**: The system SHALL provide audit trail capabilities for compliance requirements.

### 6.12 Post-Development Requirements

**NFR-POST-001**: The system SHALL be deployed to production environment with zero downtime migration strategy.

**NFR-POST-002**: The system SHALL include 30 days of post-deployment support and bug fixes.

**NFR-POST-003**: The system SHALL provide user training sessions for all user roles.

**NFR-POST-004**: The system SHALL include 90 days of warranty period for critical defects.

**NFR-POST-005**: The system SHALL transfer knowledge to OIB Group IT team for ongoing maintenance.

---

## 7. System Requirements

### 7.1 Hardware Requirements

#### Server Infrastructure

**Processor:**
- Minimum: Intel Xeon E5-2620 v4 (8 cores) or AMD EPYC 7282 (16 cores)
- Recommended: Intel Xeon Gold 6248R (24 cores) or AMD EPYC 7402P (24 cores)
- Architecture: x86_64 (64-bit)

**Memory (RAM):**
- Minimum: 8 GB DDR4
- Recommended: 16 GB DDR4 or higher
- Database Server: Additional 4 GB dedicated to PostgreSQL

**Disk Storage:**
- System Drive: 50 GB SSD (for OS and application files)
- Database Drive: 100 GB SSD (for PostgreSQL data with room for growth)
- Backup Storage: 200 GB (for database backups and file uploads)
- File Storage: 50 GB (for room photos and uploaded documents)
- Total Recommended: 400 GB

**Network:**
- Network Interface Card (NIC): 1 Gbps Ethernet
- Internet Connection: 100 Mbps minimum dedicated bandwidth
- Internal Network: 1 Gbps LAN for database connectivity

#### Client Requirements (User Workstations)

**Processor:**
- Minimum: Intel Core i3 or AMD Ryzen 3
- Recommended: Intel Core i5 or AMD Ryzen 5

**Memory:**
- Minimum: 4 GB RAM
- Recommended: 8 GB RAM

**Display:**
- Minimum Resolution: 1366 x 768
- Recommended Resolution: 1920 x 1080 (Full HD)
- Touch Screen: Optional but supported

**Input Devices:**
- Keyboard and Mouse (standard)
- Touch screen support for tablets

**Network:**
- Ethernet: 100 Mbps or higher
- Wi-Fi: 802.11n or higher (5 GHz preferred)

### 7.2 Software Requirements

#### Server Software

**Operating System:**
- Ubuntu Server 20.04 LTS or 22.04 LTS (64-bit)
- Alternative: Debian 11+ or CentOS Stream 9+
- Windows Server NOT supported

**Web Server:**
- Nginx 1.18+ (Recommended)
- OR Apache HTTP Server 2.4+
- SSL/TLS: OpenSSL 1.1.1+ for HTTPS support

**Application Runtime:**
- PHP 8.1+ or PHP 8.2 (Recommended)
- Required PHP Extensions:
  - OpenSSL
  - PDO
  - Mbstring
  - Tokenizer
  - XML
  - Ctype
  - JSON
  - BCMath
  - Fileinfo
  - GD (for image processing)

**Framework:**
- Laravel 10.x LTS
- Composer 2.x (dependency manager)

**Database:**
- PostgreSQL 14+ or PostgreSQL 15+ (Recommended)
- Minimum Configuration:
  - Max Connections: 100
  - Shared Buffers: 256 MB
  - Effective Cache Size: 1 GB

**Background Services:**
- Laravel Queue Worker (for asynchronous jobs)
- Laravel Scheduler (for cron tasks)
- Supervisor (for process management)

**Email Service:**
- SMTP Server (internal or external)
- Example: Postfix, SendGrid, Mailgun, Amazon SES

**Version Control:**
- Git 2.x

#### Client Software (User Access)

**Web Browser (Required):**
- Google Chrome 90+ (Recommended)
- Mozilla Firefox 88+
- Microsoft Edge 90+
- Safari 14+ (macOS/iOS)

**Browser Requirements:**
- JavaScript: Enabled (required)
- Cookies: Enabled (required)
- Local Storage: Enabled (required)
- Pop-up Blocker: Configured to allow MRBS domain

**Unsupported Browsers:**
- Internet Explorer (all versions)
- Browsers older than 2 years from release date

**Optional Software:**
- PDF Reader: Adobe Acrobat Reader or browser built-in viewer
- Excel Viewer: Microsoft Excel, LibreOffice Calc, or Google Sheets (for exported reports)

### 7.3 Network Environment

**Network Architecture:**
- Local Area Network (LAN): 1 Gbps
- Internet Connection: Minimum 100 Mbps symmetric
- Dedicated VLAN for application servers (recommended for security)

**Firewall Configuration:**
- **Inbound Rules:**
  - Port 80 (HTTP) - for redirect to HTTPS
  - Port 443 (HTTPS) - for secure web access
  - Port 22 (SSH) - restricted to IT admin IPs only
  - Port 5432 (PostgreSQL) - restricted to application server only

- **Outbound Rules:**
  - Port 25/587 (SMTP) - for email notifications
  - Port 80/443 (HTTP/HTTPS) - for external API calls if needed

**Security Requirements:**
- SSL/TLS Certificate: Required for production (valid CA-signed certificate)
- VPN Access: Optional but recommended for administrative access
- DDoS Protection: Recommended for internet-facing deployment
- Intrusion Detection System (IDS): Recommended

**Load Balancing (Optional for High Availability):**
- Load Balancer: Nginx, HAProxy, or cloud-based (AWS ALB, Azure Load Balancer)
- Session Persistence: Required (sticky sessions)
- Health Check: Enabled with 30-second intervals

**Backup Network:**
- Dedicated backup network or schedule off-peak backups
- Bandwidth: Minimum 100 Mbps for backup transfers

### 7.4 Development Environment

**Required Tools:**
- Code Editor: Visual Studio Code, PHPStorm, or Sublime Text
- Database Client: DBeaver, pgAdmin, or TablePlus
- API Testing: Postman or Insomnia
- Version Control UI: GitKraken, SourceTree (optional)
- Terminal: WSL2 (Windows), iTerm2 (macOS), or native Linux terminal

**Development Stack:**
- Docker: Recommended for local development environment
- Laravel Homestead: Alternative virtualized environment
- Node.js 16+: For front-end asset compilation
- NPM or Yarn: Package manager

### 7.5 Production Deployment Requirements

**Deployment Strategy:**
- Zero-downtime deployment using blue-green or rolling deployment
- Automated deployment using CI/CD pipeline (GitHub Actions, GitLab CI, Jenkins)
- Environment-specific configuration using .env files

**Monitoring and Logging:**
- Application Performance Monitoring: New Relic, Datadog (optional)
- Error Tracking: Sentry, Bugsnag (optional)
- Log Aggregation: ELK Stack (Elasticsearch, Logstash, Kibana) or similar

**Backup Strategy:**
- Database: Daily automated backups with 30-day retention
- File Storage: Weekly backups with 90-day retention
- Disaster Recovery: Off-site backup replication

**High Availability Configuration (Optional):**
- Database: PostgreSQL replication (master-slave or master-master)
- Application: Multiple web server instances behind load balancer
- File Storage: Shared storage (NFS, S3) or distributed file system

---

## Document Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | December 28, 2025 | Development Team | Initial release of SRDD |

---

**End of Document**
