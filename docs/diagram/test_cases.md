# Test Cases

This document provides comprehensive test cases for the Meeting Room Booking System (MRBS) based on the use case descriptions.

---

## 1. Authentication & Authorization Test Cases

### TC-AUTH-001: Successful Login
**Use Case**: UC001  
**Preconditions**: User has valid credentials  
**Test Steps**:
1. Navigate to login page
2. Enter valid email address
3. Enter valid password
4. Click Login button

**Expected Result**: User is redirected to role-specific dashboard

---

### TC-AUTH-002: Invalid Credentials
**Use Case**: UC001  
**Preconditions**: None  
**Test Steps**:
1. Navigate to login page
2. Enter invalid email or password
3. Click Login button

**Expected Result**: Error message "Invalid email or password" is displayed

---

### TC-AUTH-003: Account Lockout After 5 Failed Attempts
**Use Case**: UC001  
**Preconditions**: User account exists  
**Test Steps**:
1. Attempt login with incorrect password 5 times
2. Attempt login with correct password

**Expected Result**: 
- After 5th failed attempt, account is locked
- Error message indicates account lockout
- Successful login is prevented even with correct credentials

---

### TC-AUTH-004: Password Reset via Email
**Use Case**: UC001  
**Preconditions**: User has registered email  
**Test Steps**:
1. Click "Forgot Password?" link
2. Enter registered email
3. Submit request
4. Check email for reset link
5. Click reset link
6. Enter new password
7. Confirm password change

**Expected Result**:
- Reset email is sent
- Reset link is valid and functional
- Password is updated successfully
- User is redirected to login page

---

### TC-AUTH-005: First-Time Login Password Change
**Use Case**: UC001  
**Preconditions**: User has temporary password  
**Test Steps**:
1. Login with temporary password
2. System auto-redirects to change password page
3. Enter new password meeting security requirements
4. Submit password change

**Expected Result**:
- User is forced to change password
- New password meets security policy
- User is redirected to dashboard after change

---

### TC-AUTH-006: Session Timeout
**Use Case**: UC001, UC012  
**Preconditions**: User is logged in  
**Test Steps**:
1. Login to system
2. Remain inactive for 30 minutes
3. Attempt to perform any action

**Expected Result**: User is redirected to login page with session expired message

---

### TC-AUTH-007: Successful Logout
**Use Case**: UC012  
**Preconditions**: User is logged in  
**Test Steps**:
1. Click on user avatar in navbar
2. Click Logout option

**Expected Result**: 
- Session is terminated
- User is redirected to login page

---

## 2. Booking Management Test Cases

### TC-BM-001: Create One-Time Booking Successfully
**Use Case**: UC002  
**Preconditions**: User is logged in, room is available  
**Test Steps**:
1. Navigate to Book Meeting Room page
2. Select meeting room
3. Select date and time
4. Enter booking purpose
5. Click "Confirm Booking"

**Expected Result**:
- Booking is created with unique reference number
- Confirmation email is sent
- Success message is displayed
- Booking appears in My Bookings

---

### TC-BM-002: Booking Conflict Detection
**Use Case**: UC002  
**Preconditions**: Room is already booked for selected time  
**Test Steps**:
1. Navigate to Book Meeting Room page
2. Select meeting room
3. Select date and time that conflicts with existing booking
4. Enter booking purpose
5. Click "Confirm Booking"

**Expected Result**:
- Error message "This time slot is already booked" is displayed
- Existing booking details are shown
- Booking is not created

---

### TC-BM-003: Create Recurring Booking (Daily)
**Use Case**: UC002  
**Preconditions**: User is logged in  
**Test Steps**:
1. Navigate to Recurring Booking page
2. Select meeting room
3. Enter start date, start/end time
4. Select "Daily" recurrence pattern
5. Set end date
6. Enter purpose
7. Review booking preview
8. Click "Create Recurring Booking"

**Expected Result**:
- System generates preview of all occurrences
- All occurrences are validated for availability
- Individual bookings are created for each occurrence
- Confirmation email is sent

