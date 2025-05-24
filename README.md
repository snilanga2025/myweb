# PHP Student Registration System

## 1. System Overview

This is a simple PHP-based Student Registration System. It allows students to register for a class or system, and provides an admin panel for administrators to manage student accounts, including their profile information and images. The system focuses on core CRUD functionalities and basic security measures.

## 2. Features

*   **Student Frontend:**
    *   Student self-registration with first name, last name, email, password, and optional profile image.
    *   Client-side image preview during registration.
*   **Admin Panel Backend:**
    *   Secure admin login.
    *   Admin Dashboard.
    *   **Student Management (CRUD):**
        *   View all registered students in a paginated list (pagination not yet implemented, but table is ready).
        *   Manually create new student accounts.
        *   Edit existing student information (name, email, password, profile image).
        *   Client-side image preview during editing.
        *   Delete student accounts (also removes their profile image from server).
    *   Profile image uploads for students (handled during registration and admin edit).
*   **Security:**
    *   Password hashing for admin and student accounts.
    *   Protection against SQL Injection using prepared statements.
    *   Protection against XSS using `htmlspecialchars` for output encoding.
    *   CSRF token protection on all POST forms.
    *   Basic secure file upload checks (type, size).
*   **UI:**
    *   A colorful and user-friendly interface, styled with CSS.

## 3. Setup Instructions

### 3.1. Server Requirements

*   Web Server (Apache, Nginx, or similar) with PHP support (PHP 7.x or higher recommended).
*   MySQL Database server (MySQL 5.7 or higher, or MariaDB equivalent).
*   PHP extensions: `mysqli` (for database interaction), `gd` (if image manipulation beyond simple uploads were to be added, not strictly needed for current version but good to have).

### 3.2. Database Setup

1.  Create a new MySQL database (e.g., `student_registration_system`).
2.  Import the table structures using the `database/database.sql` file. This will create the `admins` and `students` tables.
    ```bash
    mysql -u your_username -p your_database_name < database/database.sql
    ```

### 3.3. Configuration

1.  **Database Connection:**
    Edit the file `backend/db_connect.php`. Update the following constants with your actual database credentials:
    ```php
    define('DB_SERVER', 'localhost'); // Or your DB host
    define('DB_USERNAME', 'your_db_user');
    define('DB_PASSWORD', 'your_db_password');
    define('DB_NAME', 'student_registration_system'); // Or your chosen DB name
    ```

2.  **File Permissions:**
    Ensure the `uploads/` directory in the project root is writable by your web server user (e.g., `www-data`, `apache`).
    ```bash
    chmod -R 755 uploads/ # Or 775 if needed, be cautious with permissions
    chown -R www-data:www-data uploads/ # If applicable
    ```

### 3.4. Initial Admin User Creation

1.  After setting up the database and configuration, open your web browser and navigate to:
    `http://yourdomain.com/path_to_project/backend/add_admin.php`
2.  This script will create a default admin user with:
    *   Username: `admin`
    *   Password: `securepassword123` (as defined in `add_admin.php` - **it's highly recommended to change this password immediately after first login via the application if an edit admin feature existed, or change it in the script before running**).
3.  **VERY IMPORTANT**: After the script confirms admin creation, **DELETE the `backend/add_admin.php` file from your server immediately**. This is crucial for security.

## 4. Directory Structure Overview

```
.
├── assets/                 # CSS, JavaScript, default images
│   ├── main_style.css
│   └── form_enhancements.js
│   └── default_avatar.png
├── backend/                # Admin panel PHP scripts and logic
│   ├── admin_dashboard.php
│   ├── admin_login.php
│   ├── admin_logout.php
│   ├── create_student.php
│   ├── delete_student.php
│   ├── db_connect.php
│   ├── edit_student.php
│   ├── security_utils.php
│   └── view_students.php
│   └── add_admin.php       # (To be DELETED after first use)
├── database/               # Database related files
│   └── database.sql
├── frontend/               # Student-facing PHP scripts
│   └── register.php
├── old_template/           # Original HTML/CSS/JS template (not used by the PHP app)
├── uploads/                # Directory for student profile images (must be writable)
├── index.php               # Main landing page
└── README.md               # This file
```

## 5. Security Notes

*   **CSRF Protection**: All forms performing state changes (login, create, edit) are protected by CSRF tokens.
*   **Password Hashing**: Passwords are hashed using `password_hash()` (PHP's default strong hashing).
*   **SQL Injection**: Prepared statements are used for all database queries involving user input.
*   **XSS Prevention**: Output is generally encoded using `htmlspecialchars()` to prevent XSS.
*   **File Uploads**: Basic checks for file type and size are implemented. Uploaded files are stored with unique names.
*   **`add_admin.php`**: This file is a setup utility and poses a significant security risk if left on the server after use. **DELETE IT IMMEDIATELY** after creating your initial admin account.
*   **Error Reporting**: For production, ensure PHP error reporting is set to not display errors to the user (log them instead). `display_errors = Off` in `php.ini`.
*   **HTTPS**: For any real-world deployment, use HTTPS to encrypt all traffic.

This documentation provides a basic guide to setting up and understanding the PHP Student Registration System.
```
