# Meeting Room Booking System (MRBS)

Meeting Room Booking System (MRBS) for Oriental Interest Group. A comprehensive solution for managing meeting room reservations, user roles, and resource availability.

## Project Overview

This project is a web-based application built with **Laravel 12** designed to streamline the process of booking meeting rooms. It includes role-based access control (RBAC), real-time availability checking, and administrative management tools.

## Routes and Purpose

### Guest Access
- **Login** (`/login`): User authentication entry point.
- **Forgot Password** (`/forgot-password`): Password recovery flow.

### Authenticated Users
All authenticated users have access to:
- **Dashboard** (`/dashboard`): Central hub for user activities (redirects based on role).
- **Profile** (`/profile`): Manage personal information and password changes.
- **Room Browsing** (`/rooms`): View available meeting rooms and details.
- **Booking Creation** (`/bookings/create`): Interface to book a meeting room.

### Administrative Roles
- **Admin/Director** (`/admin/rooms`): Manage meeting rooms (Create, Read, Update, Delete) and room images.
- **Director/System Admin**: Access to User Management and Audit Logs (Implementation in progress).
- **System Admin**: Full system configuration and settings.

## Development Environment Setup

This project is optimized for a **Laravel Herd** and **DBngin** workflow on Windows.

1.  **Clone the repository** into your Herd directory (e.g., `d:\Herd\mrbs`).
2.  **Install dependencies**
    ```powershell
    composer install
    npm install
    ```
3.  **Environment Configuration**
    - Copy `.env.example` to `.env`:
      ```powershell
      copy .env.example .env
      ```
    - **Database (DBngin)**:
        - Open **DBngin** and start the PostgreSQL service (default port 5432).
        - Create a new database named `mrbs`.
        - Configure `.env`:
          ```ini
          DB_CONNECTION=pgsql
          DB_HOST=127.0.0.1
          DB_PORT=5432
          DB_DATABASE=mrbs
          DB_USERNAME=postgres  # Default DBngin user
          DB_PASSWORD=          # Default is often empty
          ```
4.  **Generate App Key**
    ```powershell
    php artisan key:generate
    ```
5.  **Run Migrations and Seeders**
    ```powershell
    php artisan migrate --seed
    ```
6.  **Run the Application**
    - **Backend**: Handled automatically by Herd at [http://mrbs.test](http://mrbs.test).
    - **Frontend Assets**: Run the Vite development server:
      ```powershell
      npm run dev
      ```

## Development Approach

This project uses an agentic workflow for development. Please follow these procedures when implementing features:

### 1. Generate Implementation Plan
Before starting a new phase, generate a detailed implementation plan using the workflow documentation.
- **Workflow**: `@[.agent/workflows/generate-phase-plan.md]`
- **Usage**: Request the agent to generate a plan for a specific Phase # (e.g., Phase 2) as defined in the workflow document. The agent will create a detailed `README.md` for the phase and individual step files.

### 2. Execute Implementation Steps
Execute the development tasks by following the generated step documents.
- **Workflow**: `@[.agent/workflows/execute-step.md]`
- **Usage**: Attach the specific step file (e.g., `step-2.1-database-schema.md`) to the agent context and request execution. The agent will follow the detailed instructions in the step file to implement the code, run tests, and verify acceptance criteria.

**Note**: Always ensure the plan is generated *before* attempting execution.
