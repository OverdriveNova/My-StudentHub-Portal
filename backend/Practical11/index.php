<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$dbPath = file_exists(__DIR__ . '/db.php') ? __DIR__ . '/db.php' : __DIR__ . '/../Practical8/db.php';
require_once $dbPath;

$flashMessage = '';
$flashType = '';

if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $search = trim($_GET['search'] ?? '');
    $filterCourse = trim($_GET['course'] ?? '');
    $filterSem = (int)($_GET['semester'] ?? 0);
    $filterStatus = trim($_GET['status'] ?? '');

    $sql = "
        SELECT s.id, s.enrollment_no, u.name, u.email, s.course, s.semester, s.year, s.mobile, s.cgpa, s.dob, u.status, s.created_at
        FROM students s
        JOIN users u ON u.id = s.user_id
        WHERE 1=1
    ";
    $types = "";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR s.enrollment_no LIKE ? OR s.mobile LIKE ?)";
        $searchWild = '%' . $search . '%';
        $types .= "ssss";
        $params[] = $searchWild;
        $params[] = $searchWild;
        $params[] = $searchWild;
        $params[] = $searchWild;
    }
    if ($filterCourse !== '') {
        $sql .= " AND s.course = ?";
        $types .= "s";
        $params[] = $filterCourse;
    }
    if ($filterSem > 0) {
        $sql .= " AND s.semester = ?";
        $types .= "i";
        $params[] = $filterSem;
    }
    if ($filterStatus !== '') {
        $sql .= " AND u.status = ?";
        $types .= "s";
        $params[] = $filterStatus;
    }

    $sql .= " ORDER BY s.id DESC";

    $stmt = $mysqli->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=students_export_' . date('Ymd_His') . '.csv');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Record ID', 'Enrollment No', 'Full Name', 'Email Address', 'Course', 'Semester', 'Year', 'Mobile', 'CGPA', 'Date of Birth', 'Account Status', 'Created At']);

    while ($row = $result->fetch_assoc()) {
        fputcsv($out, [
            $row['id'],
            $row['enrollment_no'],
            $row['name'],
            $row['email'],
            $row['course'],
            $row['semester'],
            $row['year'],
            $row['mobile'],
            $row['cgpa'],
            $row['dob'] ?? '',
            $row['status'],
            $row['created_at']
        ]);
    }
    fclose($out);
    $stmt->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $flashMessage = 'CSRF validation failed. Action aborted.';
        $flashType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
            $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
            $enrollment = strtoupper(trim(filter_input(INPUT_POST, 'enrollment_no', FILTER_SANITIZE_SPECIAL_CHARS) ?? ''));
            $course = trim(filter_input(INPUT_POST, 'course', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
            $semester = (int)($_POST['semester'] ?? 1);
            $year = (int)ceil($semester / 2);
            $mobile = trim(filter_input(INPUT_POST, 'mobile', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
            $cgpa = (float)($_POST['cgpa'] ?? 0.0);
            $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;

            if (empty($name) || empty($email) || empty($enrollment) || empty($course) || empty($mobile)) {
                $flashMessage = 'All required fields must be filled.';
                $flashType = 'error';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $flashMessage = 'Please provide a valid institutional email address.';
                $flashType = 'error';
            } else {
                $chkU = $mysqli->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $chkU->bind_param("s", $email);
                $chkU->execute();
                if ($chkU->get_result()->num_rows > 0) {
                    $flashMessage = 'A user account with this email address already exists.';
                    $flashType = 'error';
                } else {
                    $chkS = $mysqli->prepare("SELECT id FROM students WHERE enrollment_no = ? LIMIT 1");
                    $chkS->bind_param("s", $enrollment);
                    $chkS->execute();
                    if ($chkS->get_result()->num_rows > 0) {
                        $flashMessage = 'A student record with this enrollment number already exists.';
                        $flashType = 'error';
                    } else {
                        $defaultPassword = password_hash('Student@123', PASSWORD_BCRYPT, ['cost' => 12]);
                        $role = 'student';
                        $status = 'active';

                        $insU = $mysqli->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
                        $insU->bind_param("sssss", $name, $email, $defaultPassword, $role, $status);

                        if ($insU->execute()) {
                            $newUserId = $mysqli->insert_id;
                            $insU->close();

                            $insS = $mysqli->prepare("INSERT INTO students (user_id, enrollment_no, course, semester, year, mobile, cgpa, dob) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                            $insS->bind_param("issiisds", $newUserId, $enrollment, $course, $semester, $year, $mobile, $cgpa, $dob);

                            if ($insS->execute()) {
                                $insS->close();

                                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                                $logS = $mysqli->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, 'STUDENT_CREATED', ?, ?)");
                                if ($logS) {
                                    $desc = "Student record created for {$name} ({$enrollment})";
                                    $logS->bind_param("iss", $newUserId, $desc, $ip);
                                    $logS->execute();
                                    $logS->close();
                                }

                                $flashMessage = "Student {$name} ({$enrollment}) created successfully!";
                                $flashType = 'success';
                            } else {
                                $flashMessage = 'Failed to insert student profile: ' . htmlspecialchars($mysqli->error);
                                $flashType = 'error';
                            }
                        } else {
                            $flashMessage = 'Failed to create user record: ' . htmlspecialchars($mysqli->error);
                            $flashType = 'error';
                        }
                    }
                    $chkS->close();
                }
                $chkU->close();
            }
        } elseif ($action === 'update') {
            $studentId = (int)($_POST['student_id'] ?? 0);
            $userId = (int)($_POST['user_id'] ?? 0);
            $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
            $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
            $course = trim(filter_input(INPUT_POST, 'course', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
            $semester = (int)($_POST['semester'] ?? 1);
            $year = (int)ceil($semester / 2);
            $mobile = trim(filter_input(INPUT_POST, 'mobile', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
            $cgpa = (float)($_POST['cgpa'] ?? 0.0);
            $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
            $status = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'active';

            if ($studentId <= 0 || $userId <= 0 || empty($name) || empty($email)) {
                $flashMessage = 'Invalid update parameters.';
                $flashType = 'error';
            } else {
                $updU = $mysqli->prepare("UPDATE users SET name = ?, email = ?, status = ? WHERE id = ?");
                $updU->bind_param("sssi", $name, $email, $status, $userId);
                $updU->execute();
                $updU->close();

                $updS = $mysqli->prepare("UPDATE students SET course = ?, semester = ?, year = ?, mobile = ?, cgpa = ?, dob = ? WHERE id = ?");
                $updS->bind_param("siisdsi", $course, $semester, $year, $mobile, $cgpa, $dob, $studentId);
                $updS->execute();
                $updS->close();

                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $logS = $mysqli->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, 'STUDENT_UPDATED', ?, ?)");
                if ($logS) {
                    $desc = "Student ID {$studentId} profile updated";
                    $logS->bind_param("iss", $userId, $desc, $ip);
                    $logS->execute();
                    $logS->close();
                }

                $flashMessage = "Student #{$studentId} updated successfully!";
                $flashType = 'success';
            }
        } elseif ($action === 'toggle_status') {
            $userId = (int)($_POST['user_id'] ?? 0);
            if ($userId > 0) {
                $tog = $mysqli->prepare("UPDATE users SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
                $tog->bind_param("i", $userId);
                $tog->execute();
                $tog->close();

                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $logS = $mysqli->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, 'STUDENT_STATUS_TOGGLED', 'Account active status toggled', ?)");
                if ($logS) {
                    $logS->bind_param("is", $userId, $ip);
                    $logS->execute();
                    $logS->close();
                }

                $flashMessage = "Student account status toggled successfully!";
                $flashType = 'success';
            }
        } elseif ($action === 'delete') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $studentId = (int)($_POST['student_id'] ?? 0);

            if ($userId > 0) {
                $del = $mysqli->prepare("DELETE FROM users WHERE id = ?");
                $del->bind_param("i", $userId);
                if ($del->execute()) {
                    $del->close();

                    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                    $logS = $mysqli->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (NULL, 'STUDENT_DELETED', ?, ?)");
                    if ($logS) {
                        $desc = "Permanently deleted student ID {$studentId} / User ID {$userId}";
                        $logS->bind_param("ss", $desc, $ip);
                        $logS->execute();
                        $logS->close();
                    }

                    $flashMessage = "Student record #{$studentId} permanently deleted.";
                    $flashType = 'info';
                } else {
                    $flashMessage = 'Failed to delete record: ' . htmlspecialchars($mysqli->error);
                    $flashType = 'error';
                }
            }
        }
    }
}

$search = trim($_GET['search'] ?? '');
$filterCourse = trim($_GET['course'] ?? '');
$filterSem = (int)($_GET['semester'] ?? 0);
$filterStatus = trim($_GET['status'] ?? '');

$sql = "
    SELECT s.id AS student_id, s.user_id, s.enrollment_no, s.course, s.semester, s.year,
           s.mobile, s.cgpa, s.dob, s.created_at, u.name, u.email, u.status
    FROM students s
    JOIN users u ON u.id = s.user_id
    WHERE 1=1
";
$types = "";
$params = [];

if ($search !== '') {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR s.enrollment_no LIKE ? OR s.mobile LIKE ?)";
    $searchWild = '%' . $search . '%';
    $types .= "ssss";
    $params[] = $searchWild;
    $params[] = $searchWild;
    $params[] = $searchWild;
    $params[] = $searchWild;
}
if ($filterCourse !== '') {
    $sql .= " AND s.course = ?";
    $types .= "s";
    $params[] = $filterCourse;
}
if ($filterSem > 0) {
    $sql .= " AND s.semester = ?";
    $types .= "i";
    $params[] = $filterSem;
}
if ($filterStatus !== '') {
    $sql .= " AND u.status = ?";
    $types .= "s";
    $params[] = $filterStatus;
}

$sql .= " ORDER BY s.id DESC";

$stmt = $mysqli->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$statTotal = count($students);
$statActive = 0;
$totalCgpa = 0.0;
foreach ($students as $st) {
    if ($st['status'] === 'active') $statActive++;
    $totalCgpa += (float)$st['cgpa'];
}
$avgCgpa = $statTotal > 0 ? number_format($totalCgpa / $statTotal, 2) : '0.00';

$csrf = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Practical 11 — Student Management CRUD | StudentHub</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #0b0f19;
  --surface: #131b2e;
  --surface2: #1c2640;
  --surface3: #253354;
  --accent: #6366f1;
  --accent-glow: rgba(99, 102, 241, 0.25);
  --teal: #14b8a6;
  --danger: #ef4444;
  --success: #22c55e;
  --warning: #f59e0b;
  --text: #f8fafc;
  --text-muted: #94a3b8;
  --border: #283553;
  --radius: 12px;
  --mono: 'JetBrains Mono', monospace;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Inter', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  line-height: 1.5;
}
.header {
  background: linear-gradient(135deg, #0d9488 0%, #0284c7 100%);
  padding: 1.25rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}
.header-brand {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.header-brand h1 {
  font-size: 1.25rem;
  font-weight: 700;
  color: #ffffff;
}
.header-badge {
  background: rgba(255, 255, 255, 0.2);
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}
.header-nav {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.header-nav a {
  color: #ffffff;
  text-decoration: none;
  font-size: 0.85rem;
  padding: 0.4rem 0.8rem;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.12);
  transition: all 0.2s;
}
.header-nav a:hover {
  background: rgba(255, 255, 255, 0.25);
}
.container {
  max-width: 1300px;
  margin: 2rem auto;
  padding: 0 1.5rem;
}
.alert {
  padding: 1rem 1.25rem;
  border-radius: var(--radius);
  margin-bottom: 1.5rem;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.alert-success { background: rgba(34, 197, 94, 0.12); border: 1px solid rgba(34, 197, 94, 0.3); color: #4ade80; }
.alert-error   { background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; }
.alert-info    { background: rgba(99, 102, 241, 0.12); border: 1px solid rgba(99, 102, 241, 0.3); color: #a5b4fc; }

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.25rem;
  margin-bottom: 1.75rem;
}
.stat-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}
.stat-label {
  font-size: 0.75rem;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--text);
}

.card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.5rem;
  margin-bottom: 1.75rem;
}

.toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}
.filter-form {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
}
.input-control {
  padding: 0.6rem 0.85rem;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 8px;
  color: var(--text);
  font-size: 0.85rem;
  font-family: inherit;
  transition: all 0.2s;
}
.input-control:focus {
  outline: none;
  border-color: var(--teal);
}
.actions-group {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  padding: 0.6rem 1rem;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.2s;
}
.btn-primary { background: #0d9488; color: #ffffff; }
.btn-primary:hover { background: #0f766e; }
.btn-accent  { background: #6366f1; color: #ffffff; }
.btn-accent:hover { background: #4f46e5; }
.btn-secondary { background: var(--surface2); border: 1px solid var(--border); color: var(--text); }
.btn-secondary:hover { background: var(--surface3); }
.btn-danger { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
.btn-danger:hover { background: rgba(239, 68, 68, 0.3); color: #ffffff; }
.btn-success { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
.btn-success:hover { background: rgba(34, 197, 94, 0.3); }

.table-responsive {
  width: 100%;
  overflow-x: auto;
  border-radius: 8px;
  border: 1px solid var(--border);
}
table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
  text-align: left;
}
th, td {
  padding: 0.85rem 1rem;
  border-bottom: 1px solid var(--border);
}
th {
  background: var(--surface2);
  color: var(--text-muted);
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.72rem;
  letter-spacing: 0.05em;
}
tr:hover td {
  background: rgba(255, 255, 255, 0.02);
}

.badge-status {
  padding: 0.25rem 0.6rem;
  border-radius: 9999px;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
}
.badge-active { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
.badge-inactive { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

.modal {
  display: none;
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(4px);
  z-index: 999;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}
.modal.open {
  display: flex;
}
.modal-content {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  width: 100%;
  max-width: 580px;
  max-height: 90vh;
  overflow-y: auto;
  padding: 1.75rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
  border-bottom: 1px solid var(--border);
  padding-bottom: 0.75rem;
}
.modal-header h3 {
  font-size: 1.1rem;
  font-weight: 600;
}
.modal-close {
  background: none;
  border: none;
  color: var(--text-muted);
  font-size: 1.5rem;
  cursor: pointer;
}
.modal-close:hover { color: var(--text); }
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
.form-group {
  margin-bottom: 1rem;
}
.form-group.full {
  grid-column: 1 / -1;
}
.form-group label {
  display: block;
  font-size: 0.8rem;
  color: var(--text-muted);
  margin-bottom: 0.3rem;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.25rem;
  border-top: 1px solid var(--border);
  padding-top: 1rem;
}
</style>
</head>
<body>

<header class="header">
  <div class="header-brand">
    <h1>StudentHub Portal</h1>
    <span class="header-badge">Practical 11</span>
  </div>
  <nav class="header-nav">
    <a href="../Practical7/index.php">P7: Form CSV</a>
    <a href="../Practical8/index.php">P8: DB Model</a>
    <a href="../Practical9/index.php">P9: Register</a>
    <a href="../Practical10/index.php">P10: Auth</a>
    <a href="../Practical12/index.php">P12: Events</a>
  </nav>
</header>

<main class="container">

  <?php if (!empty($flashMessage)): ?>
    <div class="alert alert-<?= $flashType ?>">
      <span><?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8') ?></span>
      <span style="cursor:pointer;" onclick="this.parentElement.remove();">&times;</span>
    </div>
  <?php endif; ?>

  <div class="stats-grid">
    <div class="stat-card">
      <span class="stat-label">Total Filtered</span>
      <span class="stat-value"><?= $statTotal ?></span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Active Accounts</span>
      <span class="stat-value" style="color:var(--success);"><?= $statActive ?></span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Inactive Accounts</span>
      <span class="stat-value" style="color:var(--danger);"><?= $statTotal - $statActive ?></span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Average CGPA</span>
      <span class="stat-value" style="color:var(--teal);"><?= $avgCgpa ?></span>
    </div>
  </div>

  <div class="card">
    <div class="toolbar">
      <form action="index.php" method="GET" class="filter-form">
        <input type="text" name="search" class="input-control" placeholder="Search name, email, roll..." value="<?= htmlspecialchars($search) ?>" style="min-width: 220px;">

        <select name="course" class="input-control">
          <option value="">All Programs</option>
          <option value="CSE" <?= $filterCourse === 'CSE' ? 'selected' : '' ?>>CSE</option>
          <option value="IT"  <?= $filterCourse === 'IT'  ? 'selected' : '' ?>>IT</option>
          <option value="ECE" <?= $filterCourse === 'ECE' ? 'selected' : '' ?>>ECE</option>
          <option value="ME"  <?= $filterCourse === 'ME'  ? 'selected' : '' ?>>ME</option>
          <option value="CE"  <?= $filterCourse === 'CE'  ? 'selected' : '' ?>>CE</option>
        </select>

        <select name="semester" class="input-control">
          <option value="0">All Semesters</option>
          <?php for ($i = 1; $i <= 8; $i++): ?>
            <option value="<?= $i ?>" <?= $filterSem === $i ? 'selected' : '' ?>>Sem <?= $i ?></option>
          <?php endfor; ?>
        </select>

        <select name="status" class="input-control">
          <option value="">All Statuses</option>
          <option value="active"   <?= $filterStatus === 'active'   ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>

        <button type="submit" class="btn btn-secondary">Apply Filter</button>
        <?php if ($search !== '' || $filterCourse !== '' || $filterSem > 0 || $filterStatus !== ''): ?>
          <a href="index.php" class="btn btn-secondary" style="color:var(--text-muted);">Reset</a>
        <?php endif; ?>
      </form>

      <div class="actions-group">
        <a href="index.php?action=export_csv&search=<?= urlencode($search) ?>&course=<?= urlencode($filterCourse) ?>&semester=<?= $filterSem ?>&status=<?= urlencode($filterStatus) ?>" class="btn btn-secondary">
          Export CSV
        </a>
        <button class="btn btn-primary" onclick="openAddModal()">
          + Add Student
        </button>
      </div>
    </div>
  </div>

  <div class="card" style="padding: 0; overflow: hidden;">
    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Enrollment</th>
            <th>Student Name</th>
            <th>Email</th>
            <th>Program</th>
            <th>Sem</th>
            <th>Mobile</th>
            <th>CGPA</th>
            <th>Status</th>
            <th style="text-align: right;">Operations</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($students)): ?>
            <tr>
              <td colspan="10" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                No matching student records located.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($students as $s): ?>
              <tr>
                <td style="font-family:var(--mono); color:var(--text-muted); font-size:0.8rem;">#<?= (int)$s['student_id'] ?></td>
                <td><strong style="font-family:var(--mono); color:var(--teal);"><?= htmlspecialchars($s['enrollment_no']) ?></strong></td>
                <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                <td style="color:var(--text-muted);"><?= htmlspecialchars($s['email']) ?></td>
                <td><span class="badge-status badge-active" style="background:rgba(99,102,241,0.15); color:#a5b4fc; border-color:rgba(99,102,241,0.3);"><?= htmlspecialchars($s['course']) ?></span></td>
                <td>Sem <?= (int)$s['semester'] ?></td>
                <td style="font-family:var(--mono);"><?= htmlspecialchars($s['mobile']) ?></td>
                <td><strong style="color:var(--success);"><?= htmlspecialchars($s['cgpa']) ?></strong></td>
                <td>
                  <span class="badge-status <?= $s['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                    <?= htmlspecialchars($s['status']) ?>
                  </span>
                </td>
                <td style="text-align: right; white-space: nowrap;">
                  <button class="btn btn-secondary" style="padding:0.35rem 0.65rem; font-size:0.75rem;" onclick='openEditModal(<?= json_encode($s, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>Edit</button>

                  <form action="index.php" method="POST" style="display:inline-block;">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="user_id" value="<?= (int)$s['user_id'] ?>">
                    <button type="submit" class="btn btn-secondary" style="padding:0.35rem 0.65rem; font-size:0.75rem;">
                      <?= $s['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                    </button>
                  </form>

                  <button class="btn btn-danger" style="padding:0.35rem 0.65rem; font-size:0.75rem;" onclick="confirmDelete(<?= (int)$s['user_id'] ?>, <?= (int)$s['student_id'] ?>, '<?= htmlspecialchars(addslashes($s['name'])) ?>')">Delete</button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>

<div class="modal" id="addModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Add New Student Record</h3>
      <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
    </div>
    <form action="index.php" method="POST">
      <input type="hidden" name="action" value="create">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

      <div class="form-grid">
        <div class="form-group full">
          <label>Full Student Name *</label>
          <input type="text" name="name" class="input-control" style="width:100%;" required placeholder="e.g. Aryan Patel">
        </div>

        <div class="form-group">
          <label>Institutional Email *</label>
          <input type="email" name="email" class="input-control" style="width:100%;" required placeholder="aryan@charusat.edu.in">
        </div>

        <div class="form-group">
          <label>Enrollment Number *</label>
          <input type="text" name="enrollment_no" class="input-control" style="width:100%;" required placeholder="25DCS101" pattern="^\d{2}D[A-Z]{2,3}\d{3}$">
        </div>

        <div class="form-group">
          <label>Program / Course *</label>
          <select name="course" class="input-control" style="width:100%;" required>
            <option value="CSE">CSE</option>
            <option value="IT">IT</option>
            <option value="ECE">ECE</option>
            <option value="ME">ME</option>
            <option value="CE">CE</option>
          </select>
        </div>

        <div class="form-group">
          <label>Semester (1-8) *</label>
          <input type="number" name="semester" min="1" max="8" value="3" class="input-control" style="width:100%;" required>
        </div>

        <div class="form-group">
          <label>Mobile Number *</label>
          <input type="text" name="mobile" class="input-control" style="width:100%;" required placeholder="9876543210" pattern="^[6-9]\d{9}$">
        </div>

        <div class="form-group">
          <label>Current CGPA</label>
          <input type="number" step="0.01" min="0" max="10" name="cgpa" value="8.00" class="input-control" style="width:100%;">
        </div>

        <div class="form-group full">
          <label>Date of Birth</label>
          <input type="date" name="dob" class="input-control" style="width:100%;">
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Student</button>
      </div>
    </form>
  </div>
</div>

<div class="modal" id="editModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Edit Student Details</h3>
      <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
    </div>
    <form action="index.php" method="POST">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="student_id" id="editStudentId">
      <input type="hidden" name="user_id" id="editUserId">

      <div class="form-grid">
        <div class="form-group full">
          <label>Full Student Name *</label>
          <input type="text" name="name" id="editName" class="input-control" style="width:100%;" required>
        </div>

        <div class="form-group">
          <label>Institutional Email *</label>
          <input type="email" name="email" id="editEmail" class="input-control" style="width:100%;" required>
        </div>

        <div class="form-group">
          <label>Enrollment No (Read Only)</label>
          <input type="text" id="editEnrollment" class="input-control" style="width:100%; opacity:0.6;" readonly>
        </div>

        <div class="form-group">
          <label>Program / Course *</label>
          <select name="course" id="editCourse" class="input-control" style="width:100%;" required>
            <option value="CSE">CSE</option>
            <option value="IT">IT</option>
            <option value="ECE">ECE</option>
            <option value="ME">ME</option>
            <option value="CE">CE</option>
          </select>
        </div>

        <div class="form-group">
          <label>Semester (1-8) *</label>
          <input type="number" name="semester" id="editSemester" min="1" max="8" class="input-control" style="width:100%;" required>
        </div>

        <div class="form-group">
          <label>Mobile Number *</label>
          <input type="text" name="mobile" id="editMobile" class="input-control" style="width:100%;" required pattern="^[6-9]\d{9}$">
        </div>

        <div class="form-group">
          <label>Cumulative CGPA</label>
          <input type="number" step="0.01" min="0" max="10" name="cgpa" id="editCgpa" class="input-control" style="width:100%;">
        </div>

        <div class="form-group">
          <label>Account Status</label>
          <select name="status" id="editStatus" class="input-control" style="width:100%;">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>

        <div class="form-group">
          <label>Date of Birth</label>
          <input type="date" name="dob" id="editDob" class="input-control" style="width:100%;">
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Student Record</button>
      </div>
    </form>
  </div>
</div>

<div class="modal" id="deleteModal">
  <div class="modal-content" style="max-width:440px;">
    <div class="modal-header">
      <h3 style="color:var(--danger);">Confirm Permanent Deletion</h3>
      <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
    </div>
    <form action="index.php" method="POST">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="student_id" id="delStudentId">
      <input type="hidden" name="user_id" id="delUserId">

      <p style="font-size:0.9rem; color:var(--text-muted); margin-bottom:1.25rem;">
        Are you certain you wish to permanently delete student <strong id="delStudentName" style="color:var(--text);"></strong>? This will remove all associated registrations and cannot be undone.
      </p>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
        <button type="submit" class="btn btn-danger" style="background:#dc2626; color:#ffffff;">Yes, Delete Record</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddModal() {
  document.getElementById('addModal').classList.add('open');
}

function openEditModal(student) {
  document.getElementById('editStudentId').value = student.student_id;
  document.getElementById('editUserId').value = student.user_id;
  document.getElementById('editName').value = student.name;
  document.getElementById('editEmail').value = student.email;
  document.getElementById('editEnrollment').value = student.enrollment_no;
  document.getElementById('editCourse').value = student.course;
  document.getElementById('editSemester').value = student.semester;
  document.getElementById('editMobile').value = student.mobile;
  document.getElementById('editCgpa').value = student.cgpa;
  document.getElementById('editStatus').value = student.status;
  document.getElementById('editDob').value = student.dob || '';
  document.getElementById('editModal').classList.add('open');
}

function confirmDelete(userId, studentId, studentName) {
  document.getElementById('delUserId').value = userId;
  document.getElementById('delStudentId').value = studentId;
  document.getElementById('delStudentName').textContent = studentName;
  document.getElementById('deleteModal').classList.add('open');
}

function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}

window.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal')) {
    e.target.classList.remove('open');
  }
});
</script>

</body>
</html>
