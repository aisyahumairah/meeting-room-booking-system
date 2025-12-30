# User Acceptance Test Cases - Phase 5: Security & Usability

**Project:** Meeting Room Booking System (MRBS)
**Module:** Security, Performance & UX
**Document ID:** UAT-PH5
**Version:** 1.0

## 1. Introduction
This section verifies the non-functional requirements including system security, stability, and usability across different devices.

## 2. Test Cases

| Test Case ID | Test Item | Preconditions | Input Data | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-PH5-001** | Unauthorized Access (Admin Pages) | Regular User logged in. | 1. Attempt to navigate to `/admin/users` | Access Denied. User redirected to Dashboard or shown 403 Forbidden page. | | |
| **TC-PH5-002** | Unauthorized Access (Direct URL) | User not logged in. | 1. Attempt to navigate to `/my-bookings` | Redirected to Login Page. | | |
| **TC-PH5-003** | Session Timeout | User inactive for 30 mins (or configured time). | 1. Wait for timeout duration<br>2. Click any link | Redirected to Login Page with "Session Expired" message. | | |
| **TC-PH5-004** | Invalid Page Handling (404) | User logged in. | 1. Navigate to invalid URL (e.g., `/xyz`) | Custom 404 Page displayed with "Return to Dashboard" link. | | |
| **TC-PH5-005** | Form Validation (XSS) | User on Profile Edit. | 1. Enter `<script>alert('XSS')</script>` in Name field<br>2. Click "Save" | Input is sanitized or rejected. Script does NOT execute on page reload. | | |
| **TC-PH5-006** | CSRF Protection | User logged in. | 1. Inspect form source<br>2. Verify `_token` field exists | Hidden CSRF token is present in all POST forms. | | |
| **TC-PH5-007** | Mobile Responsiveness (Menu) | Browser resized to < 768px. | 1. Open Landing Page | Navbar collapses into a "Hamburger" menu icon. | | |
| **TC-PH5-008** | Mobile Responsiveness (Tables) | Browser resized to < 768px. | 1. Open Booking List | Tables scroll horizontally or stack content appropriately. No horizontal page scroll. | | |
| **TC-PH5-009** | Performance (Load Time) | Normal network conditions. | 1. Navigate between Dashboard and Calendar | Pages load in under 2 seconds. | | |
| **TC-PH5-010** | Browser Compatibility | Latest Chrome, Firefox, Edge. | 1. Perform core booking flow on each browser | Layout and functionality remain consistent across all supported browsers. | | |
