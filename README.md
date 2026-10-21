# StudentHub Portal — Lab Portfolio (Practicals 1–15)

**Course:** Web Development Frameworks (ITUE203)
**Semester:** 3rd Semester, 2026-27 (ODD)
**Institution:** Devang Patel Institute of Advance Technology and Research (DEPSTAR), CHARUSAT
**Student:** RUDRA MODI | **Enrollment:** 25DCS058

---

## Project Overview

**StudentHub** is a centralized academic campus portal for CHARUSAT students. It provides a unified, full-stack platform covering public information, student authentication, an academic dashboard, event listings, profile management, admin panels, PHP form handling, relational database integration, RESTful JSON APIs, secure user registration, role-based access control, file uploads, async AJAX CRUD, and a complete deployment integration hub — built progressively across **15 practicals** using HTML5, CSS3, vanilla JavaScript, PHP 8.x, and MySQL/MariaDB (via XAMPP).

---

## Directory Structure

```
Web Development/
│
├── index.html                          # Home page — entry point of the portal
├── README.md                           # This documentation file
├── .gitignore                          # Git ignored files config
│
├── pages/                              # All secondary HTML pages (10 pages)
│   ├── about.html                      # About Us — portal vision, course table
│   ├── admin.html                      # Admin Dashboard — student list, stats
│   ├── contact.html                    # Contact Support — inquiry form
│   ├── dashboard.html                  # Student Dashboard — schedule, timetable
│   ├── events.html                     # Campus Events — search, filter, pagination
│   ├── faq.html                        # FAQ — accordion fetched from JSON
│   ├── feedback.html                   # Feedback Survey — rating form
│   ├── login.html                      # User Login — authentication form
│   ├── profile.html                    # Student Profile — info display
│   └── register.html                   # Student Registration — comprehensive form
│
├── assets/
│   ├── css/
│   │   └── index.css                   # Single shared stylesheet for all pages
│   ├── js/
│   │   ├── theme-nav.js                # Dark mode, nav toggle, modal, slider
│   │   ├── registration-validation.js  # Form validation with regex + strength meter
│   │   └── fetch-data.js               # Fetch API: events, students, FAQs from JSON
│   ├── data/
│   │   ├── events.json                 # Campus events dataset (15 records)
│   │   ├── faqs.json                   # FAQ dataset (15 items)
│   │   └── students.json               # Student records dataset (15 records)
│   └── images/
│       ├── Background_Img/             # Campus banner image & profile avatar
│       └── screenshots/               # Multi-device responsive screenshots
│
├── backend/                            # PHP Server-Side Practicals (7–15)
│   ├── README.md                       # Setup guide for PHP practicals
│   ├── Practical7/
│   │   └── index.php                   # PHP form processing, CSRF, validation, CSV export
│   ├── Practical8/
│   │   ├── db.php                      # Shared MySQL connection (MySQLi & PDO)
│   │   ├── index.php                   # Database dashboard, schema viewer, SQL joins
│   │   ├── studenthub_schema.sql       # Database DDL schema (5 tables)
│   │   └── studenthub_seed.sql         # Seed data (20+ students, events, audit logs)
│   ├── Practical9/
│   │   └── index.php                   # Secure registration, Bcrypt hashing, PDO transactions
│   ├── Practical10/
│   │   └── index.php                   # Secure auth, session timeout, RBAC, remember-me token
│   ├── Practical11/
│   │   └── index.php                   # Student CRUD, search, filter, CSV export, soft delete
│   ├── Practical12/
│   │   ├── index.php                   # Event CRUD, validated poster upload, status management
│   │   └── uploads/                    # Upload directory with .htaccess protection
│   ├── Practical13/
│   │   ├── api.php                     # RESTful JSON API endpoint (GET/POST/PUT/DELETE)
│   │   └── index.php                   # Asynchronous Fetch API CRUD interface with debounced search
│   ├── Practical14/
│   │   └── index.php                   # Admin RBAC dashboard, dynamic menu, user status toggle, chart
│   └── Practical15/
│       ├── apache_vhost.conf           # Apache Virtual Host deployment configuration
│       └── index.php                   # Master integration hub, runtime diagnostics, compliance audit
│
└── docs/                               # Reference documents
    └── learning reference/

XAMPP Web Server Deployment (C:\xampp\htdocs\):
│
├── Practical7/                         # Server-Side Form Handling & File I/O
│   ├── index.php                       # PHP Form processing, CSRF token, validation, CSV export
│   └── registrations.csv               # CSV file backend storage
│
├── Practical8/                         # Database Integration & Relational Schema
│   ├── db.php                          # Shared MySQL connection layer (MySQLi & PDO)
│   ├── index.php                       # Database dashboard, schema code viewer, SQL joins
│   ├── studenthub_schema.sql           # Database DDL schema (5 tables)
│   └── studenthub_seed.sql             # SQL seed script (20+ students, events, audit logs)
│
├── Practical9/                         # Secure Database Operations & User Registration
│   └── index.php                       # Registration processor, duplicate checks, Bcrypt hashing
│
├── Practical10/                        # Secure Session Authentication & Role-Based Access Control
│   └── index.php                       # Login/Logout, password_verify, 15-min timeout, RBAC
│
├── Practical11/                        # Student Management Module CRUD with Search & Filter
│   └── index.php                       # MySQLi prepared statements CRUD, CSV export, soft-delete
│
├── Practical12/                        # Event Management Module CRUD with Poster Upload
│   ├── index.php                       # Event CRUD, MIME & size validated poster upload
│   └── uploads/                        # Uploaded event posters with .htaccess protection
│
├── Practical13/                        # Asynchronous JSON API & Fetch CRUD
│   ├── api.php                         # RESTful JSON API backend
│   └── index.php                       # Asynchronous SPA interface
│
├── Practical14/                        # Admin Dashboard with RBAC & Dynamic Menus
│   └── index.php                       # Administrative monitoring panel, status toggle, analytics
│
└── Practical15/                        # Master Integration, Testing & Deployment Hub
    ├── apache_vhost.conf               # Apache Virtual Host configuration
    └── index.php                       # System health diagnostics and compliance audit matrix
```

