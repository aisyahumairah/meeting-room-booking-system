# User Acceptance Test Cases - Phase 2: Meeting Room Management

**Project:** Meeting Room Booking System (MRBS)
**Module:** Room Inventory & Amenities
**Document ID:** UAT-PH2
**Version:** 1.0

## 1. Introduction
This section verifies the functionality for managing the meeting room inventory, including room creation, updates, and user visibility.

## 2. Test Cases

| Test Case ID | Test Item | Preconditions | Input Data | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-PH2-001** | View Room List (User) | Registered User logged in. | 1. Navigate to `/rooms` | List of active rooms is displayed with Name, Capacity, and Location. | | |
| **TC-PH2-002** | Room Search | Multiple rooms exist. | 1. Navigate to `/rooms/search`<br>2. Enter "Conference" in Keyword<br>3. Click "Search" | Usage results filter to show only rooms with "Conference" in the name. | | |
| **TC-PH2-003** | Room Details View | Room "Boardroom" exists. | 1. Click "View Details" on Boardroom card | Page displays full room specs, equipment list, and "Book Now" button. | | |
| **TC-PH2-004** | Create Room (Admin) | Admin logged in. | 1. Navigate to `Admin > Rooms > Create`<br>2. Enter Name: "New Lab", Capacity: 10<br>3. Select Amenities: "Projector"<br>4. Click "Create" | Success message displayed. "New Lab" appears in the Room List. | | |
| **TC-PH2-005** | Create Room Validation | Admin logged in. | 1. Navigate to Create Room<br>2. Leave Name empty<br>3. Click "Create" | Error "The name field is required" is displayed. Room is not created. | | |
| **TC-PH2-006** | Update Room Details | Room "Lab A" exists. | 1. Navigate to `Admin > Rooms`<br>2. Click "Edit" on "Lab A"<br>3. Change Capacity to 20<br>4. Click "Update" | Success message displayed. Room capacity is now 20. | | |
| **TC-PH2-007** | Delete Room (Unused) | Room "Temp Room" exists (no bookings). | 1. Navigate to `Admin > Rooms`<br>2. Click "Delete" on "Temp Room"<br>3. Confirm Alert | Success message displayed. Room is removed from the list. | | |
| **TC-PH2-008** | Delete Room (Protected) | Room "Hall A" has active bookings. | 1. Navigate to `Admin > Rooms`<br>2. Click "Delete" on "Hall A" | Error message displayed: "Cannot delete room with active bookings". Room remains. | | |
| **TC-PH2-009** | Manage Amenities (Add) | Admin logged in. | 1. Navigate to `Admin > Rooms > Amenities`<br>2. Enter "Smart Board"<br>3. Click "Add Amenity" | "Smart Board" is added to the amenity list and available for new rooms. | | |
| **TC-PH2-010** | Manage Amenities (Delete) | Amenity "Old TV" exists. | 1. Click "Delete" on "Old TV" | Amenity is removed from the system. | | |
