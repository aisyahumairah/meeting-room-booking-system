# User Acceptance Test Cases - Phase 1: Foundation & User Access

**Project:** Meeting Room Booking System (MRBS)
**Module:** Authentication & Profile Management
**Document ID:** UAT-PH1
**Version:** 1.0

## 1. Introduction
This section verifies the core authentication and user management functionalities from an end-user perspective.

## 2. Test Cases

| Test Case ID | Test Item | Preconditions | Input Data | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-PH1-001** | User Registration | User is not logged in. | 1. Navigate to `/register`<br>2. Fill Name, Email, Staff ID, Password<br>3. Click "Register" | System redirects to Dashboard/Login and displays success message. | | |
| **TC-PH1-002** | Registration Validation | User is not logged in. | 1. Navigate to `/register`<br>2. Leave required fields empty<br>3. Click "Register" | Validation errors are displayed for empty fields. Form is not submitted. | | |
| **TC-PH1-003** | User Login (Success) | Registered account exists. | 1. Navigate to `/login`<br>2. Enter valid Email & Password<br>3. Click "Login" | User is authenticated and redirected to the Dashboard. | | |
| **TC-PH1-004** | User Login (Failure) | None. | 1. Navigate to `/login`<br>2. Enter invalid Email or Password<br>3. Click "Login" | Error message "These credentials do not match our records" is displayed. | | |
| **TC-PH1-005** | Forgot Password (Request) | Registered email exists. | 1. Navigate to `/password/reset`<br>2. Enter registered Email<br>3. Click "Send Reset Link" | Success message displayed: "We have emailed your password reset link". | | |
| **TC-PH1-006** | Reset Password (Execution) | User received reset token. | 1. Click link in email<br>2. Enter New Password & Confirm<br>3. Click "Reset Password" | Password is updated. User can login with new password. | | |
| **TC-PH1-007** | User Logout | User is logged in. | 1. Click User Avatar in Navbar<br>2. Click "Log Out" | User Session is terminated. Redirected to Login page. | | |
| **TC-PH1-008** | Edit User Profile | User is logged in. | 1. Navigate to Profile Settings<br>2. Update Name or Phone<br>3. Click "Save Changes" | Success message displayed. New details are visible on page reload. | | |
| **TC-PH1-009** | Role-Based Dashboard | Admin User is logged in. | 1. Navigate to Dashboard (`/`) | Admin Dashboard loaded (showing Utilization Stats, User Management links). | | |
| **TC-PH1-010** | Role-Based Dashboard | Regular User is logged in. | 1. Navigate to Dashboard (`/`) | User Dashboard loaded (showing My Bookings, Upcoming Meetings). | | |