---

### TC-BM-004: Recurring Booking with Conflicts
**Use Case**: UC002  
**Preconditions**: Some dates in recurrence have conflicts  
**Test Steps**:
1. Navigate to Recurring Booking page
2. Configure recurring booking with some conflicting dates
3. Review booking preview

**Expected Result**:
- Conflicting dates are highlighted
- "Create Recurring Booking" button is disabled
- Error message indicates conflicting dates and times

---

### TC-BM-005: Book on Behalf of Another User (Admin)
**Use Case**: UC002  
**Preconditions**: User is Admin or Director  
**Test Steps**:
1. Navigate to Book Meeting Room page
2. Enter booking details
3. Select another user from PIC dropdown
4. Click "Confirm Booking"

**Expected Result**:
- Booking is created with selected user as PIC
- Confirmation email is sent to PIC
- Audit log shows admin as creator and selected user as PIC

---

### TC-BM-006: Calendar View Booking Creation
**Use Case**: UC002  
**Preconditions**: User is logged in  
**Test Steps**:
1. Navigate to calendar view
2. Click on available time slot
3. Verify date and time are pre-filled
4. Complete booking form

**Expected Result**:
- Booking form opens with pre-populated date and time
- Booking is created successfully

---

### TC-BM-007: Cancel Single Booking
**Use Case**: UC003  
**Preconditions**: User has confirmed booking  
**Test Steps**:
1. Navigate to My Bookings
2. Select booking with Confirmed status
3. Click "Cancel Booking"
4. Enter cancellation reason
5. Click "Confirm Cancellation"

**Expected Result**:
- Booking status changes to Cancelled
- Room time slot is released
- Cancellation email is sent
- Action is logged in audit trail

---

### TC-BM-008: Cancel Single Occurrence from Recurring Series
**Use Case**: UC003  
**Preconditions**: User has recurring booking  
**Test Steps**:
1. Navigate to My Bookings
2. Select recurring booking
3. Click "Cancel Booking"
4. Select "Cancel This Date Only"
5. Enter cancellation reason
6. Confirm cancellation

**Expected Result**:
- Only selected occurrence is cancelled
- Other bookings in series remain Confirmed
- Cancellation is logged

---

### TC-BM-009: Cancel All Remaining Bookings in Series
**Use Case**: UC003  
**Preconditions**: User has recurring booking  
**Test Steps**:
1. Navigate to My Bookings
2. Select recurring booking
3. Click "Cancel Booking"
4. Select "Cancel All Remaining Bookings"
5. Enter cancellation reason
6. Confirm cancellation

**Expected Result**:
- All future bookings with Confirmed status are cancelled
- Past completed bookings are preserved
- Cancellation is logged

---

### TC-BM-010: Admin View All Bookings
**Use Case**: UC004  
**Preconditions**: User is Admin or Director  
**Test Steps**:
1. Navigate to All Bookings page
2. Verify all system bookings are displayed

**Expected Result**: All bookings from all users are visible

---

### TC-BM-011: Admin Filter Bookings
**Use Case**: UC004  
**Preconditions**: User is Admin or Director  
**Test Steps**:
1. Navigate to All Bookings page
2. Apply filters (User, Room, Date Range, Status)
3. View filtered results

**Expected Result**: Only bookings matching filter criteria are displayed

---

### TC-BM-012: Admin Update Booking
**Use Case**: UC004  
**Preconditions**: User is Admin or Director  
**Test Steps**:
1. Navigate to All Bookings
2. Select booking
3. Click "Update Booking"
4. Modify booking details
5. Submit changes

**Expected Result**:
- Booking is updated
- Notification is sent to affected user
- Update is logged in audit trail

---

### TC-BM-013: Admin Cancel Any Booking
**Use Case**: UC004  
**Preconditions**: User is Admin or Director  
**Test Steps**:
1. Navigate to All Bookings
2. Select any booking
3. Click "Cancel Booking"
4. Enter cancellation reason
5. Confirm cancellation

**Expected Result**:
- Booking is cancelled
- Notification is sent to booking owner
- Cancellation is logged

