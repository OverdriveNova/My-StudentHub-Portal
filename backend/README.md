# Backend Practicals — PHP & MySQL (Practicals 7–15)

**Student:** RUDRA MODI (25DCS058) | **Course:** ITUE203 Web Development Frameworks | **DEPSTAR CHARUSAT 2026**

---

## Requirements

- **XAMPP** (Apache + MySQL/MariaDB + PHP 8.x)
- PHP extensions: `mysqli`, `pdo_mysql`, `session`, `fileinfo`, `json`

## Setup Instructions

1. Start **XAMPP Control Panel** → Enable **Apache** and **MySQL**.
2. Copy each practical folder into `C:\xampp\htdocs\`:

```
C:\xampp\htdocs\Practical7\    ← copy from backend/Practical7/
C:\xampp\htdocs\Practical8\    ← copy from backend/Practical8/
C:\xampp\htdocs\Practical9\    ← copy from backend/Practical9/
C:\xampp\htdocs\Practical10\   ← copy from backend/Practical10/
C:\xampp\htdocs\Practical11\   ← copy from backend/Practical11/
C:\xampp\htdocs\Practical12\   ← copy from backend/Practical12/
C:\xampp\htdocs\Practical13\   ← copy from backend/Practical13/
C:\xampp\htdocs\Practical14\   ← copy from backend/Practical14/
C:\xampp\htdocs\Practical15\   ← copy from backend/Practical15/
```

3. For Practicals 8–15, import the database:
   - Open [phpMyAdmin](http://localhost/phpmyadmin)
   - Run `backend/Practical8/studenthub_schema.sql` (creates DB + tables)
   - Run `backend/Practical8/studenthub_seed.sql` (inserts 20 students, events, audit logs)

4. Access in browser:
   - **Practical 7:** http://localhost/Practical7/
   - **Practical 8:** http://localhost/Practical8/
   - **Practical 9:** http://localhost/Practical9/
   - **Practical 10:** http://localhost/Practical10/
   - **Practical 11:** http://localhost/Practical11/
   - **Practical 12:** http://localhost/Practical12/
   - **Practical 13:** http://localhost/Practical13/
   - **Practical 14:** http://localhost/Practical14/
   - **Practical 15:** http://localhost/Practical15/

---

## Practical 7 — PHP Form Processing & CSV Storage

| File | Purpose |
|------|---------|
| `index.php` | POST form handler, CSRF token, server-side validation, `fputcsv()` write, live record table |
| `registrations.csv` | Runtime CSV storage (gitignored — generated on form submit) |

**Key concepts demonstrated:**
- `session_start()`, CSRF via `bin2hex(random_bytes(32))`, `hash_equals()`
- `filter_input()`, `trim()`, `htmlspecialchars()` sanitization
- Regex validation: name, email `FILTER_VALIDATE_EMAIL`, phone `^[6-9]\d{9}$`
- File I/O: `fopen()`, `flock(LOCK_EX)`, `fputcsv()`, `fgetcsv()`, `fclose()`
- Sticky form state repopulation on validation error

---

## Practical 8 — MySQL Schema, ER Model & PDO Connectivity

| File | Purpose |
|------|---------|
| `db.php` | Shared DB connector: MySQLi connection + `getPDO()` singleton function |
| `index.php` | Dashboard: connection status cards, table record counts, student JOIN query, PDO demo |
| `studenthub_schema.sql` | DDL: 5 tables (`users`, `students`, `events`, `registrations`, `audit_logs`) |
| `studenthub_seed.sql` | DML: 1 admin + 20 students, 10 events, 20 registrations, 5 audit log entries |

**Key concepts demonstrated:**
- Relational schema with `FOREIGN KEY`, `ON DELETE CASCADE`, `UNIQUE KEY`
- MySQLi prepared statements (`prepare()`, `execute()`, `get_result()`)
- PDO `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `ATTR_EMULATE_PREPARES => false`
- `INNER JOIN` across `users` ↔ `students` tables

---

## Practical 9 — Secure User Registration & Password Hashing

| File | Purpose |
|------|---------|
| `index.php` | Full registration processor: duplicate checks, bcrypt hashing, PDO transactions, audit log |

**Key concepts demonstrated:**
- `password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12])` — bcrypt cost factor 12
- `password_verify()` — timing-safe verification
- Duplicate email and enrollment_no check before INSERT
- PDO `beginTransaction()` → dual INSERT (`users` + `students`) → `commit()` / `rollBack()`
- Audit log entry on each successful registration
- `filter_input(INPUT_POST, ..., FILTER_SANITIZE_SPECIAL_CHARS)` backend sanitization
- Live password strength meter via JavaScript

---

## Practical 10 — Secure Authentication, Sessions & RBAC

| File | Purpose |
|------|---------|
| `index.php` | Complete auth portal: `password_verify()`, `session_regenerate_id(true)`, 15-min timeout, RBAC views, SHA-256 remember-me |

