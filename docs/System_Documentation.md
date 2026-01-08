# System Documentation

## 2. Specific Requirements

### External Interface Requirements

#### User Interfaces
The user interface for the Meeting Room Booking System (MRBS) is a web-based responsive application designed to be accessible via standard web browsers on desktops, tablets, and mobile devices.

**(a) Logical Characteristics:**
The interface follows a role-based structure, providing specific dashboards and navigation menus for Regular Users, Administrators, Directors, and System Administrators.
- **Layout**: A consistent global layout featuring a collapsible sidebar for navigation, a top navigation bar for user profile access and notifications, and a main content area.
- **Dashboards**:
    - **Regular User Dashboard**: Displays upcoming and past bookings, a "Quick Book" button, and personal usage statistics.
    - **Admin/Director Dashboard**: Features widgets for today's bookings, weekly/monthly statistics, room utilization charts, and quick actions for room and booking management.
- **Forms**: Input forms (e.g., Booking Creation, Room Management) use standard HTML5 controls with clear labels and validation feedback.
- **Calendar**: A centralized calendar view supports Day, Week, and Month modes, with color-coded blocks indicating booking status (Confirmed, Cancelled, Completed, Maintenance).

**(b) Optimization and Usability:**
The system ensures a user-friendly experience through the following do's and don'ts practices:
- **Do**: Provide clear, concise success and error messages (e.g., "Booking confirmed successfully" vs "Error 500").
- **Do**: Use visual indicators (badges, icons, colors) to denote status (Green for Active/Confirmed, Red for Inactive/Cancelled).
- **Do**: Ensure navigation depth is minimal, adhering to the "3-click rule" to reach any primary feature.
- **Do**: Provide immediate feedback for user actions, such as loading indicators for asynchronous operations.
- **Don't**: Display raw database errors or stack traces to end-users.
- **Don't**: Overwhelm users with irrelevant administrative options; strictly enforce role-based visibility.

#### Hardware Interfaces
The system interacts with standard server and client hardware.

**Server Side:**
- **Processor**: Interaction optimized for multi-core processors (e.g., Intel Xeon or AMD EPYC) to handle concurrent PHP requests.
- **Memory**: Optimized for systems with 8GB RAM minimum, utilizing shared buffers for PostgreSQL.
- **Network**: Requires a standard 1Gbps Network Interface Card (NIC) for handling HTTP requests.

**Client Side:**
- **Devices**: Supports interaction with Standard PC workstations (Intel Core/AMD Ryzen), Tablets, and Smartphones.
- **Display**: Responsive interface adapts to resolutions from 1366x768 (Desktop) down to 375px (Mobile).
- **Input**: Supports standard keyboard/mouse input and touch events for mobile devices.

#### Software Interfaces
The application integrates with the following software products and systems:

**Required Software Products:**
1.  **Operating System**
    *   **Name**: Linux (Ubuntu Server)
    *   **Version**: 20.04 LTS or higher
    *   **Source**: Open Source (Canonical)
2.  **Web Server**
    *   **Name**: Nginx
    *   **Version**: 1.18 or higher
    *   **Source**: Open Source (Nginx, Inc.)
    *   **Purpose**: Handles incoming HTTP/HTTPS requests and serves static assets.
3.  **Database System**
    *   **Name**: PostgreSQL
    *   **Version**: 14 or higher
    *   **Source**: Open Source (The PostgreSQL Global Development Group)
    *   **Purpose**: specific functional requirement: Stores all persistent data including user records, room inventory, booking details, and audit logs.
4.  **Framework**
    *   **Name**: Laravel
    *   **Version**: 10.x / 11.x
    *   **Source**: Open Source (Taylor Otwell)
    *   **Purpose**: Provides the core application structure, MVC architecture, routing, and ORM.

**Interfaces:**
- **Email Service (SMTP)**:
    - **Purpose**: To send transactional emails such as booking confirmations, password resets, and notifications.
    - **Interface Definition**: Standard SMTP protocol messages via TCP ports 25, 465, or 587. Content includes HTML/Text email bodies.
- **Browser Runtime**:
    - **Purpose**: To render the client-side application.
    - **Interface Definition**: HTML5, CSS3, and ECMAScript 2020+ standards compliance.

#### Communication Interfaces
The system utilizes standard network protocols for communication:
-   **HTTP/HTTPS**:
    *   **Protocol**: TCP/IP (Ports 80 and 443).
    *   **Purpose**: Primary communication channel between the client browser and the web server. All data exchange is encrypted via TLS (HTTPS) in production.
