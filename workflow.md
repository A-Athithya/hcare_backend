# Project Workflow & Architecture Documentation

This document provides a highly detailed, end-to-end workflow for all 11 modules implemented in the Healthcare (Hcare) system. It covers security protocols, data flow, and role-based logic.

---

## 🛠 1. System-Wide Base Workflow
Every request to the Hcare API follows a strict lifecycle to ensure security and tenant isolation.

### A. The Encryption Envelope (AES-256-CBC)
*   **Request**: All `POST`, `PUT`, `PATCH` bodies must be sent as an encrypted JSON object: `{"payload": "BASE64_ENCRYPTED_STRING"}`.
    *   **Logic**: `EncryptionMiddleware` intercept the request.
    *   **Process**: It extracts the IV (first 16 bytes), decrypts the payload using `APP_KEY`, and populates `$_REQUEST['decoded_input']`.
*   **Response**: Every response is automatically encrypted by `Response::json()` helper before being sent to the client.

### B. Security & Identity Layers
1.  **CSRF Protection**: `CsrfMiddleware` validates the `X-CSRF-Token` header against the PHP `$_SESSION['csrf_token']`.
2.  **Authentication (JWT + Session)**: 
    *   The system checks for a `Bearer` token in the `Authorization` header OR a token in `$_SESSION['accessToken']`.
    *   `AuthMiddleware` validates the JWT and extracts `user_id`, `role`, and `tenant_id`.
    *   Context is stored in `$_REQUEST['user']`.
3.  **Tenant Isolation (The Golden Rule)**: 
    *   Every query in the `Repositories` includes `WHERE tenant_id = :tenant_id`.
    *   This ensures data from one clinic (tenant) is never visible to another.

### C. Registration & Onboarding Hierarchy
The system distinguishes between **Self-Service** enrollment and **Managed** staff onboarding:

*   **Self-Service (Public)**: 
    *   **Admin**: Registers to create a new Tenant (Clinic).
    *   **Patient**: Registers to access their medical history and book appointments.
    *   *Endpoint*: `POST /register` (Public).
*   **Managed Onboarding (Internal)**:
    *   **Staff (Doctor, Nurse, Pharmacist, Receptionist)**: While the technical route is open, the intended workflow is for an **Admin** to create these accounts via the Staff/User Management modules. This ensure they are correctly linked to the clinic's data and license requirements.
    *   *Endpoint*: `POST /staff` or `POST /users` (Admin Only).

---

## 📂 2. Detailed Module Workflows

### 🟢 Module 1: Authentication & Tenant Management
**Purpose**: Manages user entry, tenant assignment, and session security.
-   **Flow**:
    1.  **Handshake**: Client calls `GET /csrf-token` to initialize session.
    2.  **Registration**: `POST /register` creates a new user and assigns a `tenant_id` (default 1 for new clinics). It hashes the password using `PASSWORD_BCRYPT`.
    3.  **Login**: `POST /login` verifies credentials. On success, it generates:
        -   **Access Token**: Short-lived JWT (15 mins) for API access.
        -   **Refresh Token**: Long-lived (7 days) stored in `token_management` table.
        -   **CSRF Token**: Stored in session for subsequent state-changing requests.
-   **Key Endpoints**: `POST /login`, `POST /register`, `GET /csrf-token`, `POST /logout`.

### 🔵 Module 2: User & Role Management
**Purpose**: Allows Admins to manage staff accounts and roles.
-   **Roles**: `Admin` only.
-   **Flow**: 
    1.  Admin lists users via `GET /users`.
    2.  Admin creates a user (e.g., Nurse) via `POST /users`.
    3.  User details are updated via `PUT /users/{id}`.
-   **Data Validation**: Ensures email uniqueness within the system.

### 🔴 Module 3: Patient Management
**Purpose**: Centralized storage for medical records.
-   **Roles**: `Admin`, `Provider` (Doctor), `Nurse`.
-   **Flow**:
    1.  `POST /patients`: Creates a patient record.
    2.  `GET /patients/{id}/appointments`: Fetches history specifically for that patient.