**Key concepts demonstrated:**
- Secure authentication with timing-safe `password_verify()`
- Session fixation protection using `session_regenerate_id(true)`
- 15-minute inactivity session expiration with automated redirect and audit logging
- Database-backed Remember-Me tokens hashed via SHA-256
- Role-Based Access Control (RBAC): Admin management dashboard vs Student portal view
- Full security audit logging for successful logins, failed attempts, and logouts

---

## Practical 11 — Student Management CRUD with Search & Filter

| File | Purpose |
|------|---------|
| `index.php` | MySQLi prepared statements for SELECT, INSERT, UPDATE, DELETE, multi-field search, filters, CSV export, soft-delete toggle |

**Key concepts demonstrated:**
- Complete CRUD workflow using parameterized MySQLi prepared statements
- Multi-column search across name, email, enrollment number, and phone
- Multi-criteria filtering by academic program (CSE/IT/ECE/ME/CE), semester (1-8), and account status
- Client-side confirmation modal before permanent deletion
- Advanced extension: Soft delete account status toggle (`active` / `inactive`)
- Intermediate extension: Filtered export of student records to CSV stream

---

## Practical 12 — Event Management CRUD with Poster Upload & File Validation

| File | Purpose |
|------|---------|
| `index.php` | Event CRUD, poster file validation (MIME, extension, size <= 2MB), cryptographic renaming, directory protection |
| `uploads/.htaccess` | Apache directory protection preventing script execution inside upload directory |
| `uploads/posters/.gitkeep` | Preserves upload folder structure in version control |

**Key concepts demonstrated:**
- Server-side file upload verification using `finfo_file()` for true MIME type validation (`image/jpeg`, `image/png`, `image/webp`)
- File size boundary validation (max 2MB) and extension whitelisting
- Cryptographically safe file renaming (`poster_<timestamp>_<random>.<ext>`) preventing directory traversal and overwriting
- Direct file cleanup (`unlink()`) on event deletion or poster replacement
- Intermediate extension: Event filtering by category and registration status
- Advanced extension: Directory protection via `.htaccess` and audit logging

---

## Practical 13 — PHP JSON API & Asynchronous Fetch CRUD

| File | Purpose |
|------|---------|
| `api.php` | RESTful JSON API handling GET, POST, PUT, DELETE with HTTP status codes and centralized response handler |
| `index.php` | Asynchronous single-page event manager: debounced live search, async modal CRUD, toast notifications |

**Key concepts demonstrated:**
- RESTful JSON API returning structured payloads with proper status codes (200, 201, 400, 404, 500)
- Asynchronous CRUD via browser `fetch()` API without full page reloads
- Debounced live search delaying execution by 300ms to minimize server queries
- Dynamic DOM insertion, update, and removal with CSS animation transitions
- Unified JSON error handling and real-time toast feedback

---

## Practical 14 — Admin Dashboard with RBAC, Dynamic Menu & Analytics

| File | Purpose |
|------|---------|
| `index.php` | Superuser administrative panel: role inspection, dynamic navigation bar, active user toggle, pure bar chart, audit log |

**Key concepts demonstrated:**
- Role-Based Access Control strictly gatekeeping sensitive administrative operations
- Dynamic navigation menu adapting its items based on current role (`admin` vs `student` vs `guest`)
- Active/Inactive account toggle with immediate persistence and audit trail logging
- Pure CSS/HTML data analytics bar chart visualizing event registrations by category
- System activity monitor tracking recent logins, status changes, and deletions

---

## Practical 15 — Full-Stack Integration, Testing & Deployment Hub

| File | Purpose |
|------|---------|
| `index.php` | Master integration dashboard, server runtime health check, database diagnostics, navigation matrix, compliance checklist |
| `apache_vhost.conf` | Virtual host template for deploying StudentHub to `http://studenthub.local` |

**Key concepts demonstrated:**
- Comprehensive system health verification across PHP runtime, extensions, database, and file permissions
- Full-stack integration matrix connecting all 15 semester practicals into a unified portal
- End-to-end verification checklist covering WCAG 2.1 AA accessibility, SQL injection, CSRF, and session fixation
- Apache Virtual Host deployment configuration and local DNS mapping

---

## Database Schema Overview

```
users (id, name, email, password, role, status, last_login, created_at)
  │
  ├── students (id, user_id →FK, enrollment_no, course, semester, year, mobile, cgpa, dob)
  │
  ├── events (id, title, category, venue, event_date, status, poster_path, created_by →FK)
  │
  ├── registrations (id, student_id →FK, event_id →FK)  [junction table]
  │
  ├── remember_tokens (id, user_id →FK, token_hash, expires_at, created_at)
  │
  └── audit_logs (id, user_id →FK, action, description, ip_address, created_at)
```

---

*© 2026 RUDRA MODI (25DCS058) — CHARUSAT DEPSTAR, ITUE203 Web Development Frameworks*