---

## File Descriptions

### Root Files

| File | Purpose |
|------|---------|
| `index.html` | Home page — entry point. Shows portal intro, campus announcements, key services list, and external links. Contains notification banner, modal popup trigger, and quick-search form. |
| `README.md` | This file — full project documentation. |

### HTML Pages (`pages/`)

| File | Purpose |
|------|---------|
| `about.html` | Portal vision, ordered course learning roadmap with nested lists, and ITUE203 module breakdown table (with `colspan`/`rowspan`). |
| `admin.html` | Admin dashboard — system statistics cards and live-searchable student list fetched from `students.json`. |
| `contact.html` | Contact support — inquiry form with text, email, tel, select, textarea, and file upload inputs. |
| `dashboard.html` | Student academic dashboard — weekly timetable table, subject list, attendance stats, and a quick-search form. |
| `events.html` | Campus events — Fetch API events from `events.json`, keyword search, status/category filters, sort-by-date, and 5-per-page pagination. Also has a static HTML timetable table. |
| `faq.html` | FAQ — fetches `faqs.json` and renders collapsible accordion items with a live search bar. |
| `feedback.html` | Feedback survey — star rating (radio), feature checkboxes, range slider, and comment textarea. |
| `login.html` | Login form — email, password, "remember me" checkbox, and hidden session token field. |
| `profile.html` | Student profile — readonly display fields (name, email, course, year, CGPA) and photo upload. |
| `register.html` | Full registration form — all HTML5 input types: text, email, password, tel, number, date, url, file, radio, checkbox, select, textarea, hidden, readonly, disabled. Live strength meter + validation. |

### CSS (`assets/css/`)

| File | Purpose |
|------|---------|
| `index.css` | Single unified stylesheet shared by all 11 pages. Contains CSS custom properties (design tokens), base reset, header/footer, navigation, breadcrumbs, forms, tables, media queries (mobile/tablet/desktop), dark mode overrides, modal, accordion, event/student cards, pagination, and form error states. |

### JavaScript (`assets/js/`)

