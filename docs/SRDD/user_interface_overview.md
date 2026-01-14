# Overview of User Interface

This section outlines the functionality of the Meeting Room Booking System (MRBS) from the end-user's perspective, detailing how users interact with the system to perform key tasks and the feedback mechanisms in place to guide their experience.

## 1. User Access and Navigation

### 1.1 Login and Authentication
All users access the system via a secure login page.
-   **Action**: Users enter their registered email address and password.
-   **Feedback**:
    -   **Success**: Upon successful authentication, the user is immediately redirected to their specific dashboard based on their role (Regular User, Administrator, or Director).
    -   **Error**: Invalid credentials trigger an immediate "Invalid email or password" error message.
    -   **Security**: After 5 failed attempts, the account is temporarily locked, and the user is informed to contact the administrator or wait before trying again.

### 1.2 Dashboard
The dashboard serves as the central hub for all user activities.
-   **Regular Users**: See a personalized view featuring:
    -   **Quick Actions**: Prominent buttons to "Book a Room" or "Check Availability".
    -   **Upcoming Bookings**: A list of their scheduled meetings for the day and week.
    -   **Usage Stats**: A summary of their recent activity.
-   **Administrators/Directors**: See an expanded view featuring:
    -   **System Overview**: Widgets displaying total bookings today, active rooms, and utilization metrics.
    -   **Management Tools**: Quick links to manage rooms, users, and generate reports.

---

## 2. Core Functionality

### 2.1 Room Discovery and Availability
Users can find suitable meeting spaces using two primary methods:
-   **Search Filters**: Users filter rooms by Date, Time, Capacity (e.g., 4 pax, 10 pax), and Amenities (e.g., Projector, Whiteboard).
-   **Interactive Calendar**: A visual calendar (Day/Week/Month view) displays room availability.
    -   **Feedback**: The calendar uses color-coded blocks to indicate status (e.g., Green for Available, Red for Booked/Busy, Grey for Maintenance). This provides instant visual confirmation of free slots.

### 2.2 Creating a Booking
The booking process is designed to be streamlined and error-proof.
-   **Step 1: Selection**: Clicking a "Book" button or an open time slot on the calendar opens the Booking Form.
-   **Step 2: Details**: Users input meeting details:
    -   **Title & Description**: Purpose of the meeting.
    -   **Participants**: Expected number of attendees.
    -   **Recurring Options**: For repeating meetings (e.g., "Weekly Team Sync"), users can select Daily, Weekly, or Monthly recurrence patterns.
-   **Step 3: Confirmation**:
    -   **Conflict Check**: The system automatically checks for conflicts in real-time. If the room is already booked, the user cannot proceed.
    -   **Submission**: Clicking "Confirm Booking" finalizes the reservation.

### 2.3 Managing Bookings
The system provides tailored management interfaces for different user roles.

#### 2.3.1 Regular Users (My Bookings)
Regular users maintain control over their own scheduled events.
-   **My Bookings List**: A dedicated section lists all past and future reservations made by the user.
-   **Cancellation**: Users can cancel their own future bookings with a single click.
    -   **Prompt**: A standard confirmation modal ("Are you sure you want to cancel?") prevents accidental deletions.
    -   **Result**: The slot is immediately freed up for others.
-   **Modifications**: Users can update meeting details (e.g., change the title or description) for their upcoming events.

#### 2.3.2 Administrators (All Bookings)
Administrators have full oversight of all bookings in the system.
-   **All Bookings View**: A comprehensive table listing every booking, with filters for User, Room, Date Range, and Status.
-   **Global Management**: Admins can View, Edit, or Cancel **any** booking regardless of the creator.
    -   **Conflict Resolution**: Admins can intervene to resolve double-booking claims or priority changes.
    -   **Override & Notification**: If an Admin cancels a booking, they are prompted to provide a reason, which is automatically emailed to the affected user.

### 2.4 Administrative Functions
Administrators have access to elevated privileges to ensure system integrity.
-   **Room Management**: Admins can Add, Edit, or Disable meeting rooms (e.g., for maintenance).
-   **User Management**: Admins create accounts for new staff, reset passwords, and manage user roles.
-   **System Settings**: Configuration of global settings such as booking windows or operational hours.

---

## 3. System Feedback and Notifications

The system provides consistent feedback to keep users informed of their actions and system state.

### 3.1 On-Screen Visual Feedback
-   **Toasts/Alerts**: Pop-up notifications appear at the top right of the screen to confirm actions (e.g., "Booking created successfully!" in green) or report errors (e.g., "Room is under maintenance" in red).
-   **Form Validation**: If a user misses a required field or enters an invalid time, the input field is highlighted in red with a helpful error message below it.
-   **Loading States**: When submitting a form or loading data, buttons show a spinning loader to indicate processing, preventing duplicate submissions.

### 3.2 Email Notifications
The system sends automated emails for critical events to ensure users are always up-to-date:
-   **Booking Confirmation**: Sent immediately after a booking is successfully created, containing the room name, time, and meeting details.
-   **Cancellation Alert**: Sent if a booking is cancelled by the user or an administrator.
-   **Registration/Password Reset**: Secure emails with links are sent for account setup and password recovery.
