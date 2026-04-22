<img width="1869" height="905" alt="image" src="https://github.com/user-attachments/assets/ad2eb853-c106-4632-9a32-c25947967f6f" />
<img width="1909" height="907" alt="image" src="https://github.com/user-attachments/assets/c7b31610-d0b6-431b-a37d-4c67570548c8" />
<img width="1900" height="964" alt="image" src="https://github.com/user-attachments/assets/0e77d279-f2e9-4366-b548-0ea7c0a88c30" />


# Dallas Premiere Hotel - ICT Management System

This system provides a centralized dashboard for managing ICT operations within the hotel environment. It is designed to streamline infrastructure monitoring, incident management, and automated maintenance workflows.

---

## Technical Overview

### Infrastructure Management
The system tracks critical network operations, including VLAN management and static IP assignments. It also provides real-time monitoring for power systems, covering both solar arrays and UPS clusters, and maps assets across all nine floors of the facility.

### Service and Incident Tracking
Incident management is handled through a full-lifecycle ticketing system that includes priority levels and SLA indicators. For hotel staff outside of the ICT department, a dedicated self-service portal allows for quick issue reporting and status tracking.

### Operations and Security
The platform includes an automated maintenance scheduler for daily, weekly, and monthly tasks. Security is maintained through an encrypted credential vault using AES-256-CBC and a comprehensive audit logging system that tracks all administrative actions.

---

## Access and Redirection

Users are automatically directed to the interface corresponding to their role upon successful login:

| Role | Access Level | Primary Landing Page |
| :--- | :--- | :--- |
| **Admin** | Full system control | Administrator Dashboard |
| **Technician** | Incident and asset management | Administrator Dashboard |
| **Staff** | Issue reporting and status tracking | End-User Self-Service Portal |

---

## Technical Stack

- **Backend**: Native PHP 8 using PDO for secure database interactions.
- **Database**: MySQL or MariaDB.
- **Frontend**: Modern interface built with Tailwind CSS and Alpine.js.
- **Security**: Robust encryption standards and role-based access control.

---

## Installation and Setup

1.  **Database Configuration**:
    - Create a new database such as `hotel_ict`.
    - Import the `database/production_schema.sql` file to set up necessary tables.
2.  **System Configuration**:
    - Update your database credentials in `config/database.php`.
    - Ensure the `ENCRYPTION_KEY` in `includes/encryption.php` is updated before production use.
3.  **Deployment**:
    - Upload the project files to your web server root.
    - Log in using the default credentials: `admin` / `password`.

---

This system is maintained by the Dallas ICT Team (2026) to ensure the operational continuity of all hotel technology services.