| File | Purpose |
|------|---------|
| `theme-nav.js` | (1) Light/Dark theme toggle with `localStorage` persistence. (2) Mobile hamburger nav toggle with `aria-expanded`. (3) Dismissible notification banner with `sessionStorage`. (4) Event modal popup with keyboard (Escape) and backdrop-click support. (5) Announcement carousel with auto-advance every 5s and dot indicators. |
| `registration-validation.js` | Client-side registration form validation: regex for name, email, mobile (Indian format), and password complexity. Live password strength meter (weak/medium/strong). Per-field inline error messages with red border highlight. Focuses first failing field on submit. |
| `fetch-data.js` | Fetch API with `async/await` and fallback path resolution. Dynamically loads and renders: (1) events from `events.json` (search, filter, sort, paginate, modal), (2) students from `students.json` (text + CGPA search), (3) FAQs from `faqs.json` (accordion with search). |

### JSON Data (`assets/data/` and root)

| File | Purpose |
|------|---------|
| `events.json` | 10 campus event objects — `title`, `date`, `venue`, `category`, `status`, `description`. Powers the Events page. |
| `faqs.json` | FAQ objects — `question`, `answer`, `category`. Powers the FAQ accordion. |
| `students.json` | Student record objects — `name`, `enrollment`, `course`, `year`, `email`, `mobile`, `cgpa`, `status`. Powers Admin and Profile pages. |

---

## Practical Summary (Practicals 1 to 15)

### Practical 1 — Project Setup, Sitemap & Wireframes

**What we did:**
- Defined the project scope: a multi-page campus portal called *StudentHub* for CHARUSAT students.
- Planned all 11 pages and their purpose with three user role levels: Guest, Registered Student, Administrator.
- Created a **sitemap** diagram showing the navigation hierarchy between all pages.
- Drew **wireframes** for all 11 pages — low-fidelity layout mockups (header, nav, main, footer placement).
- Set up a **GitHub repository** with a structured folder layout and initial commit.
- Documented URL anatomy, browser rendering process, and navigation flow.

**What was new in this practical:**
- First time designing a multi-page web project from scratch with proper planning.
- Introduction to Git version control, conventional commits, and repository structure.
- Learning to think about user roles (Guest / Student / Admin) before writing any code.

---

### Practical 2 — Semantic HTML5 Pages with Accessibility

**What we did:**
- Built the full HTML skeleton for all 11 pages using semantic HTML5: `<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<aside>`, `<footer>`.
- Applied a consistent page template: Header → Nav → Breadcrumbs → Main → Footer.
- Used all text formatting tags: `<strong>`, `<em>`, `<mark>`, `<ins>`, `<del>`, `<sup>`, `<sub>`, `<code>`, `<small>`, `<b>`, `<i>`.
- Built **data tables** with `<thead>`, `<tbody>`, `<tfoot>`, `<caption>`, `scope`, `colspan`, `rowspan`.
- Used unordered, ordered (`type`, `start`, `reversed` attributes), and nested lists.
- Added external links with `target="_blank" rel="noopener noreferrer"` for security.
- Added images with `loading="lazy"`, `referrerpolicy`, and descriptive `alt` text.
- Added **accessibility**: skip-link, `aria-label` on navs, `aria-current="page"`, single `<h1>`, heading hierarchy.

**What was new in this practical:**
- First full use of the HTML5 semantic element set.
- Learning WCAG 2.1 accessibility patterns (skip links, ARIA attributes, focus management).
- Understanding `colspan` and `rowspan` to build complex multi-header data tables.

---

### Practical 3 — Responsive CSS Design (Grid, Flexbox, Media Queries)

**What we did:**
- Created `assets/css/index.css` — a single shared stylesheet for all pages.
- Defined **CSS custom properties** (`:root` variables) for colors, shadows, radii, fonts, and transitions.
- Applied a **base reset** (`box-sizing: border-box`, `margin: 0`, `padding: 0`).
- **CSS Flexbox** for: header actions, navigation menu, breadcrumbs, form button groups, card rows, pagination.
- **CSS Grid** for: announcements (1-col → 2-col at 768px), portal highlights (1-col → 3-col at 768px).
- **Responsive media queries**: mobile-first → `@media (min-width: 768px)` tablet → `@media (min-width: 1024px)` desktop.
- Styled inputs, buttons, tables, and footer with consistent design tokens.
- Added `max-width: 1200px` container centering.

**What was new in this practical:**
- First use of CSS variables (custom properties) as a design token system.
- Learning the difference between Flexbox (1D row/column) and Grid (2D layout).
- Understanding mobile-first responsive design with breakpoints.

---

### Practical 4 — JavaScript DOM Manipulation & Interactive UI

