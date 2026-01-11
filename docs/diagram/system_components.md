# System Components List

## Models
| Model Name | File Path | Description |
| :--- | :--- | :--- |
| `Amenity` | `app/Models/Amenity.php` | Represents room amenities (e.g., Projector, Whiteboard). |
| `AuditLog` | `app/Models/AuditLog.php` | Records system activities for auditing purposes. |
| `Booking` | `app/Models/Booking.php` | Represents a single booking. |
| `BookingSeries` | `app/Models/BookingSeries.php` | Represents a recurring booking series. |
| `NotificationLog` | `app/Models/NotificationLog.php` | Logs notifications sent to users. |
| `Room` | `app/Models/Room.php` | Represents a meeting room. |
| `RoomImage` | `app/Models/RoomImage.php` | Stores images associated with rooms. |
| `RoomMaintenanceSchedule` | `app/Models/RoomMaintenanceSchedule.php` | Schedules maintenance for rooms. |
| `SystemSetting` | `app/Models/SystemSetting.php` | Stores global system configuration. |
| `User` | `app/Models/User.php` | Represents a system user (Admin/Regular). |
| `UserNotificationPreference` | `app/Models/UserNotificationPreference.php` | Stores user preferences for notifications. |

## Controllers
### General (`app/Http/Controllers`)
| Controller Name | File Path | Description |
| :--- | :--- | :--- |
| `BookingController` | `app/Http/Controllers/BookingController.php` | Handles general booking operations (create, view, cancel). |
| `CalendarController` | `app/Http/Controllers/CalendarController.php` | Manages calendar views. |
| `DashboardController` | `app/Http/Controllers/DashboardController.php` | Handles the dashboard display for users and admins. |
| `ProfileController` | `app/Http/Controllers/ProfileController.php` | Manages user profile updates. |
| `RoomController` | `app/Http/Controllers/RoomController.php` | Handles public room listing and details. |

### Admin (`app/Http/Controllers/Admin`)
| Controller Name | File Path | Description |
| :--- | :--- | :--- |
| `AmenityController` | `app/Http/Controllers/Admin/AmenityController.php` | Admin management of amenities. |
| `AuditLogController` | `app/Http/Controllers/Admin/AuditLogController.php` | Admin view for audit logs. |
| `BookingController` | `app/Http/Controllers/Admin/BookingController.php` | Admin management of all bookings. |
| `ReportController` | `app/Http/Controllers/Admin/ReportController.php` | Generates system reports. |
| `RoomController` | `app/Http/Controllers/Admin/RoomController.php` | Admin management of rooms. |
| `SettingsController` | `app/Http/Controllers/Admin/SettingsController.php` | Manages system settings. |
| `UserController` | `app/Http/Controllers/Admin/UserController.php` | Admin management of users. |

### Auth (`app/Http/Controllers/Auth`)
| Controller Name | File Path | Description |
| :--- | :--- | :--- |
| `ChangePasswordController` | `app/Http/Controllers/Auth/ChangePasswordController.php` | Handles password changes. |
| `ForgotPasswordController` | `app/Http/Controllers/Auth/ForgotPasswordController.php` | Handles forgot password requests. |
| `LoginController` | `app/Http/Controllers/Auth/LoginController.php` | Handles user authentication. |
| `ResetPasswordController` | `app/Http/Controllers/Auth/ResetPasswordController.php` | Handles password resets. |

## Views (`resources/views`)
### Admin Views (`resources/views/admin`)

#### Amenities (`admin/amenities`)
| File | Description |
| :--- | :--- |
| `create.blade.php` | Form to add a new amenity. |
| `edit.blade.php` | Form to edit an existing amenity. |
| `index.blade.php` | List of all amenities. |
| `show.blade.php` | View details of a specific amenity. |

#### Audit Logs (`admin/audit-logs`)
| File | Description |
| :--- | :--- |
| `index.blade.php` | Viewer for system audit logs. |

#### Bookings (`admin/bookings`)
| File | Description |
| :--- | :--- |
| `edit.blade.php` | Admin form to edit an existing booking. |
| `index.blade.php` | Admin list of all bookings with filters. |
| `show.blade.php` | Admin view of detailed booking information. |
| `partials/cancel-modal.blade.php` | Modal for cancelling a single booking. |
| `partials/cancel-recurring-modal.blade.php` | Modal for processing recurring booking cancellations. |

#### Reports (`admin/reports`)
| File | Description |
| :--- | :--- |
| `index.blade.php` | Main reports dashboard. |
| `booking-statistics.blade.php` | View for booking statistics report. |
| `room-utilization.blade.php` | View for room utilization report. |
| `user-activity.blade.php` | View for user activity report. |
| `pdf/booking-statistics.blade.php` | PDF template for booking statistics. |
| `pdf/room-utilization.blade.php` | PDF template for room utilization. |
| `pdf/user-activity.blade.php` | PDF template for user activity. |

#### Rooms (`admin/rooms`)
| File | Description |
| :--- | :--- |
| `create.blade.php` | Form to add a new room. |
| `edit.blade.php` | Form to edit an existing room. |
| `index.blade.php` | List of all rooms for management. |

#### Settings (`admin/settings`)
| File | Description |
| :--- | :--- |
| `index.blade.php` | System configuration form. |

#### Users (`admin/users`)
| File | Description |
| :--- | :--- |
| `activity.blade.php` | View of specific user's activity history. |
| `create.blade.php` | Form to add a new user. |
| `edit.blade.php` | Form to edit an existing user. |
| `index.blade.php` | List of all users. |

### Booking Views
| View Folder/File | Description |
| :--- | :--- |
| `bookings/calendar.blade.php` | Booking calendar view. |
| `bookings/create.blade.php` | Form to create a one-time booking. |
| `bookings/create-recurring.blade.php` | Form to create a recurring booking. |
| `bookings/edit.blade.php` | Form to edit a booking. |
| `bookings/my.blade.php` | List of current user's bookings. |
| `bookings/show.blade.php` | Booking details view. |

### Dashboard Views
| View Folder/File | Description |
| :--- | :--- |
| `dashboard/admin.blade.php` | Admin dashboard. |
| `dashboard/user.blade.php` | Regular user dashboard. |

### Profile Views
| View Folder/File | Description |
| :--- | :--- |
| `profile/show.blade.php` | User profile overview. |
| `profile/edit.blade.php` | Edit profile form. |
| `profile/change-password.blade.php` | Change password form. |
| `profile/notifications.blade.php` | Notification preferences. |

### Room Views
| View Folder/File | Description |
| :--- | :--- |
| `rooms/index.blade.php` | Public room listing. |
| `rooms/show.blade.php` | Room details. |