---

## 3. Room Management Test Cases

### TC-RM-001: Regular User Browse Active Rooms Only
**Use Case**: UC005  
**Preconditions**: User is Regular User  
**Test Steps**:
1. Navigate to meeting rooms list
2. View displayed rooms

**Expected Result**: Only rooms with Active status are displayed

---

### TC-RM-002: Admin Browse All Rooms
**Use Case**: UC005  
**Preconditions**: User is Admin or Director  
**Test Steps**:
1. Navigate to meeting rooms list
2. View displayed rooms

**Expected Result**: All rooms (Active, Inactive, Under Maintenance) are displayed

---

### TC-RM-003: View Room Details and Availability
**Use Case**: UC005  
**Preconditions**: None  
**Test Steps**:
1. Select a meeting room
2. View room details page

**Expected Result**:
- Room amenities, capacity, floor, images, description are displayed
- Availability calendar shows all bookings
- Bookings are color-coded by status

---

### TC-RM-004: Create New Meeting Room
**Use Case**: UC005  
**Preconditions**: User is Admin or Director  
**Test Steps**:
1. Navigate to Manage Meeting Rooms
2. Click "Create New Room"
3. Enter room details (name, capacity, floor, amenities, images, description)
4. Submit form

**Expected Result**:
- Room is created
- Success message is displayed
- Action is logged in audit trail

---

### TC-RM-005: Update Room Details
**Use Case**: UC005  
**Preconditions**: User is Admin or Director  
**Test Steps**:
1. Select meeting room
2. Click "Update Room Details"
3. Modify room information
4. Submit changes

**Expected Result**:
- Room details are updated
- Success message is displayed
- Update is logged

---

### TC-RM-006: Change Room Status to Under Maintenance
**Use Case**: UC005  
**Preconditions**: User is Admin or Director  
**Test Steps**:
1. Select meeting room
2. Click "Change Room Status"
3. Select "Under Maintenance"
4. Enter maintenance start date, end date, reason
5. Confirm status change

**Expected Result**:
- Room status changes to Under Maintenance
- Room is unavailable for booking during maintenance period
- Status change is logged

---

### TC-RM-007: Auto-Revert Room Status After Maintenance
**Use Case**: UC005  
**Preconditions**: Room is Under Maintenance with end date passed  
**Test Steps**:
1. Wait for maintenance end date to pass
2. Check room status

**Expected Result**: Room status automatically changes to Active

---

### TC-RM-008: Schedule Room Maintenance
**Use Case**: UC005  
**Preconditions**: User is Admin or Director  
**Test Steps**:
1. Select meeting room
2. Click "Schedule Maintenance"
3. Enter start date, end date, reason
4. Submit maintenance schedule

**Expected Result**:
- Maintenance is scheduled
- Room availability calendar reflects maintenance period
- Action is logged

---

### TC-RM-009: Delete Meeting Room
**Use Case**: UC005  
**Preconditions**: User is Admin or Director  
**Test Steps**:
1. Select meeting room
2. Click "Delete Room"
3. Confirm deletion

**Expected Result**:
- Room is soft deleted (if has booking history)
- Deletion is logged
- Success message is displayed

---

## 4. Amenity Management Test Cases

### TC-AM-001: Create New Amenity
**Use Case**: UC006  
**Preconditions**: User is Admin or Director  
**Test Steps**:
1. Navigate to Manage Amenities
2. Click "Create New Amenity"
3. Enter name, icon, description
4. Submit form

**Expected Result**:
- Amenity is created with Active status
- Success message is displayed

---

### TC-AM-002: View Amenity Details
**Use Case**: UC006  
**Preconditions**: Amenity exists  
**Test Steps**:
1. Select amenity from list
2. Click "View Details"

**Expected Result**:
- Amenity name, icon, description, status are displayed
- List of rooms using amenity is shown

---

### TC-AM-003: Edit Amenity
**Use Case**: UC006  
**Preconditions**: User is Admin or Director  
**Test Steps**:
1. Select amenity
2. Click "Edit Amenity"
3. Update information
4. Submit changes