**What we did:**
- Created `assets/js/theme-nav.js` and wired up all interactive features:
  - **Dark Mode**: reads/writes `localStorage`, applies `.dark` class to `<body>`.
  - **Mobile Nav Toggle**: hamburger shows/hides nav `<ul>`, updates `aria-expanded`.
  - **Dismissible Banner**: close button hides notification bar, state saved in `sessionStorage`.
  - **Modal Popup**: "Event Modal" button opens overlay. Closes via ✕, backdrop click, or `Escape`. Content dynamically populated by `openEventModal()`.
  - **Carousel Slider**: auto-advances every 5s, Prev/Next buttons, dot navigation.
  - **FAQ Accordion**: fetches `faqs.json`, renders collapsible Q&A items, click to toggle.

**What was new in this practical:**
- First JavaScript: `getElementById`, `classList.toggle`, `addEventListener`.
- `localStorage` and `sessionStorage` for client-side state persistence across sessions/tabs.
- Building a modal with keyboard (Escape) and backdrop accessibility.
- First use of `fetch()` to load external JSON data into the page.

---

### Practical 5 — HTML5 Forms & Client-Side Validation

**What we did:**
- Fully built `pages/register.html` covering every HTML5 input type:
  - `text`, `email`, `password`, `tel`, `number`, `date`, `url`, `file`, `search`
  - `radio` (gender), `checkbox` (interests, terms), `select` (department, semester)
  - `textarea`, `hidden`, `readonly`, `disabled`
- Used form attributes: `action`, `method`, `enctype`, `required`, `minlength`, `maxlength`, `min`, `max`, `placeholder`, `accept`.
- Created `assets/js/registration-validation.js` with:
  - Regex for name `/^[A-Za-z ]{3,}$/`, email, mobile `/^[6-9]\d{9}$/`, password complexity.
  - Confirm-password match check.
  - Required selection checks (gender, course, semester, terms).
  - Live password strength meter showing Weak / Medium / Strong.
  - Per-field inline red error messages; first failing field gets focus.
  - On success: `alert()` + `form.reset()`.

**What was new in this practical:**
- First use of Regular Expressions (`RegExp`) for field-level validation.
- Building a live password strength indicator with real-time `input` event listening.
- Managing multiple independent error states per field (set, clear, focus).
- Understanding the difference between HTML5 native validation attributes and custom JavaScript validation.

---

### Practical 6 — Fetch API, JSON Data Rendering, Search & Pagination

**What we did:**
- Created JSON datasets: `events.json` (10 events), `students.json`, `faqs.json`.
- Created `assets/js/fetch-data.js` using **Fetch API** with `async/await` and smart fallback path resolution (automatically handles `/` root vs `/pages/` subdirectory).

  **Events Module** (`events.html`, `dashboard.html`):
  - Loads `events.json` → renders styled event cards (title, date, venue, category, status badge).
  - Live **keyword search** (title, category, venue, description).
  - **Status filter** dropdown (Open / Limited Seats / Coming Soon).
  - **Category filter** dropdown (Technical, Sports, Cultural, etc.).
  - **Sort by date** button — toggles ascending/descending.
  - **Client-side pagination** — 5 events per page with numbered buttons and page info.
  - Each card has "View Details" → opens modal with the event's full data.

  **Students Module** (`admin.html`, `profile.html`):
  - Loads `students.json` → renders student cards (name, enrollment, course, year, email, CGPA).
  - Live search by name, enrollment, course, year, email, or CGPA (numeric `>=` comparison).

  **FAQ Module** (`faq.html`):
  - Loads `faqs.json` → renders accordion items with expand/collapse and icon (+/−).
  - Live search filtering by question, answer, or category.
  - Hardcoded fallback FAQs if the fetch fails.

**What was new in this practical:**
- First use of `async/await` syntax for asynchronous data loading.
- Building a complete client-side search + filter + sort + pagination system from scratch.
- Dynamic DOM generation with `document.createElement()` and template literals.
- Conditional CSS class badges (`badge-open`, `badge-limited`, `badge-soon`) based on data values.
- Integrating with the Practical 4 modal to show full event details on card click.
- Graceful error handling with `try/catch` and fallback data.

---

### Practical 7 — PHP Form Processing with Server-Side Validation and CSV Storage

