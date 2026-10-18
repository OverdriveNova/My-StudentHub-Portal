<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$dbPath = file_exists(__DIR__ . '/db.php') ? __DIR__ . '/db.php' : __DIR__ . '/../Practical8/db.php';
require_once $dbPath;

$mysqli->query("
    CREATE TABLE IF NOT EXISTS remember_tokens (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        token_hash VARCHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_rem_user (user_id),
        INDEX idx_rem_hash (token_hash)
    ) ENGINE=InnoDB
");

$checkCol = $mysqli->query("SHOW COLUMNS FROM users LIKE 'last_login'");
if ($checkCol && $checkCol->num_rows === 0) {
    $mysqli->query("ALTER TABLE users ADD COLUMN last_login DATETIME NULL AFTER status");
}

$timeoutDuration = 900;
$flashMessage = '';
$flashType = '';

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'logged_out') {
        $flashMessage = 'You have been safely logged out.';
        $flashType = 'info';
    } elseif ($_GET['status'] === 'timeout') {
        $flashMessage = 'Your session expired due to 15 minutes of inactivity. Please log in again.';
        $flashType = 'warning';
    } elseif ($_GET['status'] === 'unauthorized') {
        $flashMessage = 'Access denied. Please log in with appropriate credentials to access that section.';
        $flashType = 'error';
    } elseif ($_GET['status'] === 'logged_in') {
        $flashMessage = 'Authentication successful! Session created with role-based privileges.';
        $flashType = 'success';
    }
}

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeoutDuration)) {
        $uid = (int)$_SESSION['user_id'];
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $logStmt = $mysqli->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, 'SESSION_TIMEOUT', 'Session timed out automatically', ?)");
        if ($logStmt) {
            $logStmt->bind_param("is", $uid, $ip);
            $logStmt->execute();
            $logStmt->close();
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        header('Location: index.php?status=timeout');
        exit;
    }
    $_SESSION['last_activity'] = time();
} elseif (!empty($_COOKIE['studenthub_remember'])) {
    $cookieParts = explode(':', $_COOKIE['studenthub_remember']);
    if (count($cookieParts) === 2) {
        $cookieUserId = (int)$cookieParts[0];
        $cookieToken = $cookieParts[1];
        $hashedToken = hash('sha256', $cookieToken);

        $chkStmt = $mysqli->prepare("SELECT user_id FROM remember_tokens WHERE user_id = ? AND token_hash = ? AND expires_at > NOW() LIMIT 1");
        if ($chkStmt) {
            $chkStmt->bind_param("is", $cookieUserId, $hashedToken);
            $chkStmt->execute();
            $chkRes = $chkStmt->get_result();
            if ($chkRow = $chkRes->fetch_assoc()) {
                $uStmt = $mysqli->prepare("SELECT id, name, email, role, status FROM users WHERE id = ? LIMIT 1");
                $uStmt->bind_param("i", $cookieUserId);
                $uStmt->execute();
                $user = $uStmt->get_result()->fetch_assoc();
                $uStmt->close();

                if ($user && $user['status'] === 'active') {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['last_activity'] = time();
                    $_SESSION['login_time'] = time();

                    $updStmt = $mysqli->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $updStmt->bind_param("i", $user['id']);
                    $updStmt->execute();
                    $updStmt->close();

                    header('Location: index.php?status=logged_in');
                    exit;
                }
            }
            $chkStmt->close();
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    if (isset($_SESSION['user_id'])) {
        $uid = (int)$_SESSION['user_id'];
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $logStmt = $mysqli->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, 'USER_LOGOUT', 'User logged out manually', ?)");
        if ($logStmt) {
            $logStmt->bind_param("is", $uid, $ip);
            $logStmt->execute();
            $logStmt->close();
        }
    }

    if (!empty($_COOKIE['studenthub_remember'])) {
        $cookieParts = explode(':', $_COOKIE['studenthub_remember']);
        if (count($cookieParts) === 2) {
            $cookieUserId = (int)$cookieParts[0];
            $hashedToken = hash('sha256', $cookieParts[1]);
            $delStmt = $mysqli->prepare("DELETE FROM remember_tokens WHERE user_id = ? AND token_hash = ?");
            if ($delStmt) {
                $delStmt->bind_param("is", $cookieUserId, $hashedToken);
                $delStmt->execute();
                $delStmt->close();
            }
        }
        setcookie('studenthub_remember', '', time() - 3600, '/', '', false, true);
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: index.php?status=logged_out');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $flashMessage = 'Security validation failed: Invalid CSRF token.';
        $flashType = 'error';
    } else {
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
        $password = trim($_POST['password'] ?? '');
        $rememberMe = isset($_POST['remember_me']);

        if (empty($email) || empty($password)) {
            $flashMessage = 'Please enter both your email address and password.';
            $flashType = 'error';
        } else {
            $stmt = $mysqli->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $res = $stmt->get_result();
                $user = $res->fetch_assoc();
                $stmt->close();

                if ($user && password_verify($password, $user['password'])) {
                    if ($user['status'] !== 'active') {
                        $flashMessage = 'Account deactivated. Please contact the administrator.';
                        $flashType = 'error';
                    } else {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = (int)$user['id'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['name'] = $user['name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['last_activity'] = time();
                        $_SESSION['login_time'] = time();
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                        $updStmt = $mysqli->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                        if ($updStmt) {
                            $updStmt->bind_param("i", $user['id']);
                            $updStmt->execute();
                            $updStmt->close();
                        }

                        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                        $logStmt = $mysqli->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, 'USER_LOGIN', 'Logged in successfully via password_verify()', ?)");
                        if ($logStmt) {
                            $logStmt->bind_param("is", $user['id'], $ip);
                            $logStmt->execute();
                            $logStmt->close();
                        }

                        if ($rememberMe) {
                            $rawToken = bin2hex(random_bytes(32));
                            $hashedToken = hash('sha256', $rawToken);
                            $expiresAt = date('Y-m-d H:i:s', time() + (86400 * 30));

                            $remStmt = $mysqli->prepare("INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
                            if ($remStmt) {
                                $remStmt->bind_param("iss", $user['id'], $hashedToken, $expiresAt);
                                $remStmt->execute();
                                $remStmt->close();
                            }

                            setcookie('studenthub_remember', $user['id'] . ':' . $rawToken, time() + (86400 * 30), '/', '', false, true);
                        }

                        header('Location: index.php?status=logged_in');
                        exit;
                    }
                } else {
                    $flashMessage = 'Invalid credentials. Please verify your email and password.';
                    $flashType = 'error';

                    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                    $dummyUid = null;
                    $failStmt = $mysqli->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, 'LOGIN_FAILED', ?, ?)");
                    if ($failStmt) {
                        $desc = 'Failed login attempt for: ' . substr($email, 0, 50);
                        $failStmt->bind_param("iss", $dummyUid, $desc, $ip);
                        $failStmt->execute();
                        $failStmt->close();
                    }
                }
            } else {
                $flashMessage = 'Database query failed: ' . htmlspecialchars($mysqli->error);
                $flashType = 'error';
            }
        }
    }
}

$currentUser = null;
$studentDetails = null;
$registeredEvents = [];
$adminStats = [];
$recentLogs = [];

if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $uStmt = $mysqli->prepare("SELECT id, name, email, role, status, last_login, created_at FROM users WHERE id = ? LIMIT 1");
    if ($uStmt) {
        $uStmt->bind_param("i", $uid);
        $uStmt->execute();
        $currentUser = $uStmt->get_result()->fetch_assoc();
        $uStmt->close();
    }

    if ($currentUser && $currentUser['role'] === 'student') {
        $stStmt = $mysqli->prepare("SELECT id, enrollment_no, course, semester, year, mobile, cgpa, dob FROM students WHERE user_id = ? LIMIT 1");
        if ($stStmt) {
            $stStmt->bind_param("i", $uid);
            $stStmt->execute();
            $studentDetails = $stStmt->get_result()->fetch_assoc();
            $stStmt->close();
        }

        if ($studentDetails) {
            $regStmt = $mysqli->prepare("
                SELECT e.id, e.title, e.category, e.venue, e.event_date, e.status, r.registered_at
                FROM registrations r
                JOIN events e ON e.id = r.event_id
                WHERE r.student_id = ?
                ORDER BY e.event_date ASC
            ");
            if ($regStmt) {
                $regStmt->bind_param("i", $studentDetails['id']);
                $regStmt->execute();
                $registeredEvents = $regStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $regStmt->close();
            }
        }
    } elseif ($currentUser && $currentUser['role'] === 'admin') {
        $cntU = $mysqli->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'] ?? 0;
        $cntS = $mysqli->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()['total'] ?? 0;
        $cntE = $mysqli->query("SELECT COUNT(*) AS total FROM events")->fetch_assoc()['total'] ?? 0;
        $cntR = $mysqli->query("SELECT COUNT(*) AS total FROM registrations")->fetch_assoc()['total'] ?? 0;
        $adminStats = [
            'users' => $cntU,
            'students' => $cntS,
            'events' => $cntE,
            'registrations' => $cntR
        ];

        $logRes = $mysqli->query("
            SELECT a.id, a.action, a.description, a.ip_address, a.created_at, u.name
            FROM audit_logs a
            LEFT JOIN users u ON u.id = a.user_id
            ORDER BY a.id DESC LIMIT 6
        ");
        if ($logRes) {
            $recentLogs = $logRes->fetch_all(MYSQLI_ASSOC);
        }
    }
}

$csrf = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Practical 10 — Secure Authentication & Sessions | StudentHub</title>
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
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
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
  background: rgba(255, 255, 255, 0.18);
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.05em;
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
  max-width: 1200px;
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
  border: 1px solid transparent;
}
.alert-success { background: rgba(34, 197, 94, 0.12); border-color: rgba(34, 197, 94, 0.3); color: #4ade80; }
.alert-error   { background: rgba(239, 68, 68, 0.12); border-color: rgba(239, 68, 68, 0.3); color: #f87171; }
.alert-warning { background: rgba(245, 158, 11, 0.12); border-color: rgba(245, 158, 11, 0.3); color: #fbbf24; }
.alert-info    { background: rgba(99, 102, 241, 0.12); border-color: rgba(99, 102, 241, 0.3); color: #a5b4fc; }
.grid-login {
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  gap: 1.75rem;
}
@media (max-width: 840px) {
  .grid-login { grid-template-columns: 1fr; }
}
.card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.75rem;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}
.card h2 {
  font-size: 1.15rem;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 1.25rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.form-group {
  margin-bottom: 1.2rem;
}
.form-group label {
  display: block;
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--text-muted);
  margin-bottom: 0.4rem;
}
.form-control {
  width: 100%;
  padding: 0.75rem 1rem;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 8px;
  color: var(--text);
  font-size: 0.9rem;
  font-family: inherit;
  transition: all 0.2s;
}
.form-control:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px var(--accent-glow);
}
.form-check {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  cursor: pointer;
  font-size: 0.85rem;
  color: var(--text-muted);
}
.form-check input {
  width: 1rem;
  height: 1rem;
  accent-color: var(--accent);
  cursor: pointer;
}
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  font-size: 0.9rem;
  font-weight: 600;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
}
.btn-primary {
  width: 100%;
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  color: #ffffff;
  box-shadow: 0 4px 14px var(--accent-glow);
}
.btn-primary:hover {
  opacity: 0.95;
  transform: translateY(-1px);
}
.btn-danger {
  background: #dc2626;
  color: #ffffff;
}
.btn-danger:hover { background: #b91c1c; }
.btn-outline {
  background: transparent;
  border: 1px solid var(--border);
  color: var(--text-muted);
}
.btn-outline:hover {
  background: var(--surface2);
  color: var(--text);
}
.demo-box {
  background: var(--surface2);
  border-radius: 8px;
  padding: 1rem;
  margin-top: 1rem;
}
.demo-box h3 {
  font-size: 0.82rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--teal);
  margin-bottom: 0.75rem;
}
.demo-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  font-size: 0.85rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
.demo-row:last-child { border-bottom: none; }
.fill-btn {
  background: rgba(99, 102, 241, 0.15);
  color: #a5b4fc;
  border: 1px solid rgba(99, 102, 241, 0.3);
  padding: 0.25rem 0.6rem;
  border-radius: 4px;
  font-size: 0.75rem;
  cursor: pointer;
  transition: 0.2s;
}
.fill-btn:hover {
  background: rgba(99, 102, 241, 0.35);
  color: #ffffff;
}
.features-list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}
.feature-item {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
  font-size: 0.85rem;
}
.feature-icon {
  background: rgba(20, 184, 166, 0.15);
  color: var(--teal);
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  flex-shrink: 0;
  margin-top: 2px;
}
.session-bar {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.25rem 1.75rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.75rem;
}
.session-info {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  flex-wrap: wrap;
}
.user-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--accent) 0%, var(--teal) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.1rem;
  color: #ffffff;
}
.user-meta h3 {
  font-size: 1rem;
  font-weight: 600;
}
.user-meta p {
  font-size: 0.8rem;
  color: var(--text-muted);
}
.badge-role {
  font-size: 0.7rem;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  font-weight: 600;
  text-transform: uppercase;
}
.badge-admin { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); }
.badge-student { background: rgba(20, 184, 166, 0.2); color: #2dd4bf; border: 1px solid rgba(20, 184, 166, 0.4); }
.session-timer {
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 0.5rem 1rem;
  font-family: var(--mono);
  font-size: 0.8rem;
  color: var(--warning);
}
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
  gap: 0.4rem;
}
.stat-label {
  font-size: 0.8rem;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--text);
}
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
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  font-size: 0.7rem;
  font-weight: 600;
}
.status-open { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
.status-limited { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
.status-closed { background: rgba(239, 68, 68, 0.2); color: #f87171; }
.quick-links {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-top: 1rem;
}
.quick-link-btn {
  background: var(--surface2);
  color: var(--text);
  border: 1px solid var(--border);
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  text-decoration: none;
  font-size: 0.85rem;
  font-weight: 500;
  transition: all 0.2s;
}
.quick-link-btn:hover {
  background: var(--accent);
  border-color: var(--accent);
  color: #ffffff;
}
</style>
</head>
<body>

<header class="header">
  <div class="header-brand">
    <h1>StudentHub Portal</h1>
    <span class="header-badge">Practical 10</span>
  </div>
  <nav class="header-nav">
    <a href="../Practical7/index.php">P7: Form CSV</a>
    <a href="../Practical8/index.php">P8: DB Model</a>
    <a href="../Practical9/index.php">P9: Register</a>
    <a href="../Practical11/index.php">P11: Students</a>
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

  <?php if (!$currentUser): ?>
    <div class="grid-login">
      <div class="card">
        <h2>Secure Login System</h2>
        <form action="index.php" method="POST" id="loginForm">
          <input type="hidden" name="action" value="login">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

          <div class="form-group">
            <label for="emailInput">Institutional Email Address</label>
            <input type="email" id="emailInput" name="email" class="form-control" required placeholder="name@charusat.ac.in" autocomplete="email">
          </div>

          <div class="form-group">
            <label for="passwordInput">Secret Password</label>
            <input type="password" id="passwordInput" name="password" class="form-control" required placeholder="Enter password" autocomplete="current-password">
          </div>

          <div class="form-group" style="display:flex; justify-content:space-between; align-items:center;">
            <label class="form-check">
              <input type="checkbox" name="remember_me" value="1">
              <span>Remember me for 30 days (Hashed token)</span>
            </label>
          </div>

          <button type="submit" class="btn btn-primary">Authenticate & Create Session</button>
        </form>

        <div class="demo-box">
          <h3>Test Demonstration Credentials</h3>
          <div class="demo-row">
            <div>
              <strong>Admin Account</strong>
              <div style="font-size:0.75rem; color:var(--text-muted);">admin@charusat.ac.in | Admin@123</div>
            </div>
            <button class="fill-btn" onclick="fillForm('admin@charusat.ac.in', 'Admin@123')">Fill Admin</button>
          </div>
          <div class="demo-row">
            <div>
              <strong>Student Account</strong>
              <div style="font-size:0.75rem; color:var(--text-muted);">rudra.modi@charusat.edu.in | Student@123</div>
            </div>
            <button class="fill-btn" onclick="fillForm('rudra.modi@charusat.edu.in', 'Student@123')">Fill Student</button>
          </div>
        </div>
      </div>

      <div class="card">
        <h2>Security Framework Overview</h2>
        <ul class="features-list">
          <li class="feature-item">
            <span class="feature-icon">&#10003;</span>
            <div>
              <strong>Timing-Safe Password Verification</strong>
              <p style="color:var(--text-muted); font-size:0.78rem;">Uses PHP's <code>password_verify()</code> to safely validate bcrypt hashes.</p>
            </div>
          </li>
          <li class="feature-item">
            <span class="feature-icon">&#10003;</span>
            <div>
              <strong>Session ID Regeneration</strong>
              <p style="color:var(--text-muted); font-size:0.78rem;">Calls <code>session_regenerate_id(true)</code> on login to stop session fixation.</p>
            </div>
          </li>
          <li class="feature-item">
            <span class="feature-icon">&#10003;</span>
            <div>
              <strong>Automatic 15-Minute Session Timeout</strong>
              <p style="color:var(--text-muted); font-size:0.78rem;">Tracks activity timestamp and invalidates abandoned sessions.</p>
            </div>
          </li>
          <li class="feature-item">
            <span class="feature-icon">&#10003;</span>
            <div>
              <strong>Database-Backed Remember-Me</strong>
              <p style="color:var(--text-muted); font-size:0.78rem;">Stores cryptographically random SHA-256 tokens in database.</p>
            </div>
          </li>
          <li class="feature-item">
            <span class="feature-icon">&#10003;</span>
            <div>
              <strong>Role-Based Access Control (RBAC)</strong>
              <p style="color:var(--text-muted); font-size:0.78rem;">Separates Student and Admin panels dynamically from MySQL role records.</p>
            </div>
          </li>
          <li class="feature-item">
            <span class="feature-icon">&#10003;</span>
            <div>
              <strong>Security Audit Trail</strong>
              <p style="color:var(--text-muted); font-size:0.78rem;">Records every login, failed attempt, and logout with IP addresses.</p>
            </div>
          </li>
        </ul>
      </div>
    </div>

  <?php else: ?>

    <div class="session-bar">
      <div class="session-info">
        <div class="user-avatar"><?= strtoupper(substr($currentUser['name'], 0, 1)) ?></div>
        <div class="user-meta">
          <div style="display:flex; align-items:center; gap:0.5rem;">
            <h3><?= htmlspecialchars($currentUser['name']) ?></h3>
            <span class="badge-role <?= $currentUser['role'] === 'admin' ? 'badge-admin' : 'badge-student' ?>">
              <?= htmlspecialchars($currentUser['role']) ?>
            </span>
          </div>
          <p><?= htmlspecialchars($currentUser['email']) ?> &bull; Last Login: <?= $currentUser['last_login'] ? htmlspecialchars($currentUser['last_login']) : 'First Session' ?></p>
        </div>
      </div>
      <div style="display:flex; align-items:center; gap:1rem;">
        <div class="session-timer" id="timeoutWidget">
          Inactivity Timeout: <span id="timeLeft">15:00</span>
        </div>
        <a href="index.php?action=logout" class="btn btn-danger" style="padding:0.5rem 1rem; font-size:0.85rem;">Log Out</a>
      </div>
    </div>

    <?php if ($currentUser['role'] === 'admin'): ?>

      <div class="stats-grid">
        <div class="stat-card">
          <span class="stat-label">Total Users</span>
          <span class="stat-value"><?= (int)$adminStats['users'] ?></span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Enrolled Students</span>
          <span class="stat-value"><?= (int)$adminStats['students'] ?></span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Campus Events</span>
          <span class="stat-value"><?= (int)$adminStats['events'] ?></span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Event Registrations</span>
          <span class="stat-value"><?= (int)$adminStats['registrations'] ?></span>
        </div>
      </div>

      <div class="card" style="margin-bottom:1.75rem;">
        <h2>Admin Management Quick Actions</h2>
        <div class="quick-links">
          <a href="../Practical11/index.php" class="quick-link-btn">Manage Students (Practical 11) &rarr;</a>
          <a href="../Practical12/index.php" class="quick-link-btn">Manage Events & Posters (Practical 12) &rarr;</a>
          <a href="../Practical9/index.php" class="quick-link-btn">Register New User (Practical 9) &rarr;</a>
          <a href="../Practical8/index.php" class="quick-link-btn">View Database Schema & Relations (Practical 8) &rarr;</a>
        </div>
      </div>

      <div class="card">
        <h2>Live Security Audit Log (Recent Entries)</h2>
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Log ID</th>
                <th>User / Initiator</th>
                <th>Action Type</th>
                <th>Event Description</th>
                <th>Origin IP</th>
                <th>Recorded At</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recentLogs)): ?>
                <tr><td colspan="6" style="text-align:center; color:var(--text-muted);">No recent logs recorded.</td></tr>
              <?php else: ?>
                <?php foreach ($recentLogs as $log): ?>
                  <tr>
                    <td style="font-family:var(--mono);">#<?= (int)$log['id'] ?></td>
                    <td><strong><?= htmlspecialchars($log['name'] ?? 'System/Guest') ?></strong></td>
                    <td><span class="badge-role badge-student"><?= htmlspecialchars($log['action']) ?></span></td>
                    <td><?= htmlspecialchars($log['description']) ?></td>
                    <td style="font-family:var(--mono); color:var(--text-muted);"><?= htmlspecialchars($log['ip_address']) ?></td>
                    <td style="color:var(--text-muted); font-size:0.75rem;"><?= htmlspecialchars($log['created_at']) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php else: ?>

      <div style="display:grid; grid-template-columns: 1fr 1.5fr; gap:1.75rem; margin-bottom:1.75rem;">
        <div class="card">
          <h2>Student Profile Card</h2>
          <?php if ($studentDetails): ?>
            <div style="display:flex; flex-direction:column; gap:0.75rem; font-size:0.9rem;">
              <div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border); padding-bottom:0.5rem;">
                <span style="color:var(--text-muted);">Enrollment No:</span>
                <strong style="font-family:var(--mono); color:var(--teal);"><?= htmlspecialchars($studentDetails['enrollment_no']) ?></strong>
              </div>
              <div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border); padding-bottom:0.5rem;">
                <span style="color:var(--text-muted);">Program / Course:</span>
                <strong><?= htmlspecialchars($studentDetails['course']) ?></strong>
              </div>
              <div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border); padding-bottom:0.5rem;">
                <span style="color:var(--text-muted);">Current Semester:</span>
                <strong>Semester <?= (int)$studentDetails['semester'] ?> (Year <?= (int)$studentDetails['year'] ?>)</strong>
              </div>
              <div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border); padding-bottom:0.5rem;">
                <span style="color:var(--text-muted);">Cumulative CGPA:</span>
                <strong style="color:var(--success); font-size:1.1rem;"><?= htmlspecialchars($studentDetails['cgpa']) ?></strong>
              </div>
              <div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border); padding-bottom:0.5rem;">
                <span style="color:var(--text-muted);">Contact Mobile:</span>
                <span><?= htmlspecialchars($studentDetails['mobile']) ?></span>
              </div>
              <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--text-muted);">Date of Birth:</span>
                <span><?= htmlspecialchars($studentDetails['dob'] ?? 'N/A') ?></span>
              </div>
            </div>
          <?php else: ?>
            <p style="color:var(--text-muted);">Student profile details not linked to user account.</p>
          <?php endif; ?>
        </div>

        <div class="card">
          <h2>Enrolled Campus Events</h2>
          <div class="table-responsive">
            <table>
              <thead>
                <tr>
                  <th>Event Name</th>
                  <th>Category</th>
                  <th>Venue</th>
                  <th>Date</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($registeredEvents)): ?>
                  <tr><td colspan="5" style="text-align:center; color:var(--text-muted);">You are not registered for any events yet.</td></tr>
                <?php else: ?>
                  <?php foreach ($registeredEvents as $ev): ?>
                    <tr>
                      <td><strong><?= htmlspecialchars($ev['title']) ?></strong></td>
                      <td><span class="badge-role badge-student"><?= htmlspecialchars($ev['category']) ?></span></td>
                      <td><?= htmlspecialchars($ev['venue']) ?></td>
                      <td style="font-family:var(--mono);"><?= htmlspecialchars($ev['event_date']) ?></td>
                      <td><span class="badge-status status-open"><?= htmlspecialchars($ev['status']) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    <?php endif; ?>

  <?php endif; ?>

</main>

<script>
function fillForm(email, pass) {
  document.getElementById('emailInput').value = email;
  document.getElementById('passwordInput').value = pass;
}

<?php if ($currentUser): ?>
let secondsRemaining = <?= $timeoutDuration ?>;
const timerEl = document.getElementById('timeLeft');

function updateTimer() {
  const mins = Math.floor(secondsRemaining / 60);
  const secs = secondsRemaining % 60;
  timerEl.textContent = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
  if (secondsRemaining <= 60) {
    timerEl.style.color = '#ef4444';
  }
  if (secondsRemaining <= 0) {
    window.location.href = 'index.php?status=timeout';
  } else {
    secondsRemaining--;
  }
}
setInterval(updateTimer, 1000);
<?php endif; ?>
</script>

</body>
</html>