**Expected Result**:
- Amenity is updated
- Success message is displayed

---

### TC-AM-004: Deactivate Amenity
**Use Case**: UC006  
**Preconditions**: User is Admin or Director  
**Test Steps**:
1. Select active amenity
2. Click "Deactivate"
3. Confirm action

**Expected Result**:
- Amenity status changes to Inactive
- Existing room associations are preserved
- Amenity is unavailable for new room assignments

---

### TC-AM-005: Delete Amenity Not in Use
**Use Case**: UC006  
**Preconditions**: Amenity is not assigned to any rooms  
**Test Steps**:
1. Select amenity
2. Click "Delete"
3. Confirm deletion

**Expected Result**:
- Amenity is deleted
- Deletion is logged

---

### TC-AM-006: Prevent Delete Amenity in Use
**Use Case**: UC006  
**Preconditions**: Amenity is assigned to rooms  
**Test Steps**:
1. Select amenity in use
2. View delete button state

**Expected Result**: Delete button is disabled

---

## 5. User Management Test Cases

### TC-UM-001: Create User with Auto-Generated Password
**Use Case**: UC007  
**Preconditions**: User is System Admin or Director  
**Test Steps**:
1. Navigate to Manage Users
2. Click "Create New User"
3. Enter user details
4. Select role
5. Leave password field blank (auto-generate)
6. Submit form

**Expected Result**:
- User account is created
- Temporary password "abc123" is generated
- Welcome email is sent with credentials
- Action is logged

---

### TC-UM-002: Create User with Manual Password
**Use Case**: UC007  
**Preconditions**: User is System Admin or Director  
**Test Steps**:
1. Navigate to Manage Users
2. Click "Create New User"
3. Enter user details
4. Select role
5. Enter temporary password manually
6. Submit form

**Expected Result**:
- User account is created
- Manual password is set
- Welcome email is sent
- Action is logged

---

### TC-UM-003: Edit User Details
**Use Case**: UC007  
**Preconditions**: User is System Admin or Director  
**Test Steps**:
1. Select user
2. Click "Edit User"
3. Update allowed fields
4. Submit changes

**Expected Result**:
- User details are updated
- Update is logged

---

### TC-UM-004: Deactivate User Account
**Use Case**: UC007  
**Preconditions**: User is System Admin or Director  
**Test Steps**:
1. Select active user
2. Click "Deactivate User"
3. Confirm action

**Expected Result**:
- User status changes to Inactive
- User cannot login
- Status change is logged

---

### TC-UM-005: Reset Password with Temporary Password
**Use Case**: UC007  
**Preconditions**: User is System Admin or Director  
**Test Steps**:
1. Select user
2. Click "Reset Password"
3. Select "Generate temporary password"
4. Confirm action

**Expected Result**:
- Temporary password "abc123" is set
- User is forced to change password on next login
- Action is logged

---

### TC-UM-006: Reset Password via Email Link
**Use Case**: UC007  
**Preconditions**: User is System Admin or Director  
**Test Steps**:
1. Select user
2. Click "Reset Password"
3. Select "Send password reset link via email"
4. Confirm action

**Expected Result**:
- Password reset email is sent to user
- Action is logged

---

### TC-UM-007: View User Activity History
**Use Case**: UC007  
**Preconditions**: User is System Admin or Director  
**Test Steps**:
1. Select user
2. Click "View Activity History"
3. Apply filters (date range, event type)
4. View results

**Expected Result**: User's activity log is displayed with applied filters

---

### TC-UM-008: Export User Activity
**Use Case**: UC007  
**Preconditions**: User is System Admin or Director  
**Test Steps**:
1. View user activity history
2. Select export format (CSV or Excel)
3. Click export

**Expected Result**: Activity log is downloaded in selected format

---

### TC-UM-009: Delete User Account
**Use Case**: UC007  
**Preconditions**: User is System Admin or Director  
**Test Steps**:
1. Select user
2. Click "Delete User"
3. Confirm deletion