-   **Logic**: Records are "Soft Deleted" (`is_deleted = 1`) to preserve historical medical data. See [Data Retention Policy](#soft-deletion--data-retention) for details.

### 🟣 Module 4: Appointment & Scheduling
**Purpose**: Handles the booking lifecycle.
-   **Flow**:
    1.  **Conflict Check**: Before saving, `AppointmentRepository::hasConflict` checks if the doctor is already booked for that exact date/time.
    2.  **Booking**: `POST /appointments` saves with status `Pending`.
    3.  **Lifecycle**: Appointments can move from `Pending` -> `Confirmed` -> `Completed` or `Cancelled`.
-   **Automated Logic**: Fetching "Upcoming" appointments filters by `date >= today`.

### 🟡 Module 5: Prescription & Pharmacy
**Purpose**: Bridge between doctors and pharmacists.
-   **Data Flow**:
    1.  **Doctor**: Calls `POST /prescriptions` with medicine array (JSON) and dosage.
    2.  **Pharmacist**: Views `GET /prescriptions` or filters by `GET /prescriptions/status/Pending`.
    3.  **Dispensing**: Pharmacist calls `PATCH /prescriptions/{id}/status` to mark as `Dispensed`.
-   **Storage**: Medical instructions are stored as text, while medicine lists are stored as JSON strings in the DB.

### 🟠 Module 6: Dashboard & Reports
**Purpose**: Real-time analytics for clinic performance.
-   **Roles**: `Admin` (Global), `Provider` (Tenant-specific).
-   **Logic**:
    -   **Tenant Dashboard**: Aggregates patient count, appointment status counts, and prescription volume for the logged-in `tenant_id`.
    -   **Global Analytics**: (Admin Only) Aggregates data across ALL tenants for system-wide health monitoring.

### 🏥 Module 7: Communication (Notes)
**Purpose**: Internal collaboration between medical staff regarding specific appointments.
-   **Flow**:
    1.  `POST /communication/notes`: A Nurse adds a note like "Patient fever is high" to an appointment.
    2.  `GET /communication/notes/appointment/{id}`: Doctor views all notes in chronological order during the checkup.
-   **Security**: Notes are linked to both `appointment_id` and `tenant_id`.

### 💸 Module 8: Billing & Payment
**Purpose**: Financial management and invoice tracking.
-   **Flow**:
    1.  **Invoice Generation**: After an appointment, Admin/Provider calls `POST /billing` to create an `Unpaid` invoice.
    2.  **Payment**: When paid, `PATCH /billing/{id}/status` updates it to `Paid` and records the `paid_amount`.
    3.  **Summary**: `GET /billing/summary` provides total "Paid" vs "Pending" amounts for the clinic.

### 👩‍⚕️ Module 9: Staff Management
**Purpose**: Detailed profiles for non-system users (Doctors, Nurses, etc.).
-   **Mapping**: Uses a polymorphic-style repository where a single `StaffRepository` handles multiple tables (`doctors`, `nurses`, `pharmacists`, `receptionists`) based on a `role` parameter.
-   **Details**: Stores specialization, license numbers, and available shifts.

### 📅 Module 10: Calendar API
**Purpose**: Visual scheduling data for frontends.
-   **Flow**:
    1.  `GET /appointments/calendar?start=...&end=...`: Fetches all appointments within a date range.
    2.  `GET /appointments/{id}/tooltip`: Provides a lightweight detail object for hover-effects (Patient name, Reason, Time).

### 🛡️ Module 11: Settings & Security
**Purpose**: Account security and session termination.
-   **Logic**:
    -   `POST /change-password`: Validates `oldPassword` before allowing update.
    -   `POST /logout`: Revokes the Refresh Token in the database and destroys the PHP Session.
    -   `POST /csrf-regenerate`: Rotates the CSRF token for higher security during sensitive operations.

---

## 🔑 3. Role-Based Access Matrix

| Module | Admin | Provider | Nurse | Pharmacist | Patient |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Auth** | Full | Full | Full | Full | Full |
| **Users** | CRUD | View | - | - | - |
| **Patients** | CRUD | CRUD | CRUD | - | View |
| **Appointment**| CRUD | CRUD | CRUD | - | Book/View |
| **Prescription**| Full | Create | View | Dispense | View |
| **Billing** | Full | View | - | - | View |
| **Staff** | CRUD | View | View | - | - |
| **Inventory** | CRUD | - | - | CRUD | - |
| **Dashboard** | Global | Tenant | - | - | - |

---

## 📋 4. System Policies

### Soft-Deletion & Data Retention
*   **Mechanism**: The system uses a flag-based soft deletion strategy (`is_deleted = 1`).
*   **Duration**: There is **no fixed duration** for soft-deleted records. 
*   **Policy**: Once soft-deleted, records remain in the database **indefinitely** to ensure medical audit trails are preserved. They are excluded from all standard API responses but can be recovered or audited manually via the database if necessary.