**What we did:**
- Created backend form processor in PHP using `POST` method handling.
- Implemented security measures including CSRF tokens (`bin2hex(random_bytes(32))`) and input sanitization (`htmlspecialchars`, `trim`).
- Performed robust server-side validation for email, phone numbers, and required fields.
- Implemented file storage operations using `fputcsv()` to write submission records to `registrations.csv`.
- Displayed live CSV stored records in a styled HTML table directly within the page.

**What was new in this practical:**
- Server-side execution using Apache/PHP in XAMPP environment.
- Native PHP session management (`session_start()`) and anti-CSRF token verification.
- File system I/O stream processing (`fopen`, `fputcsv`, `fgetcsv`, `fclose`).

---

### Practical 8 — MySQL Schema Design, ER Model, PDO Connectivity and Prepared Statements

**What we did:**
- Designed 5-table relational database schema (`users`, `students`, `events`, `registrations`, `audit_logs`) with Foreign Key constraints and cascades.
- Built robust dual DB connector `db.php` supporting both **MySQLi** and **PDO** API wrappers.
- Created relational SQL query views utilizing `INNER JOIN` across multiple tables.
- Developed dynamic PHP database dashboard displaying real-time table counts, SQL schema source code, and connection status cards.

**What was new in this practical:**
- Relational schema architecture with primary/foreign keys and indexed constraints.
- Object-Oriented database connectivity via PDO (`PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`).
- Parameterized prepared statements preventing SQL Injection attacks.

---

### Practical 9 — Secure User Registration with Database Insert, Duplicate Email Check, and Password Hashing

**What we did:**
- Developed secure multi-step backend registration workflow interacting with `studenthub` database.
- Implemented backend pre-flight checks for duplicate Email and Enrollment Number before insertion.
- Enforced password security using `password_hash()` with `PASSWORD_BCRYPT` (cost factor 12).
- Utilized PDO database transactions (`beginTransaction()`, `commit()`, `rollBack()`) to atomically write to both `users` and `students` tables.
- Automated security audit logging into `audit_logs` table upon successful registration.
- Added live frontend password strength gauge and sticky form state repopulation on validation errors.

**What was new in this practical:**
- Cryptographic password hashing (`password_hash`, `password_verify`).
- Transaction safety ensuring database consistency across multi-table writes.
- Backend sanitization via `filter_input(INPUT_POST, ..., FILTER_SANITIZE_SPECIAL_CHARS)`.

---

### Practical 10 — Secure Login/Logout with Sessions, Role-Based Access, Timeout, and Remember-Me Token

**What we did:**
- Developed complete secure authentication workflow using PHP native sessions and anti-CSRF token verification.
- Verified passwords safely using `password_verify()` against bcrypt hashes stored in `studenthub.users`.
- Implemented session fixation defense by calling `session_regenerate_id(true)` immediately after successful authentication.
- Implemented automated **15-minute inactivity timeout**: tracks `$_SESSION['last_activity']`, destroys expired sessions, and redirects with notification.
- Built **Role-Based Access Control (RBAC)**:
  - **Admin View**: Displays university metrics, management links, and live security audit logs.
  - **Student View**: Displays individual profile card (enrollment number, semester, CGPA) and enrolled campus events.
- Implemented **Database-Backed Remember-Me** (Advanced Extension): generates 64-character cryptographic tokens, stores SHA-256 hash in `remember_tokens` table for 30 days, and auto-logs in via HTTP-only cookie.
- Recorded comprehensive audit trails in `audit_logs` table for successful logins, failed attempts, timeouts, and manual logouts.

**What was new in this practical:**
- Complete session lifecycle security: `session_regenerate_id()`, cookie param flags (`httponly`, `samesite`), `session_destroy()`.
- Client and server timeout synchronization with a live JavaScript countdown timer.
- Database-backed token persistence for long-term authentication without storing raw credentials in cookies.

---

### Practical 11 — Student Management CRUD with Search and Filter

**What we did:**
- Developed data-driven administrative student management module implementing full CRUD operations.
- Built all database queries using parameterized **MySQLi prepared statements** preventing SQL injection.
- Implemented multi-field keyword search across name, email, enrollment number, and mobile phone.
- Built multi-criteria filtering by academic program (CSE, IT, ECE, ME, CE), semester (1–8), and account status.
- Implemented **Soft Delete** toggle (Advanced Extension) using account status column (`active` / `inactive`), as well as permanent hard delete with modal confirmation.
- Implemented **CSV Export** stream (Intermediate Extension): dynamically generates downloadable `.csv` spreadsheet containing filtered student records.
- Added live statistical metrics: Total Students, Active Accounts, Inactive Accounts, and Average CGPA.