**Expected Result**:
- User account is deleted
- Deletion is logged

---

## 6. System Configuration Test Cases

### TC-SC-001: Update Session Timeout
**Use Case**: UC008  
**Preconditions**: User is System Admin  
**Test Steps**:
1. Navigate to System Settings
2. Select "Session & Security" tab
3. Update Session Timeout value (15-120 minutes)
4. Click "Save Changes"

**Expected Result**:
- Setting is updated
- Success message is displayed
- New timeout applies to all sessions

---

### TC-SC-002: Invalid Session Timeout Value
**Use Case**: UC008  
**Preconditions**: User is System Admin  
**Test Steps**:
1. Navigate to System Settings
2. Enter Session Timeout value outside valid range (e.g., 5 minutes)
3. Click "Save Changes"

**Expected Result**:
- Error message displays valid range (15-120 min)
- Settings are not saved

---

### TC-SC-003: Enable Maintenance Mode
**Use Case**: UC008  
**Preconditions**: User is System Admin  
**Test Steps**:
1. Navigate to System Settings
2. Check "Enable Maintenance Mode"
3. Confirm action

**Expected Result**:
- Regular users are logged out
- Public sees "System Under Maintenance" page
- Only Admin and System Admin can login

---

### TC-SC-004: Configure Email Notifications
**Use Case**: UC008  
**Preconditions**: User is System Admin  
**Test Steps**:
1. Navigate to System Settings
2. Select "Notification Settings" tab
3. Toggle Master Email Enable
4. Select/deselect specific notification triggers
5. Click "Save Changes"

**Expected Result**:
- Settings are updated
- Email notifications are sent based on configuration

---

## 7. Audit Trail Test Cases

### TC-AT-001: View Audit Trail
**Use Case**: UC009  
**Preconditions**: User is System Admin  
**Test Steps**:
1. Navigate to Manage Audit Trail
2. View audit records

**Expected Result**: All audit trail records are displayed with date/time, user, role, module, action

---

### TC-AT-002: Filter Audit Trail by Date Range
**Use Case**: UC009  
**Preconditions**: User is System Admin  
**Test Steps**:
1. Navigate to Manage Audit Trail
2. Select date range filter
3. Apply filter

**Expected Result**: Only records within selected date range are displayed

---

### TC-AT-003: Filter Audit Trail by User
**Use Case**: UC009  
**Preconditions**: User is System Admin  
**Test Steps**:
1. Navigate to Manage Audit Trail
2. Select user filter
3. Apply filter

**Expected Result**: Only records for selected user are displayed

---

### TC-AT-004: Export Audit Trail to CSV
**Use Case**: UC009  
**Preconditions**: User is System Admin  
**Test Steps**:
1. Navigate to Manage Audit Trail
2. Apply desired filters
3. Select "Export to CSV"
4. Click export

**Expected Result**: Audit trail is downloaded in CSV format

---

### TC-AT-005: Export Audit Trail to Excel
**Use Case**: UC009  
**Preconditions**: User is System Admin  
**Test Steps**:
1. Navigate to Manage Audit Trail
2. Apply desired filters
3. Select "Export to Excel"
4. Click export

**Expected Result**: Audit trail is downloaded in Excel format

---

## 8. Reporting Test Cases

### TC-RP-001: Generate Room Utilization Report
**Use Case**: UC010  
**Preconditions**: User is System Admin, Admin, or Director  
**Test Steps**:
1. Navigate to Reports module
2. Select "Room Utilization"
3. Select date range
4. Click "Update Report"

**Expected Result**:
- Report displays booking counts, hours used, utilization rate per room
- Data matches selected date range

---

### TC-RP-002: Generate Booking Statistics Report
**Use Case**: UC010  
**Preconditions**: User is System Admin, Admin, or Director  
**Test Steps**:
1. Navigate to Reports module
2. Select "Booking Statistics"
3. Select date range
4. Click "Update Report"

**Expected Result**:
- Report displays trends, status distribution, peak hours, top rooms
- Data matches selected date range

---

