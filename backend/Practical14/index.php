<?php
session_start();

$dbPath = file_exists(__DIR__ . '/db.php') ? __DIR__ . '/db.php' : __DIR__ . '/../Practical8/db.php';
require_once $dbPath;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$flashMessage = '';
$flashType = '';

if (isset($_GET['switch_role'])) {
    $targetRole = $_GET['switch_role'];
    if ($targetRole === 'admin') {
        $uStmt = $mysqli->prepare("SELECT id, name, email, role FROM users WHERE role = 'admin' LIMIT 1");
        $uStmt->execute();
        $user = $uStmt->get_result()->fetch_assoc();
        $uStmt->close();
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'admin';
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            header('Location: index.php?status=switched_admin');
            exit;
        }
    } elseif ($targetRole === 'student') {
        $uStmt = $mysqli->prepare("SELECT id, name, email, role FROM users WHERE role = 'student' LIMIT 1");
        $uStmt->execute();
        $user = $uStmt->get_result()->fetch_assoc();
        $uStmt->close();
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'student';
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            header('Location: index.php?status=switched_student');
            exit;
        }
    } elseif ($targetRole === 'guest') {
        $_SESSION = [];
        header('Location: index.php?status=switched_guest');
        exit;
    }
}

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'switched_admin') {
        $flashMessage = 'Session switched to Administrator account.';
        $flashType = 'success';
    } elseif ($_GET['status'] === 'switched_student') {
        $flashMessage = 'Session switched to Student account. RBAC restrictions active.';
        $flashType = 'warning';
    } elseif ($_GET['status'] === 'switched_guest') {
        $flashMessage = 'Session cleared. Viewing as Guest.';
        $flashType = 'info';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $flashMessage = 'CSRF validation error.';
        $flashType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'toggle_user_status') {
            if (($_SESSION['role'] ?? '') !== 'admin') {
                $flashMessage = 'Access denied: Admin privileges required to modify user status.';
                $flashType = 'error';
            } else {
                $targetUserId = (int)($_POST['target_user_id'] ?? 0);
                if ($targetUserId > 0) {
                    $tog = $mysqli->prepare("UPDATE users SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
                    $tog->bind_param("i", $targetUserId);
                    if ($tog->execute()) {
                        $tog->close();

                        $adminId = (int)$_SESSION['user_id'];
                        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                        $logS = $mysqli->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, 'RBAC_STATUS_TOGGLE', ?, ?)");
                        if ($logS) {
                            $desc = "Admin toggled status for user ID {$targetUserId}";
                            $logS->bind_param("iss", $adminId, $desc, $ip);
                            $logS->execute();
                            $logS->close();
                        }

                        $flashMessage = "User #{$targetUserId} status toggled successfully.";
                        $flashType = 'success';
                    }
                }
            }
        }
    }
}

$currentUserRole = $_SESSION['role'] ?? 'guest';
$currentUserName = $_SESSION['name'] ?? 'Guest Visitor';
$currentUserId   = $_SESSION['user_id'] ?? null;

$menuItems = [];
if ($currentUserRole === 'admin') {
    $menuItems = [
        ['label' => 'Dashboard Overview', 'url' => '#overview', 'active' => true],
        ['label' => 'Student Records (P11)', 'url' => '../Practical11/index.php', 'active' => false],
        ['label' => 'Event Manager & Posters (P12)', 'url' => '../Practical12/index.php', 'active' => false],
        ['label' => 'Async JSON API (P13)', 'url' => '../Practical13/index.php', 'active' => false],
        ['label' => 'Security Audit Logs', 'url' => '#audit-section', 'active' => false],
        ['label' => 'Registration Analytics', 'url' => '#analytics-section', 'active' => false]
    ];
} elseif ($currentUserRole === 'student') {
    $menuItems = [
        ['label' => 'Student Profile', 'url' => '../Practical10/index.php', 'active' => true],
        ['label' => 'Campus Events Calendar', 'url' => '../Practical12/index.php', 'active' => false],
        ['label' => 'Browse API Events', 'url' => '../Practical13/index.php', 'active' => false]
    ];
} else {
    $menuItems = [
        ['label' => 'Portal Home', 'url' => '../../index.html', 'active' => true],
        ['label' => 'Authenticate (P10)', 'url' => '../Practical10/index.php', 'active' => false],
        ['label' => 'Register (P9)', 'url' => '../Practical9/index.php', 'active' => false]
    ];
}