**What was new in this practical:**
- Full MySQLi prepared statement lifecycle (`prepare`, `bind_param`, `execute`, `get_result`).
- Building dynamic WHERE clauses with variable parameter binding for combinable filters.
- Generating HTTP streaming file downloads (`Content-Type: text/csv`, `php://output`, `fputcsv`).

---

### Practical 12 — Event Management CRUD with Poster Upload and File Validation

**What we did:**
- Developed administrative event management module with full CRUD and multi-criteria event filtering.
- Implemented server-side file upload pipeline for event poster images with rigorous security validation:
  - True MIME type verification via `finfo_file(FILEINFO_MIME_TYPE)` (`image/jpeg`, `image/png`, `image/webp`).
  - File extension whitelisting (`.jpg`, `.jpeg`, `.png`, `.webp`).
  - Maximum upload size enforcement (max 2MB).
  - Cryptographically safe file renaming (`poster_<timestamp>_<random>.<ext>`) to prevent directory traversal and file overwriting.
- Protected upload storage directory via Apache `.htaccess` (`FilesMatch` blocking PHP execution and disabling directory indexing).
- Automated physical file deletion (`unlink()`) when an event is deleted or its poster is replaced.
- Integrated event status management (`Open`, `Limited Seats`, `Coming Soon`, `Closed`) and category filters (`Technical`, `Sports`, `Cultural`, `Career`).
- Automated activity auditing into `audit_logs` for event creation, updates, and removals.

**What was new in this practical:**
- Handling `multipart/form-data` uploads in PHP (`$_FILES`) with error code inspection.
- MIME type sniffing via `finfo_file` to block disguised executable scripts.
- Apache `.htaccess` directory hardening against remote code execution.

---

### Practical 13 — PHP JSON API and AJAX/Fetch-Based CRUD without Page Reload

**What we did:**
- Built a RESTful PHP JSON API (`api.php`) supporting `GET`, `POST`, `PUT`, and `DELETE` requests.
- Implemented standard JSON response formatting with appropriate HTTP status codes (`200 OK`, `201 Created`, `400 Bad Request`, `404 Not Found`, `500 Server Error`).
- Created an asynchronous Single Page Application (SPA) frontend consuming the JSON API using the browser `fetch()` API.
- Implemented **debounced search** (Intermediate Extension): delays query dispatch by 300ms as the user types to prevent excessive server requests.
- Implemented asynchronous modal CRUD: Add Event and Edit Event modals dynamically post JSON payloads and update the DOM in real-time without page reloads.
- Built asynchronous Delete Event with interactive confirmation dialog and CSS transition exit animation.
- Created real-time toast notification system providing visual success and error feedback.

**What was new in this practical:**
- Designing and implementing RESTful JSON API endpoints in PHP (`php://input`, `json_decode`, `json_encode`).
- Replacing multi-page navigation and form refreshes with single-page Fetch API calls.
- Frontend search debouncing with JavaScript timers.

---

### Practical 14 — Admin Dashboard with Role-Based Access Control, Dynamic Menu, and Audit Log

**What we did:**
- Developed a comprehensive administrative dashboard restricted to users with the `admin` role.
- Implemented **Role-Based Access Control (RBAC)** gatekeeping: unauthorized users (Guests or Students) receive a styled Access Denied alert with quick role-switching simulation for testing.
- Built a **Dynamic Role-Based Menu**: navigation menu items adapt dynamically based on whether the logged-in user is an Admin, Student, or Guest.
- Displayed aggregated real-time database KPIs (Total Users, Enrolled Students, Active/Inactive Accounts, Published Events, Total Registrations).
- Implemented **Active/Inactive User Status Toggle** (Intermediate Extension) with immediate database persistence and audit trail generation.
- Created a pure HTML5/CSS **Registration Analytics Bar Chart** (Advanced Extension) dynamically calculating and visualizing event registrations across campus categories (Technical, Sports, Cultural, Career).
- Integrated a live System Security Audit Log stream tracking the latest 10 administrative actions.