### TC-RP-003: Generate User Activity Report
**Use Case**: UC010  
**Preconditions**: User is System Admin, Admin, or Director  
**Test Steps**:
1. Navigate to Reports module
2. Select "User Activity"
3. Select date range
4. Click "Update Report"

**Expected Result**:
- Report displays user booking activity, cancellation rates, department breakdown
- Data matches selected date range

---

### TC-RP-004: Export Report to PDF
**Use Case**: UC010  
**Preconditions**: Report is generated  
**Test Steps**:
1. Generate any report
2. Select "Export to PDF"
3. Click export

**Expected Result**: Report is downloaded in PDF format

---

### TC-RP-005: Export Report to Excel
**Use Case**: UC010  
**Preconditions**: Report is generated  
**Test Steps**:
1. Generate any report
2. Select "Export to Excel"
3. Click export

**Expected Result**: Report is downloaded in Excel format

---

### TC-RP-006: No Data Found for Selected Criteria
**Use Case**: UC010  
**Preconditions**: User is System Admin, Admin, or Director  
**Test Steps**:
1. Navigate to Reports module
2. Select date range with no data
3. Click "Update Report"

**Expected Result**: "No data available for the selected criteria" message is displayed

---

## 9. Profile Management Test Cases

### TC-PM-001: View Profile
**Use Case**: UC011  
**Preconditions**: User is logged in  
**Test Steps**:
1. Navigate to My Profile
2. View profile information

**Expected Result**: User's name, email, staff number, department, phone, role are displayed

---

### TC-PM-002: Edit Profile
**Use Case**: UC011  
**Preconditions**: User is logged in  
**Test Steps**:
1. Navigate to My Profile
2. Click "Edit Profile"
3. Update name, department, or phone number
4. Click "Save Changes"

**Expected Result**:
- Profile is updated
- Success message is displayed

---

### TC-PM-003: Change Password Successfully
**Use Case**: UC011  
**Preconditions**: User is logged in  
**Test Steps**:
1. Navigate to My Profile
2. Click "Change Password"
3. Enter current password
4. Enter new password meeting security requirements
5. Confirm new password
6. Click "Change Password"

**Expected Result**:
- Password is updated
- Success message is displayed

---

### TC-PM-004: Change Password with Incorrect Current Password
**Use Case**: UC011  
**Preconditions**: User is logged in  
**Test Steps**:
1. Navigate to My Profile
2. Click "Change Password"
3. Enter incorrect current password
4. Enter new password
5. Click "Change Password"

**Expected Result**: Error message "The password is incorrect" is displayed

---

### TC-PM-005: Change Password Not Meeting Security Requirements
**Use Case**: UC011  
**Preconditions**: User is logged in  
**Test Steps**:
1. Navigate to My Profile
2. Click "Change Password"
3. Enter current password
4. Enter new password not meeting security policy
5. Click "Change Password"

**Expected Result**:
- Error message is displayed
- Password security checklist highlights failed requirements

---

## 10. System Exception Test Cases

### TC-EX-001: Server Unavailability
**Use Case**: All  
**Preconditions**: System is down for maintenance  
**Test Steps**:
1. Attempt to access system

**Expected Result**: "System temporarily unavailable" message is displayed with suggestion to try again later

---

### TC-EX-002: Session Expired During Action
**Use Case**: All  
**Preconditions**: User session has expired  
**Test Steps**:
1. Login to system
2. Wait for session to expire
3. Attempt to perform any action

**Expected Result**: User is redirected to login page with session expired message

---

## Test Coverage Summary

| Module | Total Test Cases | Priority |
|--------|-----------------|----------|
| Authentication & Authorization | 7 | High |
| Booking Management | 13 | High |
| Room Management | 9 | High |
| Amenity Management | 6 | Medium |
| User Management | 9 | High |
| System Configuration | 4 | Medium |
| Audit Trail | 5 | Medium |
| Reporting | 6 | Medium |
| Profile Management | 5 | Low |
| System Exceptions | 2 | High |
| **Total** | **66** | - |
