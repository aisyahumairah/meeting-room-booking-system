# User Acceptance Test Cases - Phase 3: Booking System

**Project:** Meeting Room Booking System (MRBS)
**Module:** Bookings & Calendar
**Document ID:** UAT-PH3
**Version:** 1.0

## 1. Introduction
This section verifies the core purpose of the system: the ability for users to find, book, and manage meeting rooms, including complex recurring schedules.

## 2. Test Cases

| Test Case ID | Test Item | Preconditions | Input Data | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-PH3-001** | Check Availability | Room "Hall A" is free 10AM-11AM. | 1. Navigate to `/calendar`<br>2. Select "Hall A", Date: Today, Time: 10:00-11:00 | Slot is shown as "Available" (Green). | | |
| **TC-PH3-002** | Create Booking (Simple) | User logged in. Slot free. | 1. Click on Slot<br>2. Purpose: "Team Sync"<br>3. Click "Book" | Success message "Booking Confirmed". Slot turns Red/Blue on Calendar. | | |
| **TC-PH3-003** | Conflict Detection | Booking exists 10AM-11AM. | 1. Try to book same room 10:30-11:30 | Error message "Time slot overlapping" displayed. Booking rejected. | | |
| **TC-PH3-004** | Recurring Booking (Daily) | "Lab 1" free. | 1. Select "Recurring"<br>2. Repeat: Daily for 5 days<br>3. Click "Book" | 5 separate bookings created. Success message displayed. | | |
| **TC-PH3-005** | Recurring Conflict | Day 3 has a conflict. | 1. Repeat same steps as TC-PH3-004 | System warns: "Conflict on Day 3". Options: "Skip conflict" or "Cancel All". | | |
| **TC-PH3-006** | My Bookings List | User has bookings. | 1. Navigate to `My Bookings` | List displays all future bookings with Status (Confirmed/Pending). | | |
| **TC-PH3-007** | Cancel Booking | User owns a future booking. | 1. Navigate to `My Bookings`<br>2. Click "Cancel" on a booking<br>3. Confirm Alert | Status changes to "Cancelled". Slot becomes free on calendar. | | |
| **TC-PH3-008** | Booking Approval (Request) | Room requires approval. | 1. Book restricted room | Success message "Booking Pending Approval". Status is "Pending". | | |
| **TC-PH3-009** | Booking Approval (Admin) | "Pending" booking exists. | 1. Admin navigates to `Approvals`<br>2. Click "Approve" | Status changes to "Confirmed". User receives notification. | | |
| **TC-PH3-010** | Booking Rejection (Admin) | "Pending" booking exists. | 1. Admin navigates to `Approvals`<br>2. Click "Reject" | Status changes to "Rejected". Slot becomes free. | | |
| **TC-PH3-011** | Calendar Navigation | Events exist next week. | 1. Navigate to `/calendar`<br>2. Click "Next Week" arrow | Calendar updates to show events for the correct week. | | |