**What was new in this practical:**
- Implementing server-side RBAC authorization gates across sensitive admin features.
- Dynamic navigation rendering conditioned on session role values.
- Generating pure CSS/HTML responsive data visualizations without heavy third-party charting libraries.

---

### Practical 15 — Final Integration, Testing, Documentation, and Local Deployment

**What we did:**
- Integrated all 15 semester practical modules into a single unified StudentHub portal.
- Created an interactive **Integration & Diagnostics Hub** (`Practical15/index.php`):
  - Live server runtime health inspection (PHP version, `mysqli`, `pdo_mysql`, `session`, `fileinfo`, `json` extensions).
  - Live relational database health check validating connectivity and record counts across all 6 database tables.
  - Verification of directory write permissions and `.htaccess` execution shields.
- Created the **Practical Master Matrix**: an interactive visual index linking all 15 practicals with module category, technologies, and instant launch buttons.
- Conducted comprehensive security and accessibility audits (WCAG 2.1 AA accessibility, SQL injection defense, CSRF tokens, session fixation defense, remote code execution protection).
- Created a production-ready **Apache Virtual Host configuration** (`apache_vhost.conf`) for local deployment under `http://studenthub.local`.

**What was new in this practical:**
- Full-stack project unification linking HTML5/CSS/JS frontend with PHP/MySQL backend architecture.
- Automated server diagnostics and database integrity verification.
- Virtual host configuration for Apache in XAMPP.

---

## Technology Stack

| Technology | Purpose |
|-----------|---------|
| HTML5 | Page structure, semantic elements, forms, tables |
| CSS3 | Responsive layout (Grid + Flexbox), dark mode, animations |
| Vanilla JavaScript | DOM manipulation, Fetch API, form validation, localStorage |
| JSON | External static data store for events, students, FAQs, and REST API payloads |
| PHP 8.x | Server-side execution, form handling, session security, database operations, file uploads, JSON REST APIs |
| MySQL / MariaDB | Relational database management system |
| PDO & MySQLi | Secure database abstraction layers with prepared statements |
| Apache (XAMPP) | Local web server environment and virtual host hosting |
| Git & GitHub | Version control and lab submission |

---

## How to Run

### Frontend Pages (Practicals 1–6)
1. Serve the workspace folder with a local HTTP server (e.g., **VS Code Live Server** extension) so `fetch()` can read JSON files without CORS restriction.
2. Open `http://127.0.0.1:5500/index.html` in any browser.

### Backend PHP & Database Pages (Practicals 7–15)
1. Start **XAMPP Control Panel** and turn on **Apache** and **MySQL** services.
2. Ensure the PHP practical folders reside in `C:\xampp\htdocs\`:
   - `C:\xampp\htdocs\Practical7\`
   - `C:\xampp\htdocs\Practical8\`
   - `C:\xampp\htdocs\Practical9\`
   - `C:\xampp\htdocs\Practical10\`
   - `C:\xampp\htdocs\Practical11\`
   - `C:\xampp\htdocs\Practical12\`
   - `C:\xampp\htdocs\Practical13\`
   - `C:\xampp\htdocs\Practical14\`
   - `C:\xampp\htdocs\Practical15\`
3. Import database schema and seeds into MySQL via phpMyAdmin or MySQL CLI:
   - Run `C:\xampp\htdocs\Practical8\studenthub_schema.sql`
   - Run `C:\xampp\htdocs\Practical8\studenthub_seed.sql`
4. Access backend practicals in your browser:
   - **Practical 7:** `http://localhost/Practical7/index.php`
   - **Practical 8:** `http://localhost/Practical8/index.php`
   - **Practical 9:** `http://localhost/Practical9/index.php`
   - **Practical 10:** `http://localhost/Practical10/index.php`
   - **Practical 11:** `http://localhost/Practical11/index.php`
   - **Practical 12:** `http://localhost/Practical12/index.php`
   - **Practical 13:** `http://localhost/Practical13/index.php`
   - **Practical 14:** `http://localhost/Practical14/index.php`
   - **Practical 15:** `http://localhost/Practical15/index.php`

---

*© 2026 RUDRA MODI (25DCS058) — CHARUSAT DEPSTAR, ITUE203 Web Development Frameworks*
