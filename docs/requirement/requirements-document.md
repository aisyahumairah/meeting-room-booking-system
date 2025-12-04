# Meeting Room Booking System (MRBS)
## System Requirements Document

**Prepared For:** Oriental Interest Group  
**Document Version:** 1.0  
**Date:** November 24, 2025  
**Status:** Draft for Initial Approval

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [System Overview](#2-system-overview)
3. [User Roles and Responsibilities](#3-user-roles-and-responsibilities)
4. [Phase 1: Foundation & Core Layout](#4-phase-1-foundation--core-layout)
5. [Phase 2: Meeting Rooms Management](#5-phase-2-meeting-rooms-management)
6. [Phase 3: Booking Management](#6-phase-3-booking-management)
7. [Phase 4: Administrative Management](#7-phase-4-administrative-management)
8. [Phase 5: System Quality & Deployment](#8-phase-5-system-quality--deployment)
9. [Glossary](#9-glossary)

---

## 1\. Executive Summary

This document outlines the requirements for a comprehensive **Meeting Room Booking System (MRBS)** designed to streamline and modernize OIB Group’s meeting room reservation process.

### 1.1 Business Problem

Currently, OIB Group manages meeting room bookings through manual processes using Excel spreadsheets and informal communication channels. This approach leads to:

- **Double bookings** and scheduling conflicts  
- **Time wastage** coordinating room availability manually  
- **Lack of visibility** into room utilization across properties  
- **No audit trail** for booking history and accountability  
- **Difficulty in planning** and resource optimization  
- **Manual reporting** that is time-consuming and prone to errors  
- **No transparency** in booking history and accountability  
- **Sabotage** of booking

### 1.2 Proposed Solution

The Meeting Room Booking System is a centralized web-based platform designed for **internal use by OIB Group staff**. The system streamlines the entire booking lifecycle—from room discovery to booking approval, usage reporting, and audit tracking.

**Important Note:** This is an internal resource management system with **no payment or billing features**. All meeting rooms are company-owned resources available to staff at no charge.

The system provides:

- **Real-time room availability** across all floors and rooms  
- **Approval workflows** with notifications  
- **Centralized booking management** accessible from anywhere  
- **Comprehensive reporting** for data-driven decision making  
- **Complete audit trail** for transparency and accountability  
- **User-friendly interface** requiring minimal training

### 1.3 Key Benefits

| Benefit Category | Key Outcomes |
| :---- | :---- |
| **Operational Efficiency** | Reduce booking time from hours to minutes; eliminate double bookings; approval processes |
| **Cost Optimization** | Identify underutilized rooms; optimize space allocation; reduce administrative overhead |
| **User Experience** | Self-service booking; streamlined confirmation; mobile-friendly access |
| **Management Insights** | Real-time utilization reports; trend analysis; resource planning data; export-ready reports |
| **Governance & Compliance** | Complete activity logs; approval workflows; role-based access control; audit-ready records |

### 1.4 System Capabilities at a Glance

The system supports **four user roles** (Regular Users, Administrators, Directors, and System Administrators) and delivers **27 feature modules** across **three core subsystems**:

1. **Meeting Rooms Management** \- Inventory, capacity, location, and availability tracking  
2. **Booking Management** \- Creation, modification, approval, and calendar views  
3. **Administrative Management** \- User management, notifications, reporting, and system configuration

### 1.5 Implementation Approach

The system will be developed in **five progressive phases** over an estimated timeline, starting with essential authentication and dashboards, then building out room management, booking workflows, administrative tools, and finally polishing the user experience.

This phased approach allows for early feedback, iterative improvements, and ensures core functionality is delivered first.

---

## 2\. System Overview

### 2.1 Purpose

The Meeting Room Booking System (MRBS) is designed to provide OIB Group with a modern, efficient, and transparent platform for managing meeting room reservations across all properties.

The system aims to:

- **Eliminate scheduling conflicts** through centralized, real-time availability tracking  
- **Reduce administrative burden** by automating manual booking and approval processes  
- **Improve resource utilization** through data-driven insights and analytics  
- **Enhance user experience** with intuitive self-service booking capabilities  
- **Ensure accountability** through comprehensive audit trails and activity logging  
- **Support decision-making** with actionable reports on room usage patterns and trends

### 2.2 Current Situation

OIB Group currently manages meeting room bookings through a combination of manual processes:

#### Pain Points:

1. **Excel-Based Tracking**  
     
   - Multiple spreadsheet versions causing confusion  
   - No real-time updates when bookings change  
   - Difficult to view availability at a glance  
   - Manual entry prone to human error

   

2. **Communication Overhead**  
     
   - Email/phone calls to check room availability  
   - Time-consuming approval processes  
   - Lost messages or miscommunication  
   - Delayed confirmations

   

3. **Operational Challenges**  
     
   - Double bookings due to lack of synchronization  
   - No prevention of conflicting reservations  
   - Difficulty tracking who booked what and when  
   - Unable to enforce booking policies consistently

   

4. **Reporting Limitations**  
     
   - Manual compilation of usage reports  
   - Time-consuming data aggregation  
   - No centralized view of room usage  
   - Inability to generate standardized reports

   

5. **Lacking Accountability & Security**  
     
   - No comprehensive audit trail  
   - Anyone can edit the Excel file  
   - Bookings can be overridden or deleted by others (sabotage)  
   - No access control or permissions  
   - Difficult to track who made changes  
   - No protection against malicious modifications  
   - Limited visibility into cancellation reasons

### 2.3 Proposed Solution

The MRBS addresses these challenges through a centralized web-based platform with the following capabilities:

#### Core Capabilities:

1. **Centralized Room Repository**  
     
   - Single source of truth for all meeting rooms within the building  
   - Detailed room information (capacity, floor/location, amenities)  
   - Real-time availability status  
   - Organized by floor and room number for easy navigation

   

2. **Intelligent Booking System**  
     
   - Prevent double bookings automatically  
   - Check availability in real-time before confirmation  
   - Support one-time and recurring bookings (daily, weekly, monthly patterns)  
   - Flexible date/time selection with calendar views  
   - No payment or billing required (internal resource)

   

3. **Manual Approval Workflow**  
     
   - Route booking requests to administrators/directors for review  
   - Administrators manually approve or reject requests  
   - Email notifications sent at every status change  
   - Track approval timeline and decisions  
   - Document rejection reasons for transparency

   

4. **Self-Service User Portal (Responsive Web)**  
     
   - Staff can search and book rooms independently via web browser  
   - Works on desktop, tablet, and mobile devices (responsive design)  
   - View personal booking history  
   - Modify or cancel own bookings with policy enforcement  
   - Receive email confirmations and notifications

   

5. **Usage Reporting**  
     
   - Daily and monthly booking reports  
   - Room utilization reports showing usage percentages  
   - User booking history reports  
   - Custom date range selection  
   - Export reports in PDF, Excel, and CSV formats

   

6. **Complete Audit Trail**  
     
   - Immutable log of all system activities  
   - Track who did what and when  
   - Support compliance and accountability requirements  
   - Enable forensic analysis if needed

   

7. **Role-Based Access Control & Security**  
     
   - Four distinct user roles with appropriate permissions  
   - Prevent unauthorized modifications to bookings  
   - Only booking owners can edit/cancel their own bookings  
   - Administrators and directors have oversight capabilities  
   - Maintain security and data integrity

---

## 3\. User Roles and Responsibilities

The Meeting Room Booking System supports **four distinct user roles**, each with specific permissions and responsibilities designed to balance self-service convenience with proper oversight and control.

### 3.1 Regular User

**Who They Are:**

- All staff members of OIB Group  
- Default role assigned upon account creation  
- Majority of system users

**What They Can Do:**

- **Search and browse** meeting rooms by capacity, floor, and availability  
- **View room details** including capacity, location, and amenities  
- **Create bookings** for themselves (one-time or recurring)  
- **View personal bookings** \- see their own booking history and upcoming reservations  
- **Edit own bookings** \- modify date, time, room, or purpose (ONLY for Pending/draft status)  
- **Cancel own bookings** \- cancel with a reason (for Pending or Confirmed bookings)  
- **Receive notifications** \- email alerts for booking confirmations, approvals, rejections, and reminders  
- **Update profile** \- manage personal information and change password

**What They CANNOT Do:**

- Cannot approve or reject any bookings (including their own)  
- Cannot view other users' bookings  
- Cannot edit bookings that are Confirmed, Rejected, Cancelled, or past  
- Cannot manage rooms, users, or system settings  
- Cannot access reports or audit logs  
- Cannot create bookings on behalf of others

**Primary Use Case:**  
Staff members who need to book meeting rooms for their own meetings and manage their reservations.

---

### 3.2 Administrator

**Who They Are:**

- Administrative staff or office managers  
- Assigned by System Administrators  
- Primary approvers for booking requests

**What They Can Do:**

*All Regular User capabilities, PLUS:*

**Booking Management:**

- **View all bookings** \- see bookings from all users across the system  
- **Approve/reject booking requests** \- primary responsibility for processing pending bookings  
- **Edit any booking** \- modify any user's booking details  
- **Cancel any booking** \- cancel bookings on behalf of users or due to conflicts  
- **Create bookings on behalf of others** \- book rooms for staff who request assistance

**Room Management:**

- **Add new rooms** \- create new meeting room entries  
- **Edit room details** \- update capacity, location, amenities, status  
- **Set room status** \- mark rooms as Active, Inactive, or Under Maintenance  
- **Schedule maintenance periods** \- block rooms for specific date ranges  
- **Delete/deactivate rooms** \- remove rooms from the system (with constraints)

**Reporting:**

- **Generate daily reports** \- view bookings for specific dates  
- **Generate monthly reports** \- analyze usage patterns over months  
- **Generate user booking reports** \- view individual user booking history  
- **Generate room utilization reports** \- see usage percentages and peak hours  
- **Export reports** \- download in PDF, Excel, or CSV formats  
- **Print reports** \- access print-friendly layouts

**What They CANNOT Do:**

- Cannot manage users (view, create, assign roles, deactivate)  
- Cannot view audit trail logs  
- Cannot configure system-wide settings

**Primary Use Case:**  
Process booking approvals daily, manage room inventory, generate usage reports, and assist users with booking issues.

---

### 3.3 Director

**Who They Are:**

- Department heads, senior management, or executives  
- Assigned by System Administrators  
- Oversight and governance role

**What They Can Do:**

*All Administrator capabilities, PLUS:*

**User Management:**

- **View all users** \- see complete list of registered users  
- **Create new user accounts** \- register users manually if needed  
- **Assign/modify user roles** \- promote users to Administrator, or assign Director/System Admin roles (with approval)  
- **Deactivate/delete users** \- remove user access (with data integrity constraints)  
- **Reset user passwords** \- assist users with account recovery  
- **View user statistics** \- see user counts by role and activity levels

**Audit & Compliance:**

- **View complete audit trail** \- access immutable logs of all system activities  
- **Filter audit logs** \- search by date, user, event type, or entity  
- **Export audit logs** \- download for compliance or forensic analysis  
- **View user activity history** \- detailed view of individual user actions

**Approval Authority:**

- Can approve/reject bookings (passive role \- Administrators handle day-to-day approvals)  
- Focus on oversight rather than routine operations

**What They CANNOT Do:**

- Cannot configure system-wide settings (booking limits, operating hours, session timeouts)  
- Limited to oversight; System Administrators handle technical configuration

**Primary Use Case:**  
Monitor system usage, manage user accounts and roles, review audit trails for accountability, and provide executive oversight of booking operations.

---

### 3.4 System Administrator

**Who They Are:**

- IT staff or technical administrators  
- Highest technical privilege level  
- Responsible for system configuration and maintenance

**What They Can Do:**

**Personal Booking (Regular User capabilities):**

- Can book rooms for themselves  
- Manage their own bookings

**User Management:**

- **Full user management** \- create, edit, deactivate, delete users  
- **Assign any role** \- including Administrator, Director, and System Administrator roles  
- **Reset passwords** \- help users with account access

**System Configuration:**

- **Configure booking rules:**  
  - Minimum/maximum booking duration  
  - Booking cancellation deadline  
  - Room operating hours  
- **Configure session settings:**  
  - Session timeout period  
  - Login attempt limits  
- **Configure notifications:**  
  - Enable/disable email notification types  
  - Customize email templates (if implemented)  
- **System maintenance mode** \- enable/disable system access for maintenance

**Reporting & Audit:**

- **View all reports** \- access all report types  
- **View audit trail** \- complete system activity logs  
- **Export audit logs** \- for backup or analysis  
- **Monitor system health** \- track system performance and errors (if implemented)

**What They CANNOT Do:**

- Cannot approve or reject booking requests (not part of booking workflow)  
- Cannot manage rooms (add, edit, delete rooms)  
- Cannot edit or cancel other users' bookings  
- Focused on system administration, not operational booking management

**Primary Use Case:**  
Configure system-wide settings, manage user accounts and roles, maintain system security, and ensure proper system operation.

---

### 3.5 User Role Comparison

The following table provides a comprehensive comparison of permissions across all four user roles:

| Capability | Regular User | Administrator | Director | System Admin |
| :---- | :---- | :---- | :---- | :---- |
| **PERSONAL BOOKING** |  |  |  |  |
| Search & browse rooms | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes |
| Create own booking | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes |
| View own bookings | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes |
| Edit own bookings (Pending only) | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes |
| Cancel own bookings | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes |
| **BOOKING MANAGEMENT** |  |  |  |  |
| View all bookings | ❌ No | ✅ Yes | ✅ Yes | ❌ No |
| Approve/reject bookings | ❌ No | ✅ Yes (Primary) | ✅ Yes (Passive) | ❌ No |
| Edit any user's booking | ❌ No | ✅ Yes | ✅ Yes | ❌ No |
| Cancel any booking | ❌ No | ✅ Yes | ✅ Yes | ❌ No |
| Create booking for others | ❌ No | ✅ Yes | ✅ Yes | ❌ No |
| **ROOM MANAGEMENT** |  |  |  |  |
| View room details | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes |
| Add/edit rooms | ❌ No | ✅ Yes | ✅ Yes | ❌ No |
| Set room status | ❌ No | ✅ Yes | ✅ Yes | ❌ No |
| Delete rooms | ❌ No | ✅ Yes | ✅ Yes | ❌ No |
| Schedule maintenance | ❌ No | ✅ Yes | ✅ Yes | ❌ No |
| **USER MANAGEMENT** |  |  |  |  |
| View all users | ❌ No | ❌ No | ✅ Yes | ✅ Yes |
| Create users | ❌ No | ❌ No | ✅ Yes | ✅ Yes |
| Assign/modify roles | ❌ No | ❌ No | ✅ Yes | ✅ Yes |
| Deactivate/delete users | ❌ No | ❌ No | ✅ Yes | ✅ Yes |
| Reset passwords | ❌ No | ❌ No | ✅ Yes | ✅ Yes |
| **REPORTING** |  |  |  |  |
| View reports | ❌ No | ✅ Yes | ✅ Yes | ✅ Yes |
| Generate daily reports | ❌ No | ✅ Yes | ✅ Yes | ✅ Yes |
| Generate monthly reports | ❌ No | ✅ Yes | ✅ Yes | ✅ Yes |
| Generate utilization reports | ❌ No | ✅ Yes | ✅ Yes | ✅ Yes |
| Export reports (PDF/Excel/CSV) | ❌ No | ✅ Yes | ✅ Yes | ✅ Yes |
| **AUDIT & COMPLIANCE** |  |  |  |  |
| View audit trail | ❌ No | ❌ No | ✅ Yes | ✅ Yes |
| Filter/search audit logs | ❌ No | ❌ No | ✅ Yes | ✅ Yes |
| Export audit logs | ❌ No | ❌ No | ✅ Yes | ✅ Yes |
| View user activity history | ❌ No | ❌ No | ✅ Yes | ✅ Yes |
| **SYSTEM CONFIGURATION** |  |  |  |  |
| Configure system settings | ❌ No | ❌ No | ❌ No | ✅ Yes |
| Set booking rules/limits | ❌ No | ❌ No | ❌ No | ✅ Yes |
| Configure notification settings | ❌ No | ❌ No | ❌ No | ✅ Yes |
| System maintenance mode | ❌ No | ❌ No | ❌ No | ✅ Yes |
| **NOTIFICATIONS** |  |  |  |  |
| Receive booking notifications | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes |
| Receive approval notifications | ❌ No | ✅ Yes | ✅ Yes | ❌ No |

### 3.6 Role Assignment & Security

**Role Assignment Process:**

1. System Administrators create new user accounts with **Regular User** role by default  
2. System Administrators assign elevated roles (Administrator, Director, System Admin)  
3. Directors can also assign roles with appropriate approval  
4. Role changes are logged in the audit trail

**Security Principles:**

- **Principle of Least Privilege**: Users only have access to features necessary for their role  
- **Separation of Duties**: System Admins configure settings but don't manage bookings; Administrators manage bookings but can't configure system settings  
- **No Self-Elevation**: Users cannot change their own roles  
- **Audit Trail**: All role assignments and changes are logged

**Typical Organizational Setup:**

- **Regular Users**: 80-90% of staff  
- **Administrators**: 2-5 office managers or administrative assistants  
- **Directors**: 1-3 senior management or department heads  
- **System Administrators**: 1-2 IT staff members

---

## 4\. Phase 1: Foundation & Core Layout

### 4.1 Phase Overview

**Objective:**  
Establish the foundational elements of the MRBS including user authentication, role-based dashboards, and global navigation structure. This phase creates the entry point to the system and ensures users can securely access features appropriate to their role.

**Deliverables:**

- User login system  
- Password recovery mechanism  
- Role-specific dashboards (Regular User, Administrator, Director, System Administrator)  
- Global layout with navigation sidebar, top navbar, and footer  
- Session management and security controls

**Business Value:**

- Secure access control prevents unauthorized use  
- Role-based dashboards provide personalized experience  
- Centralized user management ensures security  
- Consistent navigation improves user experience

---

### 4.2 Authentication & Access Control

#### 4.2.1 User Account Creation (Admin Only)

**Purpose:**  
Enable System Administrators to create new staff accounts securely, ensuring only authorized personnel have access to the system.

**Requirements:**

**MUST:**

- Provide "Create User" interface accessible **ONLY** to System Administrators  
- Require the following fields for new account creation:  
  - **Staff Number** (unique identifier, mandatory)  
  - **Full Name** (mandatory)  
  - **Email Address** (mandatory, must be unique)  
  - **Department/Division** (optional)  
  - **Phone Number** (optional)  
  - **Role** (default to "Regular User", selectable if creating admin)  
- Validate that staff number is unique across the system  
- Validate that email address is unique  
- Validate email address format  
- **Generate a temporary password** automatically OR allow admin to set an initial password  
- **Require password change** on first login  
- Create user record in database immediately

**SHALL:**

- Display clear validation error messages  
- Send welcome email to user's registered email address containing:  
  - Login URL  
  - Username/Email  
  - Temporary password (if auto-generated)  
  - Instructions to change password on first login

**Business Benefit:**  
Ensures strict control over system access and eliminates the risk of unauthorized registrations.

**Important Note:**  
Self-registration is **NOT** supported. All accounts must be provisioned by System Administrators.

---

#### 4.2.2 Secure Login

**Purpose:**  
Authenticate users and establish secure sessions with appropriate access based on their assigned role.

**Requirements:**

**MUST:**

- Provide login form with the following fields:  
  - **Email Address** (mandatory)  
  - **Password** (mandatory)  
- Validate credentials against database  
- Create secure session upon successful authentication  
- Store session token securely (server-side)  
- Automatically log out users after **30 minutes of inactivity**  
- Redirect users to appropriate dashboard based on role:  
  - Regular User → User Dashboard  
  - Administrator → Admin Dashboard  
  - Director → Admin Dashboard (with additional menu options)  
  - System Administrator → User Dashboard (with admin menu options)  
- Display last successful login date/time after authentication  
- Log all login attempts (successful and failed) for security auditing

**SHALL:**

- Display generic error message for failed login (e.g., "Invalid email or password") without revealing which field is incorrect  
- Clear any existing session when user logs in again  
- Provide "Forgot Password?" link on login page

**SHOULD:**

- Display loading indicator during authentication process  
- Auto-focus on email field when page loads  
- Show/hide password toggle button

**MUST NOT:**

- Include "Remember Me" functionality (session security requirement)  
- Implement permanent account lockout (temporary cooldown only)

**Business Benefit:**  
Secure authentication prevents unauthorized access while session timeout ensures abandoned sessions don't pose security risks.

---

#### 4.2.3 Forgot Password / Password Reset

**Purpose:**  
Allow users to securely reset their password if forgotten, without requiring administrator intervention.

**Requirements:**

**MUST:**

- Provide "Forgot Password" page with email input field  
- Validate that email exists in the system  
- Generate unique, secure password reset token  
- Set token expiration to **30 minutes** from generation time  
- Send password reset email containing:  
  - Reset link with embedded token  
  - Token expiration time  
  - Instructions for resetting password  
  - Warning not to share the link  
- Provide password reset page that:  
  - Validates token is valid and not expired  
  - Allows user to enter new password  
  - Requires password confirmation  
  - Validates new password meets security requirements  
- Invalidate token immediately after successful password reset  
- Update password in database (securely hashed)  
- Redirect to login page after successful reset

**SHALL:**

- Display success message after sending reset email (even if email doesn't exist \- security measure)  
- Display clear error if token is invalid or expired  
- Provide link to request new reset token if expired  
- Log all password reset requests and completions in audit trail

**SHOULD:**

- Display countdown timer showing token expiration time on reset page  
- Send confirmation email after password is successfully changed

**Business Benefit:**  
Self-service password reset reduces help desk burden and allows users to regain access quickly without waiting for administrator assistance.

---

#### 4.2.4 User Profile Management

**Purpose:**  
Allow users to view and update their personal information and change their password.

**Requirements:**

**MUST:**

- Display user profile page showing:  
  - Staff Number (read-only)  
  - Full Name (editable)  
  - Email Address (read-only)  
  - Department/Division (editable)  
  - Phone Number (editable)  
  - Role (read-only, displayed as badge)  
  - Account Creation Date (read-only)  
- Allow users to update editable fields  
- Provide "Change Password" section requiring:  
  - Current password (for verification)  
  - New password (with validation)  
  - Confirm new password  
- Validate current password before allowing change  
- Validate new password meets security requirements  
- Update user information in database  
- Display success message after saving changes

**SHALL:**

- Show password strength indicator for new password  
- Require confirmation before saving changes  
- Log profile updates in audit trail

**SHOULD:**

- Display last profile update timestamp  
- Allow profile picture/avatar upload (optional)

**Business Benefit:**  
Users can maintain their own information, reducing administrative workload and ensuring data accuracy.

---

### 4.3 Dashboard & Navigation

#### 4.3.1 Administrative Dashboard

**Purpose:**  
Provide administrators and directors with an at-a-glance overview of system status, pending tasks, and key metrics to facilitate daily operations.

**Target Roles:** Administrator, Director

**Requirements:**

**MUST Display:**

1. **Pending Approvals Widget**  
     
   - Count of bookings awaiting approval  
   - Badge indicator showing number (e.g., "4 Pending")  
   - "View All" button linking to approval queue  
   - Highlight if count exceeds threshold (e.g., \>10)

   

2. **Today's Bookings Widget**  
     
   - Total number of bookings scheduled for today  
   - Breakdown by status (Confirmed, Pending)  
   - List of next 5 upcoming bookings with:  
     - Time  
     - Room name  
     - User name  
   - "View Calendar" button

   

3. **Booking Statistics Widget**  
     
   - Total bookings this week  
   - Total bookings this month  
   - Comparison with previous period (e.g., "+15% vs last week")  
   - Visual chart showing booking trend

   

4. **Room Utilization Chart**  
     
   - Bar chart or pie chart showing:  
     - Most booked rooms (top 5\)  
     - Booking count or percentage for each  
   - Time period selector (This Week, This Month)

   

5. **Quick Actions Panel**  
     
   - "Approve Bookings" button (links to approval queue)  
   - "View All Bookings" button  
   - "Generate Report" button  
   - "Add New Room" button (Administrator/Director only)

**SHALL:**

- Update statistics in real-time or refresh every 60 seconds  
- Use visual indicators (icons, colors, charts) for quick comprehension  
- Provide responsive layout that works on all screen sizes  
- Link all widgets to detailed views

**SHOULD:**

- Display system alerts or announcements (if any)

**Business Benefit:**  
Administrators can quickly assess system status, identify pending tasks, and make informed decisions without navigating through multiple pages.

---

#### 4.3.2 User Dashboard

**Purpose:**  
Provide regular users with a personalized view of their bookings and quick access to create new reservations.

**Target Roles:** Regular User, System Administrator (for personal bookings)

**Requirements:**

**MUST Display:**

1. **Upcoming Bookings Widget**  
     
   - List of user's upcoming bookings (next 5 or all within 7 days)  
   - For each booking show:  
     - Date and time  
     - Room name and location  
     - Status (Pending, Confirmed)  
     - Duration  
   - "View All My Bookings" button  
   - Empty state message if no upcoming bookings

   

2. **Past Bookings Widget**  
     
   - List of user's recent past bookings (last 5\)  
   - For each booking show:  
     - Date and time  
     - Room name  
     - Status (Completed, Cancelled)  
   - "View Full History" button

   

3. **Quick Book Button**  
     
   - Prominent "Book a Room" button  
   - Links directly to booking creation page  
   - Visually distinct (primary color, larger size)

   

4. **My Bookings Chart**  
     
   - Visual chart showing user's booking history:  
     - Bar chart: Bookings per month (last 6 months)  
     - OR Pie chart: Bookings by status (Confirmed, Pending, Cancelled, Completed)  
   - Shows booking patterns at a glance

   

5. **Quick Stats**  
     
   - Total bookings made (all time)  
   - Bookings this month  
   - Pending approvals count  
   - Cancelled bookings count

**SHALL:**

- Highlight bookings happening today or within 24 hours  
- Use status badges with color coding:  
  - Pending: Yellow/Orange  
  - Confirmed: Green  
  - Cancelled: Red  
  - Completed: Gray  
- Provide direct links to view/edit bookings (where applicable)  
- Show empty state with helpful message if user has no bookings

**SHOULD:**

- Display booking reminders for bookings starting within 1 hour  
- Show room availability summary (e.g., "5 rooms available now")

**Business Benefit:**  
Users can quickly see their booking schedule, track approval status, and create new bookings without navigating through complex menus.

---

### 4.4 Global Navigation Structure

**Purpose:**  
Provide consistent, role-appropriate navigation throughout the application.

**Requirements:**

**Navigation Components:**

1. **Sidebar Menu** (Collapsible)  
     
   **All Roles See:**  
     
   - **Dashboards** (submenu)  
     - Admin Dashboard (Admin/Director only)  
     - User Dashboard (All roles)

   

   **Meeting Rooms Section:**

   

   - Room Inventory (All roles can view; Admin/Director can manage)  
   - Search Rooms (All roles)

   

   **Bookings Section:**

   

   - Calendar (All roles)  
   - My Bookings (All roles)  
   - All Bookings (Admin/Director only)  
   - Approvals (Admin/Director only) \- with badge showing pending count

   

   **Reports Section:** (Admin/Director/System Admin only)

   

   - Reports Hub

   

   **Administration Section:**

   

   - User Management (Director/System Admin only)  
   - System Settings (System Admin only)

   

2. **Top Navbar**  
     
   - Logo and "MRBS" branding (left side)  
   - Search bar (center) \- global search for rooms/bookings  
   - User profile dropdown (right side):  
     - User name and role badge  
     - "My Profile" link  
     - "Settings" link (personal settings)  
     - "Log Out" link

   

3. **Footer**  
     
   - Copyright notice  
   - System version (optional)  
   - Links to help/support (optional)

**Visibility Rules:**

| Menu Item | Regular User | Administrator | Director | System Admin |
| :---- | :---- | :---- | :---- | :---- |
| User Dashboard | ✅ | ✅ | ✅ | ✅ |
| Admin Dashboard | ❌ | ✅ | ✅ | ❌ |
| Room Inventory | ✅ View | ✅ Manage | ✅ Manage | ✅ View |
| Search Rooms | ✅ | ✅ | ✅ | ✅ |
| Calendar | ✅ | ✅ | ✅ | ✅ |
| My Bookings | ✅ | ✅ | ✅ | ✅ |
| All Bookings | ❌ | ✅ | ✅ | ❌ |
| Approvals | ❌ | ✅ | ✅ | ❌ |
| Reports Hub | ❌ | ✅ | ✅ | ✅ |
| User Management | ❌ | ❌ | ✅ | ✅ |
| System Settings | ❌ | ❌ | ❌ | ✅ |

**SHALL:**

- Highlight active menu item  
- Show badge on "Approvals" menu item with pending count (Admin/Director only)  
- Collapse sidebar on mobile devices  
- Provide menu toggle button for mobile  
- Maintain menu state (expanded/collapsed) during session

**Business Benefit:**  
Role-based navigation ensures users only see relevant options, reducing confusion and improving efficiency. Consistent layout across all pages improves learnability.

---

## 5\. Phase 2: Meeting Rooms Management

### 5.1 Phase Overview

**Objective:**  
Enable users to discover and view meeting rooms, and provide administrators with tools to manage the complete room inventory including attributes, status, and availability.

**Deliverables:**

- Room inventory browsing (grid view for users, list view for admin management)  
- Advanced search and filtering capabilities  
- Detailed room information pages with availability calendar  
- Room administration interface (add, edit, deactivate, delete)  
- Room status management (Active, Inactive, Under Maintenance)

**Business Value:**

- Users can easily find suitable rooms based on their requirements  
- Centralized room database eliminates confusion and duplication  
- Real-time availability reduces booking conflicts  
- Administrators can maintain accurate room information  
- Maintenance scheduling prevents bookings during unavailable periods

---

### 5.2 Room Inventory

#### 5.2.1 Room Inventory Browsing (User View)

**Purpose:**  
Allow all users to browse available meeting rooms in an intuitive, visual format.

**Requirements:**

**MUST:**

- Display all active meeting rooms in a **grid view** with room cards  
- Each room card MUST show:  
  - Room photo/image (default image if none uploaded)  
  - Room name/identifier  
  - Capacity (e.g., "Seats 12")  
  - Floor/location (e.g., "3rd Floor")  
  - Current status badge (Available, Booked, Under Maintenance)  
  - Key amenities icons (projector, whiteboard, video conferencing)  
- Provide "View Details" button on each card linking to room detail page  
- Show only **Active** rooms by default (hide Inactive rooms)  
- Display total number of rooms found  
- Support responsive layout (adjust grid columns based on screen size)

**SHALL:**

- Use visual status indicators:  
  - Available: Green badge/border  
  - Currently Booked: Orange badge  
  - Under Maintenance: Red badge  
- Show placeholder image for rooms without photos  
- Provide hover effects on room cards for better interactivity  
- Display "No rooms found" message if search/filter yields no results

**SHOULD:**

- Allow sorting by:  
  - Room name (A-Z)  
  - Capacity (smallest to largest, or vice versa)  
  - Recently added  
- Display amenities as icons with tooltips

**Business Benefit:**  
Visual grid layout helps users quickly scan available rooms and identify suitable options based on capacity and amenities at a glance.

---

#### 5.2.2 Room Inventory Management (Admin View)

**Purpose:**  
Provide administrators and directors with a comprehensive list view for efficient room management.

**Target Roles:** Administrator, Director

**Requirements:**

**MUST:**

- Display all rooms (including Inactive) in a **table/list view** with columns:  
  - Room Name  
  - Capacity  
  - Floor/Location  
  - Status (Active, Inactive, Under Maintenance)  
  - Amenities (summarized)  
  - Actions (View, Edit, Delete/Deactivate buttons)  
- Provide "Add New Room" button prominently at top of page  
- Allow sorting by any column (ascending/descending)  
- Show total room count and breakdown by status (X Active, Y Inactive, Z Under Maintenance)  
- Provide quick actions dropdown for each room:  
  - View Details  
  - Edit Room  
  - Change Status (Active/Inactive/Maintenance)  
  - Delete Room (if allowed)

**SHALL:**

- Use color-coded status badges for visual clarity  
- Implement confirmation dialog before deleting or deactivating rooms  
- Display warning if attempting to change status of room with future bookings  
- Provide search box to filter rooms by name

**SHOULD:**

- Support bulk actions (select multiple rooms to deactivate/activate)  
- Export room list to CSV/Excel for reference

**Business Benefit:**  
Centralized management interface allows administrators to maintain room inventory efficiently with full visibility and quick access to editing functions.

---

### 5.3 Room Discovery

#### 5.3.1 Room Search and Filtering

**Purpose:**  
Help users quickly find meeting rooms that meet their specific requirements using multiple filter criteria.

**Requirements:**

**MUST Provide Filters:**

1. **Capacity Filter**  
     
   - Minimum capacity selector (dropdown or number input)  
   - Options: 2, 4, 6, 8, 10, 12, 15, 20, 25+ people  
   - Filter shows rooms with capacity \>= selected value

   

2. **Date & Time Availability Filter**  
     
   - Date picker for booking date  
   - Time range selector:  
     - Start time (dropdown in 30-minute increments)  
     - End time (dropdown in 30-minute increments)  
   - Filter shows only rooms available during selected period  
   - Real-time availability check against existing bookings

   

3. **Amenities/Equipment Filter**  
     
   - Multi-select checkboxes for:  
     - Projector/Screen  
     - Whiteboard  
     - Video Conferencing Equipment  
     - Teleconferencing Phone  
     - Computer/Monitor  
     - Flip Chart  
     - Air Conditioning  
     - Natural Light/Windows  
   - Filter shows rooms with ALL selected amenities (AND logic)

   

4. **Status Filter (Admin/Director Only)**  
     
   - Default: Show Active rooms only  
   - Option to include Inactive or Under Maintenance rooms

**MUST:**

- Apply filters immediately when changed (live filtering) or provide "Apply Filters" button  
- Display number of results matching current filters (e.g., "15 rooms found")  
- Provide "Clear All Filters" button to reset  
- Preserve filter selections during browsing session  
- Show empty state with suggestions if no rooms match filters

**SHALL:**

- Display applied filters as removable tags/chips  
- Validate that start time is before end time  
- Prevent date selection in the past  
- Return results within 2 seconds for typical queries

**SHOULD:**

- Remember frequently used filters for quick access  
- Suggest alternative criteria if search yields few results  
- Provide visual feedback while filters are being applied

**Business Benefit:**  
Advanced filtering reduces time spent searching for suitable rooms from minutes to seconds, improving user productivity and booking accuracy.

---

#### 5.3.2 Room Availability Display

**Purpose:**  
Show real-time availability status of meeting rooms to prevent conflicts and inform booking decisions.

**Requirements:**

**MUST Provide:**

1. **Quick Availability Indicator (on Room Cards/List)**  
     
   - Current status: "Available Now", "Booked", "Under Maintenance"  
   - Based on current date/time  
   - Update in real-time or refresh every minute

   

2. **Room Availability Calendar (on Room Detail Page)**  
     
   - Display calendar showing bookings for selected room  
   - Default view: Week view showing Monday-Friday  
   - Time slots displayed in hourly or 30-minute increments  
   - Operating hours: 8:00 AM \- 6:00 PM (configurable by system admin)  
   - Show booked time slots clearly:  
     - **For Regular Users**: Display as "Booked" (no user information)  
     - **For Administrators/Directors**: Display booker name and booking purpose  
   - Show available time slots in contrasting color  
   - Show maintenance periods with distinct visual indicator  
   - Allow navigation to different weeks (Previous Week, Next Week, Today buttons)

   

3. **Calendar Features**  
     
   - MUST color-code time slots:  
     - Available: White/Light color  
     - Booked: Blue/Orange  
     - Own bookings (if user is viewing): Green highlight  
     - Under Maintenance: Red/Gray striped pattern  
     - Past time slots: Grayed out (not clickable)  
   - MUST show booking duration clearly on calendar  
   - MUST prevent display of bookings that haven't been approved yet (Pending status) \- show as "Available" for Regular Users, show as "Pending Approval" for Admins

**SHALL:**

- Update calendar in real-time when bookings are confirmed or canceled  
- Provide tooltip on hover showing booking details (if user has permission)  
- Indicate recurring bookings with special icon/indicator  
- Display current time marker on calendar

**SHOULD:**

- Provide day view, week view, and month view options  
- Allow clicking on available slot to initiate booking (if user has permission)  
- Show room capacity and amenities above calendar for context

**Business Benefit:**  
Visual availability calendar reduces double-booking attempts and helps users identify optimal meeting times based on room availability.

---

### 5.4 Room Details

#### 5.4.1 Room Detail Page

**Purpose:**  
Provide comprehensive information about a specific meeting room to help users make informed booking decisions.

**Requirements:**

**MUST Display:**

1. **Room Header Section**  
     
   - Room name (large, prominent)  
   - Status badge (Available, Under Maintenance, etc.)  
   - Capacity with icon (e.g., "👥 Seats 12")  
   - Floor/location (e.g., "📍 3rd Floor, East Wing")

   

2. **Room Photo Gallery**  
     
   - Primary room photo (large display)  
   - Additional photos in thumbnail gallery (if available)  
   - Support lightbox/modal view for enlarged photos  
   - Default placeholder image if no photos uploaded

   

3. **Room Specifications**  
     
   - Capacity (maximum number of people)  
   - Floor and specific location/room number  
   - Room dimensions (optional, if available)  
   - Room description/notes (if provided by admin)

   

4. **Amenities/Equipment List**  
     
   - Display all available amenities with icons:  
     - ✓ Projector/Screen  
     - ✓ Whiteboard  
     - ✓ Video Conferencing  
     - ✓ Teleconferencing Phone  
     - ✓ Computer/Monitor  
     - ✓ Flip Chart  
     - ✓ Air Conditioning  
     - ✓ Natural Light/Windows  
   - Show "None specified" if no amenities listed

   

5. **Availability Calendar**  
     
   - Week view calendar showing bookings (as described in 5.3.2)  
   - Read-only for viewing availability

   

6. **Action Button**  
     
   - Prominent "Book This Room" button  
   - Links to booking creation page with room pre-selected  
   - Disabled if room is Inactive or Under Maintenance with explanation

**SHALL:**

- Provide breadcrumb navigation (Home \> Rooms \> \[Room Name\])  
- Show "Back to Rooms" link  
- Display last updated date/time for room information  
- Provide "Report Issue" or "Request Changes" link (optional)

**SHOULD:**

- Display room utilization statistics (e.g., "Booked 65% this month") for Admins/Directors  
- Show upcoming bookings (next 3-5) below calendar  
- Provide "Add to Favorites" functionality (optional enhancement)

**Business Benefit:**  
Comprehensive room information reduces uncertainty and ensures users book appropriate rooms for their needs, reducing cancellations and rebookings.

---

### 5.5 Room Administration

#### 5.5.1 Add/Edit Room (Administrator/Director)

**Purpose:**  
Enable administrators to create new room records and update existing room information.

**Target Roles:** Administrator, Director

**Requirements:**

**MUST Provide Form Fields:**

**Required Fields:**

- Room Name/Identifier (text, unique, max 50 characters)  
- Capacity (number, minimum 1, maximum 500\)  
- Floor/Location (text, e.g., "3rd Floor" or "Building A, 3F")

**Optional Fields:**

- Room Description (textarea, max 500 characters)  
- Amenities/Equipment (multi-select checkboxes):  
  - Projector/Screen  
  - Whiteboard  
  - Video Conferencing Equipment  
  - Teleconferencing Phone  
  - Computer/Monitor  
  - Flip Chart  
  - Air Conditioning  
  - Natural Light/Windows  
- Room Photos (file upload, support multiple images, max 5 images)  
  - Accepted formats: JPG, PNG  
  - Maximum file size: 5MB per image  
  - Auto-resize/optimize for web display

**MUST Validate:**

- Room name is unique (no duplicate names)  
- Capacity is a positive integer between 1 and 500  
- Floor/location is not empty  
- Photo uploads meet size and format requirements

**MUST:**

- Display form with clear labels and input validation  
- Show real-time validation errors as user types  
- Provide "Save" and "Cancel" buttons  
- Redirect to room list or room detail page after saving  
- Log room creation/modification in audit trail with timestamp and user  
- Set status to "Active" by default for new rooms

**SHALL:**

- Show preview of uploaded images before saving  
- Allow reordering photos (set primary photo)  
- Allow removing uploaded photos  
- Require confirmation if navigating away with unsaved changes  
- Display success message after saving

**SHOULD:**

- Auto-suggest floor/location based on existing rooms  
- Provide copy/duplicate functionality for similar rooms  
- Show character count for text fields with limits

**Business Benefit:**  
Streamlined room creation reduces data entry time and ensures consistent, accurate room information across the system.

---

#### 5.5.2 Room Status Management

**Purpose:**  
Allow administrators to control room availability by setting appropriate status and scheduling maintenance periods.

**Target Roles:** Administrator, Director

**Requirements:**

**MUST Support Three Status Types:**

1. **Active**  
     
   - Room is available for booking  
   - Appears in user search results and room browsing  
   - Can be booked by users (subject to approval)

   

2. **Inactive**  
     
   - Room is not available for booking  
   - Hidden from user search results and room browsing  
   - Only visible to Administrators/Directors in management view  
   - Existing future bookings remain valid  
   - Use case: Room permanently decommissioned or repurposed

   

3. **Under Maintenance**  
     
   - Room temporarily unavailable for booking  
   - Visible in search results but cannot be booked  
   - Shows "Under Maintenance" badge with dates  
   - Automatically reverts to "Active" when maintenance period ends  
   - Use case: Renovations, repairs, equipment upgrades

**MUST Provide:**

- Status change interface on room edit page or quick action menu  
- For "Under Maintenance" status:  
  - Start date and time picker (mandatory)  
  - End date and time picker (mandatory)  
  - Maintenance reason/description (optional, max 200 characters)  
- Validation that end date is after start date  
- Confirmation dialog before changing status  
- Warning if existing future bookings will be affected  
- Automatic status change from "Under Maintenance" to "Active" at end of maintenance period

**SHALL:**

- Log all status changes in audit trail (who, when, reason)  
- Send email notifications to users with affected future bookings when room status changes  
- Display maintenance schedule on room detail page  
- Prevent bookings from being created during maintenance periods  
- Allow administrators to edit or cancel maintenance schedule

**SHOULD:**

- Display countdown or maintenance schedule prominently on room detail page  
- Provide calendar view showing all maintenance schedules across rooms  
- Allow scheduling recurring maintenance (e.g., monthly cleaning)

**Business Benefit:**  
Proper status management prevents booking conflicts during maintenance periods and ensures users only see available rooms, reducing frustration and administrative overhead.

---

#### 5.5.3 Delete/Deactivate Room

**Purpose:**  
Enable safe removal or deactivation of rooms while protecting data integrity of existing bookings.

**Target Roles:** Administrator, Director

**Requirements:**

**MUST Implement Two Actions:**

1. **Permanent Delete**  
     
   - MUST check if room has ANY bookings (past, present, or future)  
   - IF bookings exist:  
     - MUST prevent deletion  
     - MUST display error message: "Cannot delete room with existing booking records. Deactivate instead."  
     - MUST show count of existing bookings  
   - IF no bookings exist:  
     - MUST show confirmation dialog: "Are you sure you want to permanently delete \[Room Name\]? This action cannot be undone."  
     - MUST require typing room name to confirm (extra safety measure)  
     - MUST remove room from database permanently  
     - MUST log deletion in audit trail

   

2. **Deactivate (Soft Delete)**  
     
   - CAN be done at any time, regardless of bookings  
   - Sets room status to "Inactive"  
   - Room hidden from user searches and browsing  
   - All booking data preserved  
   - Room remains in database (can be reactivated later)  
   - MUST show confirmation dialog: "Deactivate \[Room Name\]? It will be hidden from users but can be reactivated later."  
   - MUST log deactivation in audit trail

**SHALL:**

- Display both "Delete" and "Deactivate" options clearly with distinct colors:  
  - Delete: Red button with warning icon  
  - Deactivate: Orange/Yellow button  
- Show different warnings based on booking status  
- Require explicit confirmation for both actions  
- Provide "Cancel" option prominently  
- Display success message after action completes

**SHOULD:**

- Allow reactivating previously inactive rooms from admin view  
- Show warning if room has future bookings when deactivating  
- Provide undo option (within reasonable timeframe) for accidental deactivation

**Business Benefit:**  
Safe deletion mechanisms protect historical booking data while allowing administrators to remove rooms cleanly when appropriate, maintaining data integrity and audit compliance.

---

## 6\. Phase 3: Booking Management

### 6.1 Phase Overview

**Objective:**  
Implement the core booking workflow enabling users to reserve meeting rooms, manage their bookings, and allow administrators to approve requests and oversee all reservations.

**Deliverables:**

- Booking creation interface with validation  
- Recurring booking support (daily, weekly, monthly patterns)  
- Global booking calendar with multiple views (day, week, month)  
- Personal booking management (view, edit, cancel)  
- Administrative booking oversight (view all, approve/reject)  
- Approval queue for pending bookings

**Business Value:**

- Self-service booking reduces administrative workload  
- Approval workflow ensures proper oversight and control  
- Recurring bookings save time for regular meetings  
- Calendar views prevent double-booking and conflicts  
- Central visibility into all reservations improves resource planning

---

### 6.2 Booking Creation & Management

#### 6.2.1 Booking Creation

**Purpose:**  
Allow users to create meeting room reservations with proper validation and conflict prevention.

**Requirements:**

**MUST Provide Form Fields:**

**Required Fields:**

- **Room Selection** (dropdown or search-select)  
  - Display room name, capacity, floor  
  - Show only Active rooms  
  - Indicate current availability  
- **Booking Date** (date picker)  
  - Calendar interface  
  - Prevent past dates  
  - No maximum advance booking limit  
- **Start Time** (dropdown in 30-minute increments)  
  - Range: 8:00 AM \- 6:00 PM  
  - Based on room operating hours  
- **End Time** (dropdown in 30-minute increments)  
  - Range: 8:30 AM \- 6:00 PM  
  - Must be after start time  
- **Purpose/Description** (text area)  
  - Describe meeting purpose  
  - Maximum 500 characters  
  - Helps administrators review booking context

**System-Captured Field:**

- **Person in Charge (PIC) / Booker**  
  - **For Regular Users**: Automatically set to logged-in user (non-editable)  
  - **For Administrators/Directors booking on behalf of others**: Dropdown to select user from system

**MUST Validate:**

- Room is selected and is Active  
- Date is not in the past  
- Start time is before end time  
- **Minimum duration**: 30 minutes  
- **Maximum duration**: 8 hours  
- **Single-day booking**: Start and end time must be on the same date (no overnight bookings)  
- Booking is within operating hours (8:00 AM \- 6:00 PM)  
- Room is available during selected time slot (no conflicts with existing Confirmed bookings)  
- Room is not Under Maintenance during selected period  
- Purpose/description is not empty

**MUST Process:**

- Check room availability in real-time against:  
  - Existing Confirmed bookings (prevent conflicts)  
  - Pending bookings (warn user but allow if Admin decides)  
  - Maintenance schedules (prevent booking)  
- Generate unique booking reference number (e.g., BK-2025-00001)  
- Set initial status to "Pending" (awaiting approval)  
- Record booking timestamp (date and time created)  
- Associate booking with user (PIC)  
- Store booking in database  
- Send email notification to user confirming booking submission  
- Send email notification to Administrators/Directors about new pending booking  
- Log booking creation in audit trail

**SHALL:**

- Display real-time availability check as user selects date/time  
- Show visual calendar of room availability while selecting time  
- Display room capacity and amenities for context  
- Provide "Check Availability" button to validate before submission  
- Show confirmation screen with all booking details before final submission  
- Require explicit confirmation to proceed  
- Display success message with booking reference number after creation  
- Provide link to view booking details or return to "My Bookings"

**SHOULD:**

- Suggest alternative times if selected slot is unavailable  
- Suggest alternative rooms with similar capacity/amenities  
- Pre-fill date/time if user came from calendar view (clicked on specific slot)  
- Save draft booking for later completion (optional)  
- Display booking policies or guidelines during creation

**Business Benefit:**  
Streamlined booking creation with real-time validation reduces errors, prevents double-bookings, and ensures all necessary information is captured upfront.

---

#### 6.2.2 Recurring/Repeating Bookings

**Purpose:**  
Enable users to create bookings that repeat on a regular schedule for recurring meetings, reducing repetitive data entry.

**Requirements:**

**MUST Provide Recurring Options:**

1. **Recurrence Pattern Selection**  
     
   - **Daily**: Repeat every X days (e.g., every 1 day, every 2 days)  
   - **Weekly**: Repeat on specific days of the week (e.g., every Monday and Wednesday)  
   - **Monthly**: Repeat on specific date of the month (e.g., 1st of every month, 15th of every month)

   

2. **Recurrence End Options**  
     
   - **End by date**: Select specific end date  
   - **After X occurrences**: Specify number of repetitions  
   - Maximum allowed: **1 year worth of occurrences** from start date  
   - System calculates total occurrences and displays for confirmation

**MUST Validate:**

- Recurrence pattern is valid  
- End date is after start date  
- Total number of occurrences does not exceed 1 year  
- For **Weekly** pattern: At least one day of week is selected  
- For **Monthly** pattern: Date exists in all months (e.g., avoid 31st if not all months have it)  
- **Room availability for ALL occurrences** (check against existing bookings)  
- If ANY occurrence conflicts with existing booking, display clear error with conflicting dates

**MUST Process:**

- Create individual booking records for each occurrence  
- Link all occurrences with a **Series ID** (to identify as recurring series)  
- Mark all occurrences as "Pending" status  
- **Single approval applies to entire series** \- when Admin approves/rejects, all occurrences change status together  
- Generate unique booking reference for the series (e.g., BK-SERIES-2025-00001)  
- Each individual occurrence also has its own booking ID  
- Send single email notification about recurring booking request (not one per occurrence)  
- Log series creation in audit trail with recurrence details

**SHALL:**

- Display summary of all occurrence dates before confirmation  
- Show total number of bookings to be created (e.g., "This will create 12 bookings")  
- Highlight any dates that fall on holidays or weekends (informational)  
- Provide calendar preview showing all occurrences  
- Allow user to review and confirm or cancel before final submission

**MUST Support Series Management:**

- **View Series**: Show all occurrences grouped together in "My Bookings"  
- **Edit Series**: Editing one occurrence applies changes to **entire series**:  
  - Can change: Time, Room (if available for all dates), Purpose  
  - Cannot change: Recurrence pattern, individual dates  
  - Requires re-approval (all occurrences revert to "Pending")  
- **Cancel Series**: Cancelling one occurrence cancels **entire series**  
  - Requires cancellation reason  
  - All occurrences marked as "Cancelled"  
  - Cannot cancel individual occurrences separately

**SHOULD:**

- Warn users about long series (e.g., \>20 occurrences) to confirm intent  
- Display cost/impact summary if resource tracking is implemented (future)  
- Allow excluding specific dates from recurring pattern (e.g., skip holidays) \- optional enhancement

**Business Benefit:**  
Recurring bookings save significant time for regular meetings (e.g., weekly team meetings), reducing from 52 individual bookings to a single request. Series management ensures consistency across all occurrences.

---

#### 6.2.3 Booking Calendar View

**Purpose:**  
Provide visual calendar interface showing room availability and existing bookings to facilitate scheduling.

**Requirements:**

**MUST Provide Three Calendar Views:**

1. **Day View**  
     
   - Show single day divided into time slots  
   - Display all rooms in vertical columns or horizontal rows  
   - Time slots: 30-minute increments from 8:00 AM to 6:00 PM  
   - Show bookings as blocks spanning their duration  
   - Navigation: Previous Day, Next Day, Today buttons

   

2. **Week View (Default)**  
     
   - Show Monday through Friday (5 working days)  
   - Display all rooms across top (columns) or side (rows)  
   - Time slots: 30-minute increments (8:00 AM \- 6:00 PM)  
   - Show bookings as colored blocks with time and room details  
   - Navigation: Previous Week, Next Week, This Week buttons  
   - Display week number and date range (e.g., "Week 42: Oct 16-20, 2025")

   

3. **Month View**  
     
   - Show full calendar month with all dates  
   - Display booking count per day per room  
   - Click date to see details or switch to day/week view  
   - Navigation: Previous Month, Next Month, This Month buttons  
   - Highlight today's date

**MUST Display for Each Booking Block:**

**For Regular Users:**

- **Own bookings only** (isolate their bookings)  
- Show: Time, Room name, Purpose, Status badge  
- Color-code by status:  
  - Pending: Yellow/Orange  
  - Confirmed: Green  
  - Cancelled: Red (strikethrough)  
  - Completed: Gray  
- Cannot see other users' bookings (empty slots appear available)

**For Administrators/Directors:**

- **All bookings** across all users  
- Show: Time, Room name, User name (PIC), Status badge  
- Same color-coding as above  
- Can click booking to view full details or take actions

**MUST Support Interactions:**

- **Click on empty slot**: Initiate booking creation with room/date/time pre-filled  
- **Click on existing booking**: View booking details (own bookmark or if Admin)  
- **Drag-and-drop** (optional enhancement): Resize booking duration or move to different time/room  
- **Hover tooltip**: Display quick preview of booking details  
- **Filter by status**: Show All, Pending only, Confirmed only, etc.  
- **Search/filter by room**: Show specific room or room subset

**SHALL:**

- Update calendar in real-time or auto-refresh every 60 seconds  
- Display current time marker (red line) on day/week view  
- Highlight current day on all views  
- Show loading indicator while fetching bookings  
- Provide legend explaining color codes and symbols  
- Responsive design: Stack columns on mobile, reduce time slot granularity if needed

**SHOULD:**

- Print-friendly view option  
- Export calendar view as PDF or image  
- Sync with external calendars (Google Calendar, Outlook) \- future enhancement  
- Show recurring booking indicator (chain link icon or repeating symbol)

**Business Benefit:**  
Visual calendar interface dramatically improves scheduling by showing availability at a glance, reducing back-and-forth communication and booking conflicts.

---

### 6.3 Personal Booking Management

#### 6.3.1 View Personal Bookings

**Purpose:**  
Allow users to view their complete booking history and upcoming reservations.

**Requirements:**

**MUST Provide Dual View:**

1. **List/Table View**  
     
   - Display bookings in sortable table with columns:  
     - Booking Reference Number  
     - Date  
     - Start Time \- End Time  
     - Room Name & Location  
     - Status (badge)  
     - Purpose (truncated with "Read More")  
     - Actions (View Details, Edit, Cancel buttons)  
   - **Sort Options:**  
     - By Date (Upcoming first, or Past first)  
     - By Room Name (A-Z)  
     - By Status (Pending, Confirmed, Cancelled, Completed)  
   - Default sort: Upcoming bookings first (soonest on top)  
   - Pagination: 20 bookings per page

   

2. **Calendar View**  
     
   - Personal calendar showing only user's bookings  
   - Day, Week, Month views (same as global calendar but filtered to user)  
   - Color-coded by status  
   - Click booking to view details

**MUST Provide Filters:**

- **All Bookings**: Show all regardless of date or status  
- **Upcoming**: Show only future bookings (Pending \+ Confirmed)  
- **Past**: Show only completed or past-date bookings  
- **By Status**:  
  - Pending (awaiting approval)  
  - Confirmed (approved)  
  - Cancelled (user or admin cancelled)  
  - Completed (past end time)  
- **By Room**: Filter by specific room  
- **By Date Range**: Custom start and end date selectors

**MUST Display:**

- Total count of bookings per filter (e.g., "15 Upcoming Bookings")  
- Clear indicator if no bookings exist ("No bookings found")  
- Booking series grouped together with expandable details  
- Quick actions for each booking:  
  - View Details (modal or detail page)  
  - Edit (if Pending status)  
  - Cancel (if Pending or Confirmed)

**SHALL:**

- Preserve filter and sort selections during session  
- Display "Clear Filters" button when filters are active  
- Export personal booking history to CSV/Excel/PDF  
- Highlight bookings happening today or within 24 hours  
- Show countdown for upcoming bookings (e.g., "Starts in 2 hours")

**SHOULD:**

- Display statistics: Total bookings made, Approval rate, Cancellation rate  
- Show booking patterns chart (as in User Dashboard)  
- Provide notes field for personal reference (optional)

**Business Benefit:**  
Centralized view of all bookings helps users track their reservations, plan schedules, and manage upcoming meetings efficiently.

---

#### 6.3.2 Edit Own Bookings

**Purpose:**  
Allow users to modify their booking details under specific conditions to accommodate schedule changes.

**Requirements:**

**Edit Permissions:**

**Regular Users:**

- **CAN edit**: Only bookings with **"Pending"** status (not yet approved)  
- **CANNOT edit**: Confirmed, Rejected, Cancelled, or Completed bookings  
- Rationale: Prevents disruption after approval; requires new booking for confirmed changes

**Administrators/Directors:**

- **CAN edit**: Bookings of **any status** (Pending, Confirmed, Cancelled, Completed)  
- **Status behavior**: Status remains unchanged after edit  
  - Example: Editing Confirmed booking → stays Confirmed (if no conflicts)  
  - If changes cause conflicts, display error and prevent save  
- Can edit any user's booking

**MUST Allow Editing:**

- Booking Date (with availability check)  
- Start Time (with availability check)  
- End Time (with availability check)  
- Room (with availability check for new room)  
- Purpose/Description

**MUST Restrict:**

- Cannot change PIC/Booker (booking ownership)  
- Cannot change booking reference number  
- Cannot convert one-time booking to recurring (must create new)  
- Cannot edit individual occurrence in recurring series (must edit entire series)

**MUST Validate:**

- Same validations as booking creation:  
  - Date not in past  
  - Duration 30 min to 8 hours  
  - Within operating hours (8 AM \- 6 PM)  
  - Room available during new time slot  
  - No conflicts with existing bookings  
- If editing Confirmed booking as Admin: Ensure new time/room doesn't conflict

**MUST Process:**

- **For Pending bookings**:  
  - Update booking details in database  
  - Maintain "Pending" status  
  - Log modification in audit trail  
  - Send email to user confirming changes  
- **For Confirmed bookings (Admin/Director only)**:  
  - Update booking details  
  - **Keep "Confirmed" status** (no re-approval needed)  
  - Send email to original booker (PIC) notifying of changes made by admin  
  - Log modification in audit trail with admin name  
- **For Recurring series**:  
  - Changes apply to **all occurrences** in series  
  - Check availability for all occurrence dates  
  - If editing Confirmed series: Status remains Confirmed  
  - If editing Pending series: Series stays Pending (no re-approval for edits)

**SHALL:**

- Display confirmation dialog before saving changes  
- Show side-by-side comparison of old vs. new values  
- Highlight fields that have changed  
- Require "Save Changes" and "Cancel" buttons  
- Display success message after successful update  
- Provide option to "Save as New Booking" instead of editing (creates duplicate)

**SHOULD:**

- Warn if editing a booking that starts soon (e.g., within 1 hour)  
- Display history of changes (who edited what and when) \- audit log view  
- Allow reverting to previous version (undo) within short timeframe

**Business Benefit:**  
Flexible editing reduces need to cancel and rebook, saving time while maintaining approval workflow integrity. Admin override capability handles exceptional cases.

---

#### 6.3.3 Cancel Own Bookings

**Purpose:**  
Allow users to cancel bookings they no longer need, freeing up rooms for others.

**Requirements:**

**Cancel Permissions:**

**Regular Users:**

- **CAN cancel**: Bookings with **"Pending"** or **"Confirmed"** status  
- **CANNOT cancel**: Already Cancelled, Rejected, or Completed bookings  
- **No notice period required**: Can cancel anytime before booking end time

**Administrators/Directors:**

- **CAN cancel**: Any booking (own or others') with Pending or Confirmed status  
- Can cancel on behalf of users  
- Same rules apply: Cannot cancel already Cancelled/Rejected/Completed

**MUST Require:**

- **Cancellation Reason** (text field, maximum 500 characters)  
  - Helps track cancellation patterns  
  - Administrators review reasons for policy improvements  
  - Reason is logged and viewable in booking history

**MUST Process:**

- Update booking status to "Cancelled"  
- Record cancellation timestamp (date and time)  
- Record who cancelled (user or specific admin)  
- Store cancellation reason in database  
- Free up room availability immediately (slot becomes available for others)  
- **For Recurring Series**: Cancelling one occurrence cancels **entire series**  
  - All occurrences marked as "Cancelled"  
  - Single cancellation reason applies to all  
- Log cancellation in audit trail  
- Send email notification to user confirming cancellation  
- Send email notification to Administrators (for tracking)  
- If Admin cancels on behalf of user, notify the original booker

**SHALL:**

- Display confirmation dialog: "Are you sure you want to cancel this booking?"  
- For recurring series: "This will cancel all X occurrences in the series. Continue?"  
- Show booking details (date, time, room) in confirmation dialog  
- Require explicit "Confirm Cancellation" button  
- Provide "Go Back" or "Cancel" option  
- Display success message after cancellation  
- Prevent accidental cancellation with clear UI warnings

**SHOULD:**

- Display cancellation policy or guidelines before cancelling  
- Warn user if cancelling booking that starts very soon (e.g., within 30 minutes)  
- Allow adding private notes along with cancellation reason (visible to user only)  
- Track cancellation rate per user and flag excessive cancellations (for Admin review)

**MUST NOT:**

- Allow "undo" cancellation (cancelled bookings stay cancelled)  
- Automatically delete cancelled bookings from database (preserve for audit)

**Business Benefit:**  
Easy cancellation ensures rooms are freed up promptly for other users, improving overall resource utilization. Cancellation reasons provide insights for facility planning.

---

### 6.4 Administrative Booking Management

#### 6.4.1 View All Bookings

**Purpose:**  
Provide administrators and directors with comprehensive oversight of all bookings across the system.

**Target Roles:** Administrator, Director

**Requirements:**

**MUST Provide:**

1. **Comprehensive Table/List View**  
   - Display ALL bookings from all users  
   - Columns:  
     - Booking Reference  
     - User Name (PIC)  
     - User Department (if available)  
     - Date  
     - Start Time \- End Time  
     - Room Name & Location  
     - Status (badge with color)  
     - Purpose (truncated)  
     - Actions (View, Edit, Cancel, Approve/Reject if Pending)  
   - Pagination: 50 bookings per page  
   - Default sort: Date (upcoming first)

**MUST Provide Filters:**

- **By User**: Dropdown or search-select to filter by specific user  
- **By Room**: Dropdown to filter by specific room  
- **By Date Range**: Start date and end date pickers  
- **By Status**: Checkboxes for Pending, Confirmed, Cancelled, Completed  
- **By Department**: Dropdown to filter by user's department (helps large organizations)  
- **By Booking Type**: One-time vs. Recurring series

**MUST Provide Sort Options:**

- By Date (Ascending/Descending)  
- By User Name (A-Z)  
- By Room Name (A-Z)  
- By Status (Pending first, Confirmed, etc.)  
- By Submission Date (when booking was created)

**MUST Display:**

- Total count of bookings matching current filters (e.g., "250 bookings found")  
- Summary statistics:  
  - Total Pending (requires action)  
  - Total Confirmed (approved)  
  - Total Cancelled (freed capacity)  
  - Total Completed (historical)  
- Export functionality: Download filtered list as CSV, Excel, or PDF

**SHALL:**

- Preserve filter and sort selections during session  
- Display "Clear All Filters" button  
- Provide quick actions for each booking:  
  - View Details (modal or new page)  
  - Edit Booking (opens edit form pre-filled)  
  - Cancel Booking (with reason prompt)  
  - Approve/Reject (if Pending status, opens approval interface)  
- Highlight overdue pending approvals (e.g., submitted \>24 hours ago)  
- Show recurring series indicator (icon or badge)

**SHOULD:**

- Search functionality: Free text search across user names, room names, purposes  
- Bulk export all bookings (with filters applied)  
- Print-friendly view  
- Visual analytics: Charts showing bookings by status, by department, by room

**MUST NOT:**

- Support bulk approve/reject (each booking reviewed individually for quality control)

**Business Benefit:**  
Centralized visibility enables administrators to monitor booking patterns, identify issues (frequent cancellations, overbooked rooms), and make informed decisions about resource allocation.

---

#### 6.4.2 Booking Approval Workflow

**Purpose:**  
Implement systematic review and approval process for all booking requests to ensure proper authorization and resource management.

**Target Roles:** Administrator (Primary), Director (Passive/Oversight)

**Requirements:**

**Approval Queue Interface:**

**MUST Provide:**

- Dedicated "Approval Queue" page showing **only Pending bookings**  
- Default sort: **Submission date (oldest first)** \- ensures timely processing  
- Display pending count badge on navigation menu  
- Two-panel layout:  
  - Left: List of pending bookings  
  - Right: Selected booking details for review

**MUST Display per Booking:**

- Booking Reference Number  
- Submission timestamp ("Submitted 2 hours ago")  
- User name and department (PIC)  
- Room requested  
- Date and time  
- Duration  
- Purpose/Description (full text)  
- Recurring series details (if applicable)  
- User's booking history summary (total bookings, cancellation rate) \- context for approval decision

**Approval/Rejection Actions:**

**MUST Require:**

- Administrator/Director MUST **open booking details** to approve or reject (no one-click from list)  
- Detail view shows all booking information for informed decision  
- Two action buttons prominently displayed:  
  - **"Approve Booking"** (green button)  
  - **"Reject Booking"** (red button)

**Approval Process (Approve):**

**MUST:**

- Change booking status from "Pending" to "Confirmed"  
- Update approval timestamp  
- Record approver name (which Admin/Director approved)  
- **For recurring series**: Approving one occurrence approves **entire series** (all occurrences become Confirmed)  
- Send email notification to user:  
  - Subject: "Booking Approved \- \[Room Name\] on \[Date\]"  
  - Include booking details and confirmation  
  - Add to calendar attachment (iCal format) \- optional  
- Log approval action in audit trail  
- Display success message: "Booking approved successfully"  
- Move to next pending booking in queue automatically

**SHALL:**

- Verify room is still available (in case another booking was approved first)  
- If conflict detected, display error and prevent approval  
- Require confirmation before finalizing approval

**Rejection Process (Reject):**

**MUST:**

- Prompt for **rejection reason** (text field, **optional but recommended**)  
- Change booking status from "Pending" to "Rejected"  
- Update rejection timestamp  
- Record rejector name (which Admin/Director rejected)  
- **For recurring series**: Rejecting one occurrence rejects **entire series**  
- Send email notification to user:  
  - Subject: "Booking Request Rejected \- \[Room Name\] on \[Date\]"  
  - Include rejection reason (if provided)  
  - Suggest alternative action (contact admin, book different room/time)  
- Log rejection action in audit trail with reason  
- Display success message: "Booking rejected"  
- Move to next pending booking in queue automatically

**SHALL:**

- Display confirmation dialog: "Are you sure you want to reject this booking?"  
- Show rejection reason field (optional, pre-filled suggestions like "Room unavailable", "Insufficient notice", "Policy violation")  
- Provide "Cancel" option to abort rejection  
- Highlight rejection reason in booking history for future reference

**Queue Management:**

**MUST:**

- Display count of pending approvals (e.g., "12 Pending Approvals")  
- Sort by submission date (oldest first) by default to ensure no request is overlooked  
- Allow filtering by:  
  - Date range (bookings for specific dates)  
  - Room  
  - User/Department  
  - Recurring vs. One-time  
- Provide "Refresh" button to reload queue  
- Auto-refresh queue every 60 seconds to show new submissions

**SHALL:**

- Display estimated processing time or average approval time (informational)  
- Highlight urgent requests (e.g., booking date is approaching soon)  
- Show recurring series count separately (1 series \= X occurrences)  
- Provide timeline view showing when bookings are scheduled

**SHOULD:**

- Send digest email to Administrators daily summarizing pending approvals  
- Escalation: Notify Directors if bookings pending \>48 hours  
- Priority flagging: Allow users to mark urgent requests (optional feature)  
- Delegation: Allow Administrators to assign specific approvals to others

**MUST NOT:**

- Support bulk approve/reject (each booking requires individual review)  
- Auto-approve any bookings (all must be manually reviewed)

**Business Benefit:**  
Systematic approval ensures responsible resource use, prevents unauthorized bookings, and maintains oversight. Single-series approval for recurring bookings balances efficiency with control.

---

### 6.5 Booking Controls

#### 6.5.1 Maximum Advance Booking Period

**Purpose:**  
Document that there is **no restriction** on how far in advance users can book rooms, allowing maximum flexibility.

**Requirements:**

**MUST:**

- **No maximum advance booking period** enforced  
- Users can book rooms for any future date (months or years ahead) as long as:  
  - Room is available (no conflicts)  
  - Room is Active (not Inactive or Under Maintenance on that date)  
  - Booking meets all other validation rules (duration, operating hours, etc.)  
- System does not limit booking horizon

**SHALL:**

- Display information in booking interface: "You can book rooms for any future date"  
- No date picker restrictions beyond preventing past dates

**Rationale:**

- OIB Group does not require advance booking limits  
- Users can plan long-term events, annual meetings, or quarterly reviews without restriction  
- Flexibility supports business needs without introducing unnecessary constraints  
- Room availability and approval workflow are sufficient controls

**MUST NOT:**

- Implement configurable maximum advance booking setting (not needed)  
- Restrict booking dates based on user role or department  
- Auto-expire future bookings (bookings remain valid indefinitely until cancelled or completed)

**Business Benefit:**  
Unlimited advance booking horizon allows comprehensive long-term planning for recurring meetings, annual events, and strategic planning sessions without administrative overhead.

**Note:**  
This section documents the absence of advance booking restrictions for completeness. No development work required beyond standard date validation (no past dates).

---

### 6.6 Booking Process Flow

**User Workflows:**

1. **Create Booking**  
     
   - One-time: Fill form → Validate (any future date, 30min-8hr duration, single-day only, no conflicts) → Submit as Pending  
   - Recurring: Add recurrence pattern (Daily/Weekly/Monthly, max 1 year) → Check all dates → Submit series as Pending  
   - Notifications sent to user and admins  
   - Audit trail logged

   

2. **View My Bookings**  
     
   - Browse with filters/sorts  
   - View Details: Display full information  
   - Edit (Pending only): Modify → Validate → Update (stays Pending)  
   - Cancel (Pending/Confirmed): Reason → Confirm → Set Cancelled (entire series for recurring)

**Administrator Workflows:**

1. **Approval Queue**  
     
   - View Pending bookings (oldest first)  
   - Review booking details and user history  
   - **Approve**: Final availability check → Confirmed → Notify user (entire series confirmed)  
   - **Reject**: Optional reason → Confirmed → Rejected → Notify user (entire series rejected)

   

2. **View All Bookings**  
     
   - Filter by User, Room, Date, Status, Department  
   - **Edit Any**: Modify → Validate → Update (status unchanged, even if Confirmed)  
   - **Cancel Any**: Reason → Confirmed → Cancelled → Notify original booker

   

3. **Calendar View**  
     
   - Day/Week/Month views showing all bookings  
   - Click slot to create, click booking to view/edit  
   - Visual overview across all rooms

**Key Decision Points:**

- Booking type (one-time vs. recurring)  
- Validation (duration, conflicts, operating hours)  
- Approval decision (approve vs. reject)  
- Edit/cancel permissions (user role and booking status)  
- Series management (entire series for recurring bookings)

**Status Transitions:**

- Created → **Pending** → (Approved) → **Confirmed** → (Completed after end time) → **Completed**  
- Created → **Pending** → (Rejected) → **Rejected**  
- Any → (Cancelled) → **Cancelled**

## 7\. Phase 4: Administrative Management

### 7.1 Phase Overview

**Objective:**  
Implement the comprehensive backend management tools required for system oversight, including user administration, detailed reporting, audit logging, and system configuration. This phase transforms the operational booking tool into a managed enterprise system with full governance capabilities.

**Deliverables:**

- Full user management interface (CRUD operations)  
- Comprehensive reporting suite (Daily, Monthly, Utilization, User History)  
- Audit trail and activity logging system  
- System configuration panel  
- Notification management system

**Business Value:**

- **Governance & Accountability:** Complete visibility into who is using the system and how  
- **Data-Driven Decisions:** Reports provide insights for space planning and resource optimization  
- **Security & Compliance:** Robust user management and immutable audit trails ensure accountability and deter sabotage  
- **Operational Flexibility:** System configuration allows adaptation to changing needs without code changes  
- **Risk Mitigation:** Addresses the "sabotage" risk mentioned in the business problem through comprehensive logging

---

### 7.2 User Management

#### 7.2.1 System Administrator User Management

**Purpose:**  
Provide System Administrators and Directors with full control over the user lifecycle, ensuring accurate and secure access management while preventing unauthorized access.

**Target Roles:** System Administrator, Director

**Requirements:**

**MUST Provide User List View:**

- Display all registered users in a paginated table  
- Show columns:  
  - Staff Number  
  - Full Name  
  - Email Address  
  - Department/Division  
  - Role (with color-coded badge)  
  - Status (Active/Inactive with visual indicator)  
  - Last Login Date  
  - Account Created Date  
- Implement search functionality:  
  - By Name (full or partial match)  
  - By Email  
  - By Staff Number  
- Implement filtering:  
  - By Role (All, Regular User, Administrator, Director, System Admin)  
  - By Department  
  - By Status (All, Active, Inactive)  
- Display user count summary (e.g., "Showing 25 of 150 users")

**MUST Support Create User:**

- Accessible via "Create New User" button  
- Use the same interface as defined in Phase 1, Section 4.2.1  
- Allow role assignment during creation  
- Send welcome email automatically

**MUST Support Edit User:**

- Modify editable fields:  
  - Full Name  
  - Department/Division  
  - Phone Number  
  - **Role** (change between Regular User, Administrator, Director, System Admin)  
- **Cannot modify:**  
  - Staff Number (immutable unique identifier)  
  - Email Address (tied to identity)  
- Display confirmation dialog when changing roles  
- Log role changes in audit trail with old role → new role

**MUST Support Deactivate User:**

- Toggle user status to "Inactive"  
- **Effects of deactivation:**  
  - User **cannot log in**  
  - User **cannot create, edit, or cancel** bookings  
  - **Existing bookings are preserved** (both pending and confirmed)  
  - Bookings remain visible in system with user attribution  
  - User appears as "Deactivated User" in reports  
- Require confirmation dialog: "Deactivate \[User Name\]? They will lose system access but historical data will be preserved."  
- Provide "Reactivate" option to restore access  
- Log deactivation in audit trail

**MUST Support Reset Password:**

- Admin-initiated password reset  
- Two options:  
  1. **Generate Temporary Password:** System creates random password and displays it (admin copies manually)  
  2. **Send Reset Link:** Email password reset link to user's email  
- Require user to change temporary password on first login  
- Log password reset action in audit trail (without storing password)

**MUST Support Delete User:**

- **Strict Constraint:** Cannot delete user if they have:  
  - Any bookings (past, pending, confirmed, cancelled)  
  - Any administrative actions in audit trail (approvals, rejections)  
  - Any room management activities  
- If deletion is blocked:  
  - Display error message: "Cannot delete \[User Name\]. User has existing bookings or system activity. Use 'Deactivate' instead."  
  - Show count of bookings and activities  
  - Provide "Deactivate" button as alternative  
- If user has no activity:  
  - Show confirmation dialog: "Permanently delete \[User Name\]? This action cannot be undone."  
  - Require typing user's full name to confirm  
  - Permanently remove from database  
  - Log deletion in audit trail

**SHALL:**

- Display visual status badges:  
  - Active: Green badge  
  - Inactive: Gray badge  
- Show role badges with color coding:  
  - Regular User: Blue  
  - Administrator: Orange  
  - Director: Purple  
  - System Admin: Red  
- Provide "View Activity History" link for each user (links to Section 7.3.2)  
- Log all user management actions in audit trail  
- Update user list in real-time after changes

**MUST NOT:**

- Support bulk actions (no bulk deactivate, bulk delete, bulk role changes)  
- Allow users to change their own role  
- Allow deletion of users with any system activity

**Business Benefit:**  
Centralized user management ensures that access is granted only to active employees, roles reflect current responsibilities, and all changes are tracked for accountability. This directly addresses the "sabotage" concern by ensuring proper access control.

---

### 7.3 System Monitoring & Control

#### 7.3.1 Activity Tracking and Audit Trail

**Purpose:**  
Maintain an immutable, comprehensive record of all system events for security, compliance, troubleshooting, and deterring malicious activity.

**Target Roles:** Director, System Administrator

**Requirements:**

**MUST Log the Following Events:**

1. **Authentication Events:**  
     
   - Login success (user, timestamp, IP address)  
   - Login failure (email attempted, timestamp, IP address, reason)  
   - Logout (user, timestamp)  
   - Password reset request (user, timestamp)  
   - Password reset completion (user, timestamp)  
   - First login after password reset (user, timestamp)

   

2. **Booking Events:**  
     
   - Booking created (user, booking ID, room, date/time, timestamp)  
   - Booking edited (user, booking ID, old values → new values, timestamp)  
   - Booking cancelled (user, booking ID, reason, timestamp)  
   - Recurring series created (user, series ID, pattern, date range, timestamp)  
   - Recurring series edited (user, series ID, changes, timestamp)  
   - Recurring series cancelled (user, series ID, reason, timestamp)

   

3. **Approval Events:**  
     
   - Booking approved (admin, booking ID, timestamp)  
   - Booking rejected (admin, booking ID, reason, timestamp)  
   - Series approved (admin, series ID, occurrences count, timestamp)  
   - Series rejected (admin, series ID, reason, timestamp)

   

4. **Room Management Events:**  
     
   - Room created (admin, room ID, room name, timestamp)  
   - Room edited (admin, room ID, field changed, old → new, timestamp)  
   - Room status changed (admin, room ID, old status → new status, timestamp)  
   - Maintenance scheduled (admin, room ID, start date, end date, timestamp)  
   - Room deleted/deactivated (admin, room ID, timestamp)

   

5. **User Management Events:**  
     
   - User created (admin, new user ID, role assigned, timestamp)  
   - User edited (admin, user ID, fields changed, timestamp)  
   - User role changed (admin, user ID, old role → new role, timestamp)  
   - User deactivated (admin, user ID, timestamp)  
   - User reactivated (admin, user ID, timestamp)  
   - User deleted (admin, user ID, timestamp)  
   - Password reset by admin (admin, target user ID, timestamp)

   

6. **System Configuration Events:**  
     
   - System setting changed (admin, setting name, old value → new value, timestamp)  
   - Notification toggle changed (admin, notification type, enabled/disabled, timestamp)  
   - Maintenance mode toggled (admin, on/off, timestamp)

**MUST Store for Each Log Entry:**

- **Event ID:** Unique identifier for the log entry  
- **Timestamp:** Date and time (precise to the second)  
- **Actor:** User ID and Full Name of person who performed action  
- **Action Type:** Descriptive label (e.g., "Booking Created", "User Deactivated")  
- **Target Entity Type:** What was affected (Booking, Room, User, System Setting)  
- **Target Entity ID:** Specific ID (e.g., Booking \#123, User \#456, Room \#789)  
- **Details:** JSON or structured text with:  
  - For Creates: All initial values  
  - For Updates: Old Value → New Value for each changed field  
  - For Deletes: Reason (if applicable)  
  - For Cancellations: Cancellation reason  
  - For Rejections: Rejection reason  
- **IP Address:** User's IP address  
- **User Agent:** Browser/device information (optional)

**MUST Implement Immutability:**

- Audit log entries **cannot be edited**  
- Audit log entries **cannot be deleted** (not even by System Administrators)  
- Use database constraints to enforce write-once (INSERT only, no UPDATE or DELETE)  
- Prevent any UI from modifying logs

**MUST Provide Audit Log Viewer:**

- Accessible only to Directors and System Administrators  
- Display logs in reverse chronological order (most recent first)  
- Paginate results (e.g., 50 entries per page)  
- Show columns:  
  - Timestamp  
  - Actor Name  
  - Action Type  
  - Target Entity  
  - Summary (condensed Details)  
  - "View Full Details" link  
- Expand row or modal to show complete Details

**MUST Provide Search and Filter:**

- **Filter by Date Range:**  
  - Custom Start Date and End Date  
  - Presets: Today, Yesterday, Last 7 Days, Last 30 Days, This Month, Last Month, This Year  
- **Filter by Actor:** Select specific user  
- **Filter by Action Type:** Dropdown with all action categories (Login, Booking Created, Room Edited, etc.)  
- **Filter by Target Entity Type:** Booking, Room, User, System Setting  
- **Full-Text Search:** Search across Actor Name, Details, and Target Entity fields

**MUST Provide Export:**

- Export filtered audit logs to:  
  - **CSV:** All columns in flat format  
  - **Excel:** Formatted with headers, filters, and conditional formatting  
- Include all fields (Event ID, Timestamp, Actor, Action, Target, Details, IP)  
- Filename format: `AuditLog_[StartDate]_to_[EndDate]_[Timestamp].csv`  
- Generate within 30 seconds for typical queries (up to 10,000 entries)  
- Log export action itself in audit trail

**SHALL:**

- Retain audit logs for **minimum 2 years**  
- Archive older logs (beyond 2 years) to separate storage (optional)  
- Display warning if export file will be very large (\>5 MB)  
- Provide "Clear Filters" button

**SHOULD:**

- Highlight suspicious patterns (e.g., multiple failed login attempts, rapid booking cancellations)  
- Provide quick filter shortcuts (e.g., "Show all actions by this user", "Show all changes to this booking")

**Business Benefit:**  
Complete audit trail provides accountability, enables forensic investigation of issues (including the "sabotage" mentioned in business problems), and supports compliance requirements. The immutability ensures trust in the logs.

---

#### 7.3.2 User Activity History

**Purpose:**  
Allow administrators to investigate specific user behavior, troubleshoot issues, or review activity for a single user.

**Target Roles:** Director, System Administrator

**Requirements:**

**MUST:**

- Provide "View Activity History" link from User Management screen  
- Display a timeline of all actions performed by the selected user  
- Show events in reverse chronological order (most recent first)  
- Display:  
  - Action Type  
  - Target Entity  
  - Timestamp  
  - Details summary  
- Provide same filtering as Audit trail (Date Range, Action Type)  
- Calculate and display user statistics:  
  - **Total Bookings Created**  
  - **Bookings Cancelled** (count and percentage)  
  - **Cancellation Rate:** (Cancelled / Total Created) × 100%  
  - **Last Login Date**  
  - **Account Age:** Days since account creation  
  - **Most Frequently Booked Room**  
- Link to related entities:  
  - Click booking ID to view booking details  
  - Click room name to view room details  
- Export user activity report as CSV or Excel

**SHALL:**

- Be accessible only to Directors and System Administrators  
- Display user information at top (Name, Email, Role, Status, Department)  
- Provide "Back to User List" navigation

**Business Benefit:**  
Enables quick investigation of user behavior patterns and troubleshooting of user-reported issues without searching through global logs.

---

#### 7.3.3 System Configuration Management

**Purpose:**  
Allow System Administrators to fine-tune system behavior without requiring software updates or code changes.

**Target Roles:** System Administrator only

**Requirements:**

**MUST Provide Configuration Interface:**

Organized into sections:

**1\. Session & Security Settings (Configurable):**

- **Session Timeout Duration:**  
  - Input: Number field (minutes)  
  - Default: 30 minutes  
  - Range: 15-120 minutes  
  - Description: "Auto-logout after this period of inactivity"  
- **Password Reset Token Expiration:**  
  - Input: Number field (minutes)  
  - Default: 30 minutes  
  - Range: 5-60 minutes  
  - Description: "How long password reset links remain valid"  
- **Login Attempt Limit:**  
  - Input: Number field (attempts)  
  - Default: 5 attempts  
  - Range: 3-10 attempts  
  - Description: "Maximum failed login attempts before temporary lockout"  
  - Additional field: Lockout duration (minutes, default: 15\)

**2\. Password Policy (Read-Only / Hardcoded):**

- Display current requirements (no edit):  
  - Minimum 8 characters  
  - Must contain letters  
  - Must contain numbers  
  - Must contain symbols  
- Note: "These requirements are set by organizational policy and cannot be changed."

**3\. Booking Rules (Read-Only / Hardcoded):**

- Display current rules (no edit):  
  - **Maximum Advance Booking Period:** Unlimited  
  - **Operating Hours:** 8:00 AM \- 6:00 PM  
  - **Booking Time Increments:** 30 minutes  
  - **Minimum Booking Duration:** 30 minutes  
  - **Maximum Booking Duration:** 8 hours  
  - **Single-Day Only:** Yes (no overnight bookings)  
  - **Recurring Booking Maximum Period:** 1 year from start date  
  - **Purpose/Description Max Length:** 500 characters  
- Note: "These rules are set by organizational policy and cannot be changed."

**4\. Notification Settings (Configurable):**

- **Master Toggle:**  
  - Checkbox: "Enable Email Notifications"  
  - If OFF: All notifications are disabled  
- **Individual Notification Toggles:**  
  - Each notification type has its own toggle:  
    - ✓ Account Creation (Welcome Email)  
    - ✓ Password Reset  
    - ✓ Booking Created (to User)  
    - ✓ Booking Created (to Approvers)  
    - ✓ Booking Approved  
    - ✓ Booking Rejected  
    - ✓ Booking Cancelled  
    - ✓ Booking Reminder (24h before)  
    - ✓ Room Status Changed  
  - Disabled if Master Toggle is OFF

**5\. System Maintenance (Configurable):**

- **Maintenance Mode:**  
  - Toggle switch: ON / OFF  
  - When ON:  
    - Regular Users cannot log in  
    - Show "System Under Maintenance" page to non-admins  
    - Administrators and System Admins can still log in  
  - Display current status prominently  
  - Require confirmation before enabling

**6\. Email Server Settings (Configurable \- Optional for V1):**

- SMTP Host  
- SMTP Port  
- SMTP Username  
- SMTP Password (masked)  
- From Email Address  
- From Name (e.g., "MRBS Notifications")  
- "Test Connection" button

**MUST Implement Saving:**

- "Save Changes" button at bottom of form  
- Require confirmation for critical changes:  
  - Enabling Maintenance Mode  
  - Disabling all notifications  
  - Changing session timeout to very short period (\<20 mins)  
- Validate all inputs:  
  - Number fields are within allowed ranges  
  - Email format is valid  
- Apply changes immediately after save  
- Display success message: "Configuration updated successfully"  
- Display error messages if validation fails

**SHALL:**

- Log all configuration changes in audit trail with:  
  - Setting name  
  - Old value → New value  
  - Timestamp  
  - Admin who made the change  
- Display last modified timestamp for each section  
- Provide "Reset to Defaults" button for each configurable section  
- Show warning if changing setting may impact active users (e.g., reducing session timeout)

**SHOULD:**

- Provide inline help text or tooltips for each setting  
- Display current active users count when enabling Maintenance Mode  
- Offer "Test Email" button to send test notification

**Business Benefit:**  
Flexible configuration allows the system to adapt to changing organizational needs (e.g., increased security with shorter timeouts, disabling notifications during testing) without requiring developer intervention.

---

### 7.4 Notification System

**Purpose:**  
Manage the automated communication channel to keep users informed of booking status and actions. This section documents the **configuration and management** of notifications (triggers are defined in previous phases).

#### 7.4.1 Email Notification System

**Requirements:**

**MUST Support System-Wide Configuration:**

- Accessible via System Configuration (Section 7.3.3)  
- Master toggle to enable/disable all notifications  
- Individual toggles per notification type  
- SMTP server configuration (host, port, credentials)

**MUST Support User-Level Preferences:**

- Each user can configure their own notification preferences via profile settings  
- Allow opt-out of **non-critical** notifications:  
  - Booking Reminder (24h before)  
  - New Pending Approval (for Admins \- digest mode)  
- **Cannot opt-out** of critical notifications:  
  - Password Reset  
  - Booking Status Change (Approved/Rejected/Cancelled)  
  - Account Created/Deactivated  
- Default: All notifications enabled

**MUST Send Notifications For:** (As referenced throughout Phases 1-3)

- Account Creation (Welcome email with credentials)  
- Password Reset Link  
- Booking Created (confirmation to user)  
- Booking Created (notification to approvers)  
- Booking Approved (confirmation to user)  
- Booking Rejected (notification with reason to user)  
- Booking Cancelled (confirmation to user)  
- Booking Cancelled (notification to admin if admin cancelled on behalf)  
- Booking Reminder (24 hours before start time)  
- Room Status Changed to Maintenance (to users with upcoming bookings in that room)

**SHALL:**

- Use HTML email templates with OIB Group branding (logo, colors)  
- Include unsubscribe link for non-critical notifications  
- Queue emails for asynchronous sending (don't block user actions)  
- Retry failed deliveries up to 3 times with exponential backoff  
- Send notifications within 5 minutes of triggering event  
- Include direct links to relevant pages (e.g., "View Booking" button)

**SHOULD:**

- Support plain text fallback for email clients that don't support HTML  
- Allow System Admins to customize email templates (optional for V1)

**Business Benefit:**  
Automated notifications keep all stakeholders informed without manual communication, reducing delays and miscommunication.

---

#### 7.4.2 Notification History Log

**Purpose:**  
Track delivery status of emails to troubleshoot communication issues and verify notifications were sent.

**Target Roles:** System Administrator

**Requirements:**

**MUST Log Every Email Attempt:**

- **Recipient:** Email address  
- **Recipient Name:** User's full name  
- **Subject:** Email subject line  
- **Notification Type:** Category (e.g., "Booking Approved", "Password Reset")  
- **Related Entity:** Linked booking ID, user ID, or room ID if applicable  
- **Timestamp:** When send was attempted  
- **Status:** Sent, Failed, Queued, Bounced  
- **Error Message:** If status is Failed or Bounced  
- **Retry Count:** Number of retry attempts

**MUST Provide Notification Log Viewer:**

- Accessible via Admin Dashboard or System Configuration  
- Display in table format with columns: Timestamp, Recipient, Type, Status, Actions  
- Paginate results  
- Sort by Timestamp (default: most recent first)

**MUST Provide Filtering:**

- By Recipient (email or name search)  
- By Status (Sent, Failed, Queued, Bounced)  
- By Notification Type  
- By Date Range

**SHALL:**

- Provide "View Email Content" link to see what was sent (stored HTML)  
- Provide "Resend" button for failed notifications  
- Display status badge with color coding:  
  - Sent: Green  
  - Queued: Blue  
  - Failed: Red  
  - Bounced: Orange  
- Export log as CSV with all fields

**SHOULD:**

- Automatically retry failed emails (handled by email service)  
- Display statistics: Total sent today, Failed today, Delivery rate %

**Business Benefit:**  
Troubleshooting tool for investigating why users claim they didn't receive notifications, and monitoring email delivery health.

---

### 7.5 Reporting & Analytics

**Purpose:**  
Provide actionable insights into space utilization and user behavior to support resource planning and monitoring.

**Target Roles:** Administrator, Director, System Administrator

**General Reporting Requirements:**

All reports in this section share these common requirements:

**MUST:**

- **On-Demand Generation Only:** Reports are generated when user clicks "Generate Report" (no scheduled/automated generation)  
- **Date Range Selection:**  
  - Provide custom Start Date and End Date pickers  
  - Provide preset buttons:  
    - Today  
    - Yesterday  
    - Last 7 Days  
    - Last 30 Days  
    - This Month  
    - Last Month  
    - This Year  
  - Validate Start Date ≤ End Date  
  - Default date range: Based on report type (Daily → Today, Monthly → This Month)  
- **Export Functionality:**  
  - Support **CSV** format (comma-separated values for Excel import)  
  - Support **Excel** format (.xlsx with formatted tables and headers)  
  - Support **PDF** format (formatted, print-ready layout)  
  - Generate within 30 seconds for typical data volumes  
  - Filename format: `[ReportType]_[DateRange]_[Timestamp].[ext]`  
- **On-Screen Display:**  
  - Show report preview before export  
  - Use responsive tables  
  - Display data summary at top (e.g., "Showing 45 bookings from Jan 1-31, 2025")  
- **Print Layout:**  
  - Provide "Print" button  
  - Use print-specific CSS:  
    - A4/Letter paper size  
    - Remove navigation and buttons in print view  
    - Include header with report title and date range  
    - Include footer with page numbers and generation timestamp  
    - Use monochrome-friendly colors (readable in grayscale)  
    - Ensure proper page breaks (don't split tables mid-row)

**SHALL:**

- Display loading indicator during report generation  
- Show "No data found" message if date range has no results  
- Provide "Clear Filters" button to reset to defaults

---

#### 7.5.1 Daily Bookings Report

**Purpose:**  
Provide a comprehensive view of all bookings scheduled for a specific date.

**Requirements:**

**MUST Display:**

- **Report Title:** "Daily Bookings Report \- \[Selected Date\]"  
- **Date Selector:** Single date picker (default: Today)  
- **Room-Wise Breakdown:**  
  - Group bookings by room  
  - For each room, show:  
    - Room name and capacity  
    - All bookings sorted by start time  
- **Booking Details Table:**  
  - Columns:  
    - Time (Start \- End)  
    - Booking Reference  
    - Booker Name  
    - Department  
    - Purpose/Description  
    - Attendees (count, if provided)  
    - Status (Confirmed, Pending, Cancelled with badge)  
  - Highlight Cancelled bookings (strikethrough)  
- **Summary Section:**  
  - Total Bookings: X  
  - Confirmed: X  
  - Pending: X  
  - Cancelled: X  
  - Room Occupancy Rate: (Total booked hours / Total available hours) %  
  - Busiest Room: \[Room Name\] (X bookings)

**SHALL:**

- Default to current date  
- Show empty state for rooms with no bookings ("No bookings for this date")  
- Export includes all sections (room groups, summary)

**Business Benefit:**  
Daily snapshot helps administrators prepare for the day, identify conflicts, and monitor booking activity.

---

#### 7.5.2 Monthly Bookings Report

**Purpose:**  
Analyze booking patterns and trends over a selected month.

**Requirements:**

**MUST Display:**

- **Report Title:** "Monthly Bookings Report \- \[Month Year\]"  
- **Month Selector:** Month and Year pickers (default: Current Month)  
- **Summary Statistics:**  
  - Total Bookings: X  
  - Confirmed: X  
  - Pending: X  
  - Cancelled: X  
  - Cancellation Rate: (Cancelled / Total) %  
  - Most Active Day: \[Date\] (X bookings)  
  - Most Popular Room: \[Room Name\] (X bookings)  
  - Total Unique Users: X  
- **Bookings by Day Chart:**  
  - Bar chart showing booking count per day of month  
  - X-axis: Days (1-31)  
  - Y-axis: Number of bookings  
  - Color-code by status (Confirmed, Pending, Cancelled)  
- **Bookings by Room Chart:**  
  - Horizontal bar chart  
  - X-axis: Booking count  
  - Y-axis: Room names  
  - Show top 10 rooms (or all if ≤10)  
- **Detailed Listings (Optional for V1):**  
  - Table of all bookings with same columns as Daily Report  
  - Grouped by week

**SHALL:**

- Display charts as images in PDF export  
- Include only numerical data in CSV export  
- Show "Month-over-Month Change" if previous month data exists (e.g., "+15% vs last month")

**Business Benefit:**  
Monthly trends help identify patterns, high-demand periods, and inform capacity planning decisions.

---

#### 7.5.3 User Bookings Report

**Purpose:**  
Generate individual user booking history or aggregate user behavior analysis.

**Requirements:**

**MUST Provide Two Modes:**

**Mode 1: Single User Report**

- **User Selector:** Dropdown or search to select one user  
- **Date Range:** Custom or presets  
- **Display:**  
  - User Information: Name, Email, Department, Role  
  - Summary Statistics:  
    - Total Bookings Created: X  
    - Confirmed: X  
    - Pending: X  
    - Cancelled: X  
    - Rejected: X  
    - Cancellation Rate: %  
    - Average Booking Duration: X hours  
    - Most Frequently Booked Room: \[Room Name\]  
  - Booking List Table:  
    - Date  
    - Room  
    - Time  
    - Status  
    - Purpose

**Mode 2: All Users Summary (Optional for V1)**

- **Date Range:** Custom or presets  
- **Display:**  
  - Table with columns:  
    - User Name  
    - Department  
    - Total Bookings  
    - Cancellation Rate  
    - Last Booking Date  
  - Sort by Total Bookings (descending)  
  - Show top 20 users

**SHALL:**

- Respect user privacy: Only Directors and System Admins can view  
- Link to User Activity History (Section 7.3.2) for detailed investigation

**Business Benefit:**  
Understand individual user behavior, identify power users, and investigate excessive cancellations or unusual patterns.

---

#### 7.5.4 Room Utilization Report

**Purpose:**  
Analyze meeting room usage efficiency to support resource planning and identify underutilized or overbooked rooms.

**Requirements:**

**MUST Display:**

- **Report Title:** "Room Utilization Report \- \[Date Range\]"  
- **Date Range:** Custom or presets (default: This Month)  
- **Per-Room Metrics Table:**  
  - Columns:  
    - Room Name  
    - Capacity  
    - Total Bookings  
    - Total Hours Booked  
    - Available Hours (Operating Hours × Days)  
    - **Utilization Rate: (Booked Hours / Available Hours) × 100%**  
    - Peak Hour (most frequently booked time slot)  
    - Status (Underutilized \<30% / Normal 30-80% / High \>80%)  
  - Sort by Utilization Rate (descending)  
- **Utilization Overview Chart:**  
  - Horizontal bar chart  
  - X-axis: Utilization %  
  - Y-axis: Room names  
  - Color-code: Red (\>80%), Green (30-80%), Yellow (\<30%)  
- **Peak Hours Heatmap:**  
  - Grid showing hours (8 AM \- 6 PM) vs. rooms  
  - Color intensity \= booking frequency  
  - Darker \= more frequently booked  
- **Recommendations (Text):**  
  - "Underutilized Rooms:" List rooms with \<30% utilization  
  - "Overutilized Rooms:" List rooms with \>80% utilization  
  - Suggestion: "Consider reallocating \[Underutilized Room\] capacity or converting to different use"

**SHALL:**

- Only count **Confirmed** bookings in utilization calculation (ignore Pending/Cancelled)  
- Consider room operating hours (8 AM \- 6 PM) as available time  
- Exclude days when room was Under Maintenance from available hours  
- Display "N/A" for utilization if room was inactive entire period

**SHOULD:**

- Provide comparison with previous period (optional for V1)

**Business Benefit:**  
Identifies optimization opportunities (convert underutilized rooms, add capacity for overbooked rooms), supporting efficient space management and cost reduction.  
---

## 8\. Phase 5: System Quality & Deployment

### 8.1 Phase Overview

**Objective:**  
Ensure the MRBS is production-ready by establishing standards for responsive design, robust data validation, comprehensive error handling, security best practices, and a smooth deployment process.

**Deliverables:**

- Responsive design requirements for multi-device compatibility  
- Comprehensive data validation standards  
- Error handling and user feedback mechanisms  
- Security requirements and threat mitigations  
- Deployment and go-live procedures

**Business Value:**

- **User Experience:** Consistent, reliable experience across all devices  
- **Data Integrity:** Validation prevents bad data from entering the system  
- **User Confidence:** Clear error messages help users recover from mistakes  
- **Security:** Protection against common web vulnerabilities ensures system and data safety  
- **Smooth Transition:** Structured deployment minimizes disruption to operations

---

### 8.2 Responsive Design

**Purpose:**  
Ensure the MRBS is accessible and functional across all device types and screen sizes.

**Requirements:**

**MUST:**

- **Support Multiple Device Types:**  
  - Desktop computers  
  - Tablets  
  - Mobile phones  
- **Responsive Layout:**  
  - Automatically adapt layout to screen size  
  - Maintain functionality across all screen sizes  
  - Ensure all features are accessible on all devices  
- **Touch-Friendly Interface:**  
  - Buttons and interactive elements must be easily tappable on touch devices  
  - Minimum touch target size for mobile interfaces  
  - No functionality requires hover-only actions (all hover interactions must have touch alternatives)

**SHALL:**

- Test on common devices (iOS, Android, Windows, macOS)  
- Ensure forms are usable on mobile devices  
- Maintain readability of text across all screen sizes  
- Preserve data table functionality (horizontal scroll or adaptive columns)

**SHOULD:**

- Optimize image sizes for mobile networks  
- Minimize network requests on mobile devices

**Business Benefit:**  
Staff can access the MRBS from any device (office desktop, meeting room tablet, personal smartphone), enabling on-the-go booking management and flexibility.

---

### 8.3 Data Validation & Error Handling

**Purpose:**  
Ensure data integrity through comprehensive validation and provide clear, actionable feedback when errors occur.

#### 8.3.1 Data Validation Standards

**MUST Implement Dual-Layer Validation:**

- **Client-Side Validation:**  
  - Validate immediately as user enters data (real-time feedback)  
  - Prevent form submission if validation fails  
  - Display clear, specific error messages near invalid fields  
  - Highlight invalid fields visually (red border, error icon)  
- **Server-Side Validation:**  
  - Re-validate all data on the server (never trust client input)  
  - Return detailed validation errors to client if data is invalid  
  - Log validation failures for security monitoring

**MUST Validate:**

- **Required Fields:** All mandatory fields must have values  
- **Data Types:** Email format, numbers, dates, URLs  
- **Length Constraints:** Minimum/maximum character limits  
- **Value Ranges:** Numerical bounds (e.g., capacity \> 0\)  
- **Uniqueness:** Staff numbers, email addresses must be unique  
- **Business Rules:** Date/time logic (start time \< end time, no past dates)  
- **Cross-Field Dependencies:** Room availability, user permissions

**SHALL Provide Clear Error Messages:**

- **Specific:** "Email address is required" not "Invalid input"  
- **Actionable:** "Password must contain at least one number" not "Password invalid"  
- **Friendly Tone:** Helpful, not accusatory  
- **Placement:** Display error next to the relevant field

**Example:**

- ❌ Bad: "Error"  
- ✅ Good: "Please enter a valid email address (e.g., [user@example.com](mailto:user@example.com))"

---

#### 8.3.2 Error Handling

**Purpose:**  
Handle system errors gracefully and guide users toward resolution.

**MUST Handle the Following Error Types:**

1. **Validation Errors**  
     
   - Display: Inline near field \+ summary at top of form  
   - Example: "Please correct the highlighted fields below"

   

2. **Network Errors**  
     
   - Display: Toast notification  
   - Example: "Unable to connect to server. Please check your connection and try again."  
   - Provide "Retry" button

   

3. **Permission Errors (403 Forbidden)**  
     
   - Display: Modal dialog or dedicated error page  
   - Example: "You don't have permission to perform this action. Contact your administrator if you believe this is incorrect."

   

4. **Not Found Errors (404)**  
     
   - Display: Dedicated error page  
   - Example: "The page or resource you're looking for doesn't exist. Return to Dashboard"  
   - Provide navigation links

   

5. **Server Errors (500 Internal Server Error)**  
     
   - Display: Modal or error page  
   - Example: "Something went wrong on our end. Please try again in a few moments. If the problem persists, contact support."  
   - Log error details for administrator investigation  
   - Display reference number/timestamp for support

   

6. **Session Expired**  
     
   - Display: Modal dialog  
   - Example: "Your session has expired for security. Please log in again."  
   - Redirect to login page  
   - Preserve form data if possible (store in session storage)

   

7. **Conflict Errors (e.g., double booking)**  
     
   - Display: Modal dialog  
   - Example: "This room is no longer available at the selected time. Another booking was just confirmed. Please select a different time."  
   - Suggest alternatives

**SHALL:**

- Never display technical error details to end users (stack traces, SQL errors)  
- Log all errors server-side with context (user, action, timestamp)  
- Provide consistent error UI across the system  
- Allow users to recover from errors (back button, retry, alternative actions)

**Error Display Patterns:**

- **Inline (near field):** Validation errors  
- **Top of page banner:** Form submission errors, multiple validation errors  
- **Toast notification:** Success confirmations, non-critical warnings, network issues  
- **Modal dialog:** Critical errors requiring user acknowledgment, session expired, conflicts  
- **Dedicated error page:** 404, 500, permission errors when page cannot load

---

### 8.4 Security Requirements

**Purpose:**  
Protect the system, user data, and organizational information from common web vulnerabilities and unauthorized access.

#### 8.4.1 General Security Principles

**MUST:**

- Apply the **Principle of Least Privilege:** Users have only the permissions necessary for their role  
- Implement **Defense in Depth:** Multiple layers of security (authentication \+ authorization \+ validation \+ logging)  
- Practice **Secure by Default:** All features require authentication unless explicitly public  
- Maintain **Security Through Audit:** All security-relevant actions are logged

---

#### 8.4.2 Specific Threat Mitigations

**MUST Implement Protection Against:**

**1\. Cross-Site Scripting (XSS)**

- **Threat:** Attacker injects malicious scripts into web pages viewed by other users  
- **Mitigation:**  
  - Sanitize all user input before storing in database  
  - Escape all output when rendering user-generated content  
  - Use Content Security Policy (CSP) headers  
  - Never use `innerHTML` with user data; use safe DOM methods  
- **Example:** Room descriptions, booking purposes, user names

**2\. Cross-Site Request Forgery (CSRF)**

- **Threat:** Attacker tricks authenticated user into performing unwanted actions  
- **Mitigation:**  
  - Include CSRF tokens in all state-changing requests (POST, PUT, DELETE)  
  - Validate CSRF tokens on server before processing requests  
  - Use SameSite cookie attribute  
- **Applies to:** All forms, all API endpoints that modify data

**3\. SQL Injection**

- **Threat:** Attacker manipulates database queries through user input  
- **Mitigation:**  
  - Use parameterized queries (prepared statements) for ALL database operations  
  - Never concatenate user input into SQL strings  
  - Apply least privilege to database user account (limit permissions)  
- **Applies to:** All database interactions

**4\. Session Hijacking**

- **Threat:** Attacker steals or guesses session tokens to impersonate users  
- **Mitigation:**  
  - Generate cryptographically secure random session tokens  
  - Transmit session cookies only over HTTPS (Secure flag)  
  - Set HttpOnly flag on session cookies (prevent JavaScript access)  
  - Implement session timeout (30 minutes inactivity)  
  - Regenerate session ID after login  
  - Invalidate sessions on logout

**5\. Brute Force Attacks**

- **Threat:** Attacker tries multiple passwords to guess credentials  
- **Mitigation:**  
  - Implement login attempt limits (5 attempts per 15 minutes)  
  - Implement temporary account lockout  
  - Use CAPTCHA after multiple failed attempts (optional)  
  - Display generic error message (don't reveal if email exists)  
  - Log all failed login attempts with IP address

**6\. Insecure Direct Object References (IDOR)**

- **Threat:** User manipulates IDs in URLs to access resources they shouldn't  
- **Mitigation:**  
  - Always verify user has permission to access requested resource  
  - Never rely on obscurity (hiding URLs)  
  - Check authorization on server for EVERY request  
- **Example:** User tries to view `/booking/123` belonging to someone else

---

#### 8.4.3 Authentication & Authorization Security

**MUST:**

- **Password Security:**  
  - Hash passwords using bcrypt, Argon2, or PBKDF2 (NEVER store plain text)  
  - Enforce minimum password requirements (8 chars, letters, numbers, symbols)  
  - Never display passwords in logs or error messages  
- **Session Security:**  
  - Use secure, random session tokens  
  - Set HttpOnly and Secure flags on cookies  
  - Implement automatic logout after 30 minutesinactivity  
- **Authorization Checks:**  
  - Verify user role and permissions on every request  
  - Block unauthorized access to admin/director-only features  
  - Return 403 Forbidden for permission errors (not 404\)

**SHALL:**

- Require re-authentication for sensitive actions (password change, role change)  
- Log all authentication events (login, logout, failed attempts)  
- Invalidate all user sessions when user is deactivated

---

#### 8.4.4 Data Protection

**MUST:**

- **Encryption in Transit:**  
  - Use HTTPS/TLS for all communication (enforce SSL)  
  - Redirect HTTP to HTTPS  
- **Encryption at Rest:**  
  - Encrypt sensitive database fields (passwords via hashing)  
  - Follow database encryption best practices  
- **Audit Logs:**  
  - Store immutable audit logs (cannot be edited/deleted)  
  - Include sufficient detail for forensic analysis  
  - Retain for minimum 2 years

**SHALL:**

- Restrict database access to application server only  
- Use database connection credentials securely (environment variables, not hardcoded)  
- Implement regular security updates and patches

---

### 8.5 Deployment & Go-Live

**Purpose:**  
Ensure smooth, successful deployment of the MRBS to production with minimal disruption.

#### 8.5.1 Environment Setup

**MUST Prepare Production Environment:**

- **Server Infrastructure:**  
  - Web server (Apache, Nginx, or IIS)  
  - Application server (PHP, Node.js, Python, or Java runtime as per chosen technology)  
  - Database server (MySQL, PostgreSQL, or chosen database)  
  - Sufficient resources (CPU, RAM, disk space) for expected user load  
- **Software Configuration:**  
  - Install required runtime and dependencies  
  - Configure database connections  
  - Set up HTTPS/SSL certificates  
  - Configure email server (SMTP) for notifications  
- **Security Hardening:**  
  - Enable firewall rules  
  - Disable unnecessary services  
  - Set appropriate file permissions  
  - Configure security headers (CSP, X-Frame-Options, etc.)

**SHALL:**

- Set up separate environments: Development, Staging, Production  
- Use environment-specific configuration files  
- Test in staging environment before production deployment

---

#### 8.5.2 Data Migration

**MUST Prepare Initial Data:**

- **Administrative Accounts:**  
  - Create initial System Administrator account (for OIB Group Admin staff)  
  - Create initial Director account (for management oversight)  
  - Create initial Administrator account (for daily booking management)  
- **Meeting Rooms Data:**  
  - Import complete room inventory with:  
    - Room names/identifiers  
    - Capacity  
    - Floor/location  
    - Status (set all to Active initially)  
    - Photos (if available)  
  - Validate all room data before import  
- **System Configuration:**  
  - Configure initial system settings (session timeout, operating hours, notifications)  
  - Set up email templates with OIB Group branding

**SHALL:**

- Test data migration in staging environment first  
- Validate all imported data  
- Provide rollback plan if migration fails  
- Document migration process

**SHOULD:**

- Import historical booking data if transitioning from existing system (optional)

---

#### 8.5.3 Rollout Strategy

**MUST Use All-at-Once Rollout:**

- **Go-Live Date:** Single coordinated launch date for all users  
- **Announcement:** Communicate go-live date and time to all staff in advance  
- **Cutover:**  
  - Schedule deployment during off-peak hours (e.g., weekend or evening)  
  - Decommission old booking system (if applicable)  
  - Activate MRBS for all users  
  - Send organization-wide announcement email

**MUST Provide on Go-Live Day:**

- **User Communication:**  
  - System login URL  
  - Initial login instructions (for new accounts)  
  - Quick start guide (how to book a room)  
  - Support contact information  
- **Administrator Readiness:**  
  - Administrators and Directors available to assist users  
  - IT support available for technical issues  
  - System monitoring active

**SHALL Monitor Post-Deployment:**

- **First Week:**  
  - Monitor system performance and errors  
  - Track user adoption (login count, booking count)  
  - Collect user feedback on issues  
  - Address critical bugs immediately  
- **First Month:**  
  - Review audit logs for unusual activity  
  - Monitor server resource usage  
  - Assess if additional capacity needed

**Rollback Plan:**

- If critical issues prevent usage:  
  - Revert to previous system (if applicable)  
  - Communicate issue to users with timeline for resolution  
  - Fix issues in staging and redeploy

---

## 9\. Glossary

This glossary defines key terms used throughout this requirements document to ensure clarity for both technical and non-technical stakeholders.

---

### A. User Roles

**Administrator**  
Staff member responsible for daily booking approvals, room management, and generating reports. Cannot manage users or configure system settings.

**Director**  
Senior management role with oversight responsibilities. Has all Administrator capabilities plus user management and audit trail access.

**Regular User**  
Standard staff members who can create and manage their own bookings. Default role assigned to all new accounts.

**System Administrator**  
IT staff member responsible for technical system configuration, user account management, and system maintenance. Cannot approve bookings or manage rooms.

---

### B. Booking Terms

**Approval Queue**  
List of pending bookings awaiting review and approval by Administrators or Directors.

**Booking**  
Reservation of a meeting room for a specific date, time, and purpose.

**Booking Reference**  
Unique identifier assigned to each booking (e.g., BK-2025-00123).

**Person in Charge (PIC)**  
The staff member who created the booking and is responsible for the meeting.

**Recurring Booking**  
Booking that repeats on a regular schedule (daily, weekly, or monthly) for up to one year.

**Series**  
Group of recurring bookings linked together with a common Series ID.

**Series ID**  
Unique identifier that links all occurrences of a recurring booking together.

---

### C. Booking Status Values

**Active** (Room Status)  
Room is available for booking.

**Cancelled**  
Booking was created but later cancelled by the user or administrator.

**Completed**  
Booking has finished (end time has passed).

**Confirmed**  
Booking has been approved by an Administrator or Director.

**Inactive** (Room/User Status)  
Room or user account is deactivated and not available for use.

**Pending**  
Booking has been submitted and is awaiting approval.

**Rejected**  
Booking was reviewed and declined by an Administrator or Director.

**Under Maintenance** (Room Status)  
Room is temporarily unavailable due to scheduled maintenance.

---

### D. Room & Facility Terms

**Capacity**  
Maximum number of people a room can accommodate.

**Operating Hours**  
Time period during which rooms are available for booking (8:00 AM \- 6:00 PM).

**Room Utilization**  
Percentage of available time that a room is actually booked and used.

**Utilization Rate**  
Calculated as (Total Booked Hours / Total Available Hours) × 100%.

---

### E. System Features & Functions

**Audit Trail**  
Immutable log of all system actions (logins, bookings, changes) for accountability and security.

**Dashboard**  
Home page showing personalized overview and quick access to key functions.

**Go-Live**  
The date when the system is officially launched and made available to all staff.

**Maintenance Mode**  
System state where only Administrators and System Administrators can log in, used during updates or repairs.

**Session**  
Authenticated user connection that expires after 30 minutes of inactivity.

**Session Timeout**  
Automatic logout after 30 minutes of user inactivity for security.

---

### F. Technical Terms

**Authentication**  
Process of verifying a user's identity through email and password.

**Authorization**  
Process of determining what actions a user is permitted to perform based on their role.

**Client-Side Validation**  
Data verification performed in the user's browser before submitting to the server.

**CSRF (Cross-Site Request Forgery)**  
Security vulnerability where attackers trick users into performing unwanted actions; prevented using tokens.

**CSV (Comma-Separated Values)**  
File format for exporting data that can be opened in Excel.

**HTTPS (Hypertext Transfer Protocol Secure)**  
Encrypted web communication protocol ensuring data security.

**IDOR (Insecure Direct Object References)**  
Security vulnerability where users access resources by manipulating URLs; prevented through authorization checks.

**PDF (Portable Document Format)**  
File format for print-ready documents.

**RBAC (Role-Based Access Control)**  
Security model where permissions are assigned based on user roles.

**Server-Side Validation**  
Data verification performed on the server to ensure data integrity and security.

**SMTP (Simple Mail Transfer Protocol)**  
Protocol used for sending email notifications.

**SQL Injection**  
Security vulnerability where attackers manipulate database queries; prevented using parameterized queries.

**SSL/TLS (Secure Sockets Layer / Transport Layer Security)**  
Encryption protocols ensuring secure data transmission over the internet.

**XSS (Cross-Site Scripting)**  
Security vulnerability where attackers inject malicious scripts; prevented through input sanitization and output escaping.

---

### G. Notifications & Reporting

**Email Notification**  
Automated email sent to users when booking status changes or important events occur.

**Export**  
Download report data in PDF, Excel, or CSV format.

**Toast Notification**  
Brief pop-up message shown on screen to confirm actions or display warnings.

---

### H. System & Deployment

**Environment**  
Separate system instances (Development, Staging, Production) used for building, testing, and running the live system.

**Migration**  
Process of transferring initial data (rooms, admin accounts) into the new system.

**Rollback**  
Process of reverting to the previous system version if critical issues occur after deployment.

**Rollout**  
Deployment strategy; MRBS uses all-at-once rollout where all users gain access simultaneously.

---

### I. Acronyms

**MRBS**  
Meeting Room Booking System \- the system described in this document.

**UI/UX**  
User Interface / User Experience \- how the system looks and feels to users.

**OIB Group**  
Oriental Interest Berhad Group \- Company/Client Name