-   **SMTP**:
    *   **Protocol**: TCP/IP (Ports 25, 465, or 587).
    *   **Purpose**: Outbound communication to an external Mail Transfer Agent (MTA) for delivering email notifications.

### Performance and Other Requirements

**Performance Requirements:**
-   **Response Time**: The system shall load dashboards within 2 seconds and complete booking creation transactions within 3 seconds under normal network conditions.
-   **Throughput**: The system shall support concurrent usage by up to 100 simultaneous users without performance degradation.
-   **Search Speed**: Room search results shall be returned within 1 second for queries against the room inventory (Related to Functional Requirement: Room Discovery).

**Non-Functional Requirements:**
-   **Reliability**: The system shall maintain 99.5% uptime during business hours (8:00 AM - 6:00 PM).
-   **Maintainability**: Codebase must adhere to PSR standards and Laravel best practices to facilitate future updates.
-   **Security**: All user passwords must be hashed using Bcrypt. Session timeouts must occur after 30 minutes of inactivity.
-   **Availability**: Database backups shall be performed daily.

### Design Constraints
The system design and implementation are constrained by the following organizational and technical standards:
-   **Framework Standard**: The solution must be built using the **Laravel PHP Framework** to ensure consistency with the organization's current technology stack.
-   **Architecture Pattern**: The system must strictly follow the **Model-View-Controller (MVC)** architectural pattern.
-   **Database**: The persistence layer is restricted to **PostgreSQL**; no NoSQL or flat-file storage solutions are to be used for core data.
-   **Operating Schedule**: System logic must enforce business rules strictly within the 8:00 AM to 6:00 PM operating window.
-   **Compliance**: The system must include an immutable audit trail to comply with internal governance and anti-sabotage policies.

### Software System Attributes
-   **Usability**: The system must be attractive and easy to learn (user-friendly). A new user should be able to make a booking with less than 5 minutes of familiarization.
-   **Aesthetics**: The design should utilize a modern, clean, and professional "Sneat Admin" style theme suitable for a corporate environment.
-   **Security**: The system must protect against unauthorized access and ensure data integrity to build trust with users (preventing "sabotage").

## System Architectural Design and Components

### Architecture Style and Rationale
The application utilizes the **Model-View-Controller (MVC)** architectural style, which is inherent to the chosen Laravel framework.

**Rationale**:
-   **Separation of Concerns**: MVC separates the application data (Models), user interface (Views), and control logic (Controllers). This makes the application easier to maintain and test.
-   **Rapid Development**: Laravel's MVC implementation provides built-in tools for routing, authentication, and database interaction, accelerating the development of the required moudles (Booking, Room Management).
-   **Scalability**: The modular nature of MVC allows for independent scaling and updating of components (e.g., updating views without affecting business logic).

### Component Model
The system is partitioned into three major subsystems that collaborate to deliver the full functionality.

**1. Meeting Rooms Management Subsystem**:
-   **Responsibilities**: Manages the inventory of meeting rooms, amenities, and maintenance schedules.
-   **Components**: Room Model, Amenity Model, RoomController (Admin).
-   **Collaboration**: Provides room availability data to the Booking Management subsystem.

**2. Booking Management Subsystem**:
-   **Responsibilities**: Handles booking creation, validation, cancellation, and recurring series management.
-   **Components**: Booking Model, Series Model, BookingController.
-   **Collaboration**: Consumes room status from Room Management and user data from Administrative Management to validate and store reservations.

**3. Administrative Management Subsystem**:
-   **Responsibilities**: Manages user accounts, roles, system configuration, audit logs, and reporting.
-   **Components**: User Model, AuditLog Model, UserController.
-   **Collaboration**: Enforces authentication and authorization rules used by all other subsystems.

**Subsystem Interconnection Diagram:**
The following diagram illustrates the high-level relationships between these subsystems and the central data repository.

```mermaid
classDiagram
    package "Meeting Rooms Management" {
        class Room
        class Amenity
        class RoomMaintenanceSchedule
    }

    package "Booking Management" {
        class Booking
        class BookingSeries
    }

    package "Administrative Management" {
        class User
        class AuditLog
        class Report
    }

    %% Relationships
    Room "1" -- "*" Booking : booked_in
    User "1" -- "*" Booking : makes
    User "1" -- "*" AuditLog : performs_action
    Room "1" -- "*" Amenity : has
    Booking "1" -- "0..1" BookingSeries : part_of
    
    note for Booking "Dependent on Room Availability\nand User Authorization"
```
