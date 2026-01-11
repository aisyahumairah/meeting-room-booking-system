# System Component Diagram

This diagram illustrates the high-level components of the Meeting Room Booking System (MRBS) and their interactions, following a standard MVC (Model-View-Controller) architecture enriched with a Service layer.

```mermaid
componentDiagram
    package "Client Layer" {
        [Web Browser] as Browser
    }

    package "Presentation Layer (Blade Views)" {
        component "Admin Views" as AdminViews
        component "Booking Views" as BookingViews
        component "Dashboard Views" as DashboardViews
        component "Room Views" as RoomViews
        component "Auth Views" as AuthViews
        component "Profile Views" as ProfileViews
    }

    package "Controller Layer" {
        component "Admin Controllers" as AdminControllers {
            [BookingController (Admin)]
            [RoomController (Admin)]
            [UserController]
            [ReportController]
        }
        
        component "User Controllers" as UserControllers {
            [BookingController (User)]
            [RoomController (User)]
            [DashboardController]
            [ProfileController]
        }

        component "Auth Controllers" as AuthControllers {
            [LoginController]
            [ResetPasswordController]
        }
    }

    package "Service Layer" {
        component "Booking Services" as BookingServices {
            [BookingService]
            [BookingStatusService]
            [RoomMaintenanceService]
        }

        component "Support Services" as SupportServices {
            [AuditService]
            [NotificationService]
            [RoomImageService]
        }
    }

    package "Data Access Layer (Models)" {
        component "Core Models" as CoreModels {
            [Booking]
            [Room]
            [User]
            [BookingSeries]
        }

        component "Support Models" as SupportModels {
            [AuditLog]
            [NotificationLog]
            [RoomImage]
            [Amenity]
        }
    }

    package "Infrastructure" {
        database "MySQL Database" as DB
    }

    %% Relationships
    
    %% Client to Views
    Browser ..> AdminViews : Requests/HTML
    Browser ..> BookingViews : Requests/HTML
    Browser ..> AuthViews : Requests/HTML

    %% Views to Controllers (Conceptually, Routing maps browser requests to controllers, which return views)
    AdminViews <.. AdminControllers : Returns
    BookingViews <.. UserControllers : Returns
    AuthViews <.. AuthControllers : Returns
    DashboardViews <.. UserControllers : Returns
    RoomViews <.. UserControllers : Returns
    RoomViews <.. AdminControllers : Returns

    %% Controllers to Services
    AdminControllers --> BookingServices : Uses
    AdminControllers --> SupportServices : Uses
    UserControllers --> BookingServices : Uses
    UserControllers --> SupportServices : Uses
    AuthControllers --> SupportServices : Uses

    %% Services to Models
    BookingServices --> CoreModels : Manages
    SupportServices --> SupportModels : Manages
    SupportServices --> CoreModels : Reads

    %% Direct Model Access (Simple CRUD)
    AdminControllers --> CoreModels : Direct CRUD (Simple)
    UserControllers --> CoreModels : Direct CRUD (Simple)

    %% Models to Database
    CoreModels --> DB : Eloquent ORM
    SupportModels --> DB : Eloquent ORM

```

## Component Descriptions

### Presentation Layer
*   **Admin Views**: Interfaces for system management (users, rooms, settings, reports).
*   **Booking Views**: Interfaces for booking creation, cancellation, and calendar visualization.
*   **Dashboard Views**: Landing pages displaying summary statistics and quick actions.

### Controller Layer
*   **Admin Controllers**: Handle administrative actions, enforcing permissions and orchestrating management tasks.
*   **User Controllers**: Handle end-user actions like searching rooms, making bookings, and managing personal profiles.
*   **Auth Controllers**: Manage authentication flows (Login, Logout, Password Reset).

### Service Layer
*   **BookingService**: Encapsulates complex booking logic (conflict checking, recurrence calculation, cancellation rules).
*   **BookingStatusService**: Manages booking lifecycle states (e.g., auto-completing expired bookings).
*   **AuditService**: Centralized logging of critical system actions for security and accountability.
*   **NotificationService**: Handles email distributions and user notification preferences.
*   **RoomMaintenanceService**: Manages room availability logic regarding maintenance schedules.

### Data Access Layer
*   **Core Models**: Represents the primary business entities (`Booking`, `Room`, `User`).
*   **Support Models**: Represents auxiliary data (`AuditLog`, `SystemSetting`).
