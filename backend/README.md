# Backend Practicals — PHP & MySQL (Practicals 7–9)

**Student:** RUDRA MODI (25DCS058) | **Course:** ITUE203 Web Development Frameworks | **DEPSTAR CHARUSAT 2026**

---

## Requirements

- **XAMPP** (Apache + MySQL/MariaDB + PHP 8.x)
- PHP extensions: `mysqli`, `pdo_mysql`, `session`

## Setup Instructions

1. Start **XAMPP Control Panel** → Enable **Apache** and **MySQL**.
2. Copy each practical folder into `C:\xampp\htdocs\`:

```
C:\xampp\htdocs\Practical7\   ← copy from backend/Practical7/
C:\xampp\htdocs\Practical8\   ← copy from backend/Practical8/
C:\xampp\htdocs\Practical9\   ← copy from backend/Practical9/
```

3. For Practicals 8 & 9, import the database:
   - Open [phpMyAdmin](http://localhost/phpmyadmin)
   - Run `backend/Practical8/studenthub_schema.sql` (creates DB + tables)
   - Run `backend/Practical8/studenthub_seed.sql` (inserts 20 students, events, audit logs)

4. Access in browser:
   - **Practical 7:** http://localhost/Practical7/
   - **Practical 8:** http://localhost/Practical8/
   - **Practical 9:** http://localhost/Practical9/

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

## Database Schema Overview

```
users (id, name, email, password, role, status, created_at)
  │
  ├── students (id, user_id →FK, enrollment_no, course, semester, year, mobile, cgpa, dob)
  │
  ├── events (id, title, category, venue, event_date, status, created_by →FK)
  │
  ├── registrations (id, student_id →FK, event_id →FK)  [junction table]
  │
  └── audit_logs (id, user_id →FK, action, description, ip_address, created_at)
```

---

*© 2026 RUDRA MODI (25DCS058) — CHARUSAT DEPSTAR, ITUE203 Web Development Frameworks*