$stats = [
    'users' => 0,
    'students' => 0,
    'events' => 0,
    'registrations' => 0,
    'active_users' => 0,
    'inactive_users' => 0
];
$categoryStats = [];
$latestRegistrations = [];
$auditLogs = [];
$usersList = [];

if ($currentUserRole === 'admin') {
    $stats['users'] = (int)($mysqli->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'] ?? 0);
    $stats['students'] = (int)($mysqli->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()['c'] ?? 0);
    $stats['events'] = (int)($mysqli->query("SELECT COUNT(*) AS c FROM events")->fetch_assoc()['c'] ?? 0);
    $stats['registrations'] = (int)($mysqli->query("SELECT COUNT(*) AS c FROM registrations")->fetch_assoc()['c'] ?? 0);
    $stats['active_users'] = (int)($mysqli->query("SELECT COUNT(*) AS c FROM users WHERE status = 'active'")->fetch_assoc()['c'] ?? 0);
    $stats['inactive_users'] = $stats['users'] - $stats['active_users'];

    $catRes = $mysqli->query("
        SELECT e.category, COUNT(r.id) AS reg_count
        FROM events e
        LEFT JOIN registrations r ON r.event_id = e.id
        GROUP BY e.category
    ");
    if ($catRes) {
        while ($row = $catRes->fetch_assoc()) {
            $categoryStats[$row['category']] = (int)$row['reg_count'];
        }
    }

    $latestRes = $mysqli->query("
        SELECT r.id, r.registered_at, s.enrollment_no, u.name AS student_name, e.title AS event_title, e.category
        FROM registrations r
        JOIN students s ON s.id = r.student_id
        JOIN users u ON u.id = s.user_id
        JOIN events e ON e.id = r.event_id
        ORDER BY r.id DESC
        LIMIT 6
    ");
    if ($latestRes) {
        $latestRegistrations = $latestRes->fetch_all(MYSQLI_ASSOC);
    }

    $logRes = $mysqli->query("
        SELECT a.id, a.action, a.description, a.ip_address, a.created_at, u.name AS user_name
        FROM audit_logs a
        LEFT JOIN users u ON u.id = a.user_id
        ORDER BY a.id DESC
        LIMIT 10
    ");
    if ($logRes) {
        $auditLogs = $logRes->fetch_all(MYSQLI_ASSOC);
    }

    $userRes = $mysqli->query("
        SELECT u.id, u.name, u.email, u.role, u.status, u.created_at, u.last_login,
               s.enrollment_no, s.course
        FROM users u
        LEFT JOIN students s ON s.user_id = u.id
        ORDER BY u.id DESC
        LIMIT 8
    ");
    if ($userRes) {
        $usersList = $userRes->fetch_all(MYSQLI_ASSOC);
    }
}

$csrf = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Practical 14 — Admin Dashboard & Role-Based Access | StudentHub</title>
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
  background: linear-gradient(135deg, #4338ca 0%, #6d28d9 100%);
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
  max-width: 1350px;
  margin: 2rem auto;
  padding: 0 1.5rem;
}
.role-bar {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.25rem 1.75rem;
  margin-bottom: 1.75rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}
.role-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.role-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--accent) 0%, #ec4899 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.1rem;
  color: #ffffff;
}
.badge-role {
  font-size: 0.72rem;
  padding: 0.2rem 0.65rem;
  border-radius: 9999px;
  font-weight: 700;
  text-transform: uppercase;
}
.badge-admin   { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); }
.badge-student { background: rgba(20, 184, 166, 0.2); color: #2dd4bf; border: 1px solid rgba(20, 184, 166, 0.4); }
.badge-guest   { background: rgba(148, 163, 184, 0.2); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.4); }

.role-switcher {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: var(--surface2);
  border: 1px solid var(--border);
  padding: 0.35rem 0.5rem;
  border-radius: 8px;
}
.switch-label {
  font-size: 0.75rem;
  color: var(--text-muted);
  text-transform: uppercase;
  margin-right: 0.3rem;
}
.switch-btn {
  padding: 0.3rem 0.65rem;
  border-radius: 6px;
  font-size: 0.78rem;
  font-weight: 600;
  text-decoration: none;
  color: var(--text-muted);
  transition: all 0.2s;
}
.switch-btn:hover { color: var(--text); background: rgba(255, 255, 255, 0.05); }
.switch-btn.active {
  background: var(--accent);
  color: #ffffff;
}

.dynamic-nav {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-bottom: 1.75rem;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 0.6rem 1rem;
}
.dynamic-nav-item {
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 500;
  text-decoration: none;
  color: var(--text-muted);
  transition: all 0.2s;
}
.dynamic-nav-item:hover { color: var(--text); background: var(--surface2); }
.dynamic-nav-item.active { background: var(--surface2); color: var(--teal); font-weight: 600; }

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
.alert-warning { background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.3); color: #fbbf24; }
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
.card h2 {
  font-size: 1.15rem;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 1.25rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
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
  padding: 0.2rem 0.55rem;
  border-radius: 9999px;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
}
.badge-active   { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
.badge-inactive { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.35rem 0.7rem;
  font-size: 0.78rem;
  font-weight: 600;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.2s;
}
.btn-toggle {
  background: var(--surface2);
  color: var(--text);
  border: 1px solid var(--border);
}
.btn-toggle:hover { background: var(--surface3); }

.chart-container {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  background: var(--surface2);
  border-radius: 8px;
  padding: 1.5rem;
}
.chart-bar-row {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.chart-bar-label {
  width: 100px;
  font-size: 0.85rem;
  font-weight: 600;
}
.chart-bar-track {
  flex: 1;
  height: 24px;
  background: var(--surface);
  border-radius: 6px;
  overflow: hidden;
  border: 1px solid var(--border);
}
.chart-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--accent) 0%, var(--teal) 100%);
  border-radius: 5px;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding-right: 0.5rem;
  font-size: 0.75rem;
  font-weight: 700;
  color: #ffffff;
  transition: width 0.8s ease-out;
}
</style>
</head>
<body>

<header class="header">
  <div class="header-brand">
    <h1>StudentHub Portal</h1>
    <span class="header-badge">Practical 14</span>
  </div>
  <nav class="header-nav">
    <a href="../Practical10/index.php">P10: Auth</a>
    <a href="../Practical11/index.php">P11: Students</a>
    <a href="../Practical12/index.php">P12: Events</a>
    <a href="../Practical13/index.php">P13: Async API</a>
    <a href="../Practical15/index.php">P15: Integration</a>
  </nav>
</header>

<main class="container">

  <?php if (!empty($flashMessage)): ?>
    <div class="alert alert-<?= $flashType ?>">
      <span><?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8') ?></span>
      <span style="cursor:pointer;" onclick="this.parentElement.remove();">&times;</span>
    </div>
  <?php endif; ?>

  <div class="role-bar">
    <div class="role-info">
      <div class="role-avatar"><?= strtoupper(substr($currentUserName, 0, 1)) ?></div>
      <div>
        <div style="display:flex; align-items:center; gap:0.5rem;">
          <h2 style="font-size:1rem; margin:0;"><?= htmlspecialchars($currentUserName) ?></h2>
          <span class="badge-role badge-<?= $currentUserRole ?>">
            Role: <?= htmlspecialchars($currentUserRole) ?>
          </span>
        </div>
        <p style="font-size:0.8rem; color:var(--text-muted); margin:0;">
          <?= $currentUserRole === 'admin' ? 'Superuser Administrative Access' : 'Standard Student Account Privileges' ?>
        </p>
      </div>
    </div>

    <div class="role-switcher">
      <span class="switch-label">Simulate Role:</span>
      <a href="index.php?switch_role=admin" class="switch-btn <?= $currentUserRole === 'admin' ? 'active' : '' ?>">Admin</a>
      <a href="index.php?switch_role=student" class="switch-btn <?= $currentUserRole === 'student' ? 'active' : '' ?>">Student</a>
      <a href="index.php?switch_role=guest" class="switch-btn <?= $currentUserRole === 'guest' ? 'active' : '' ?>">Guest</a>
    </div>
  </div>

  <div class="dynamic-nav">
    <?php foreach ($menuItems as $m): ?>
      <a href="<?= htmlspecialchars($m['url']) ?>" class="dynamic-nav-item <?= $m['active'] ? 'active' : '' ?>">
        <?= htmlspecialchars($m['label']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($currentUserRole !== 'admin'): ?>

    <div class="card" style="text-align:center; padding:3.5rem 1.5rem;">
      <div style="font-size:3rem; margin-bottom:1rem;">&#128274;</div>
      <h2 style="justify-content:center; color:var(--danger); font-size:1.4rem;">RBAC Access Restriction: Admin Role Required</h2>
      <p style="color:var(--text-muted); max-width:540px; margin:0 auto 1.5rem auto;">
        You are currently authenticated as <strong><?= htmlspecialchars($currentUserRole) ?></strong>. The Administrative Dashboard, User Status toggling, and Audit Log inspection are strictly restricted to users with the <code>admin</code> role.
      </p>
      <div style="display:flex; justify-content:center; gap:1rem;">
        <a href="index.php?switch_role=admin" class="btn" style="background:var(--accent); color:#ffffff; padding:0.6rem 1.2rem; font-size:0.9rem;">
          Simulate Admin Login &rarr;
        </a>
        <a href="../Practical10/index.php" class="btn btn-toggle" style="padding:0.6rem 1.2rem; font-size:0.9rem;">
          Return to Auth Portal
        </a>
      </div>
    </div>

  <?php else: ?>

    <div class="stats-grid" id="overview">
      <div class="stat-card">
        <span class="stat-label">Total Users</span>
        <span class="stat-value"><?= $stats['users'] ?></span>
      </div>
      <div class="stat-card">
        <span class="stat-label">Students</span>
        <span class="stat-value"><?= $stats['students'] ?></span>
      </div>
      <div class="stat-card">
        <span class="stat-label">Active Users</span>
        <span class="stat-value" style="color:var(--success);"><?= $stats['active_users'] ?></span>
      </div>
      <div class="stat-card">
        <span class="stat-label">Inactive Users</span>
        <span class="stat-value" style="color:var(--danger);"><?= $stats['inactive_users'] ?></span>
      </div>
      <div class="stat-card">
        <span class="stat-label">Published Events</span>
        <span class="stat-value" style="color:var(--teal);"><?= $stats['events'] ?></span>
      </div>
      <div class="stat-card">
        <span class="stat-label">Registrations</span>
        <span class="stat-value" style="color:#ec4899;"><?= $stats['registrations'] ?></span>
      </div>
    </div>

    <div class="card" id="analytics-section">
      <h2>Registration Analytics by Event Category</h2>
      <div class="chart-container">
        <?php
          $maxCount = max(array_values($categoryStats) ?: [1]);
          if ($maxCount <= 0) $maxCount = 1;
        ?>
        <?php foreach ($categoryStats as $category => $count): ?>
          <?php $pct = round(($count / $maxCount) * 100); ?>
          <div class="chart-bar-row">
            <span class="chart-bar-label"><?= htmlspecialchars($category) ?></span>
            <div class="chart-bar-track">
              <div class="chart-bar-fill" style="width: <?= max($pct, 8) ?>%;">
                <?= $count ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <h2>User Directory & Active Status Toggle (Intermediate Extension)</h2>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>User Name</th>
              <th>Institutional Email</th>
              <th>Role</th>
              <th>Enrollment / Course</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($usersList as $u): ?>
              <tr>
                <td style="font-family:var(--mono);">#<?= (int)$u['id'] ?></td>
                <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                <td style="color:var(--text-muted);"><?= htmlspecialchars($u['email']) ?></td>
                <td>
                  <span class="badge-role badge-<?= htmlspecialchars($u['role']) ?>">
                    <?= htmlspecialchars($u['role']) ?>
                  </span>
                </td>
                <td>
                  <?= $u['enrollment_no'] ? htmlspecialchars($u['enrollment_no'] . ' (' . $u['course'] . ')') : '<span style="color:var(--text-muted);">N/A</span>' ?>
                </td>
                <td>
                  <span class="badge-status <?= $u['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                    <?= htmlspecialchars($u['status']) ?>
                  </span>
                </td>
                <td>
                  <form action="index.php" method="POST" style="margin:0;">
                    <input type="hidden" name="action" value="toggle_user_status">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="target_user_id" value="<?= (int)$u['id'] ?>">
                    <button type="submit" class="btn btn-toggle">
                      Toggle to <?= $u['status'] === 'active' ? 'Inactive' : 'Active' ?>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card" id="audit-section">
      <h2>System Security Audit Trail (Recent Activity)</h2>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Initiator</th>
              <th>Action Type</th>
              <th>Description</th>
              <th>Origin IP</th>
              <th>Timestamp</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($auditLogs as $log): ?>
              <tr>
                <td style="font-family:var(--mono);">#<?= (int)$log['id'] ?></td>
                <td><strong><?= htmlspecialchars($log['user_name'] ?? 'System/Guest') ?></strong></td>
                <td><span class="badge-role badge-student"><?= htmlspecialchars($log['action']) ?></span></td>
                <td><?= htmlspecialchars($log['description']) ?></td>
                <td style="font-family:var(--mono); color:var(--text-muted);"><?= htmlspecialchars($log['ip_address']) ?></td>
                <td style="color:var(--text-muted); font-size:0.75rem;"><?= htmlspecialchars($log['created_at']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  <?php endif; ?>

</main>

</body>
</html>
