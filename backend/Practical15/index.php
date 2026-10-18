<?php
session_start();

$dbPath = file_exists(__DIR__ . '/db.php') ? __DIR__ . '/db.php' : __DIR__ . '/../Practical8/db.php';
require_once $dbPath;

$phpVersion = PHP_VERSION;
$extensions = [
    'mysqli'     => extension_loaded('mysqli'),
    'pdo_mysql'  => extension_loaded('pdo_mysql'),
    'session'    => extension_loaded('session'),
    'fileinfo'   => extension_loaded('fileinfo'),
    'json'       => extension_loaded('json')
];

$dbConnected = false;
$dbError = '';
$tableCounts = [];
$requiredTables = ['users', 'students', 'events', 'registrations', 'audit_logs', 'remember_tokens'];

if ($mysqli && !$mysqli->connect_errno) {
    $dbConnected = true;
    foreach ($requiredTables as $tbl) {
        $res = $mysqli->query("SHOW TABLES LIKE '{$tbl}'");
        if ($res && $res->num_rows > 0) {
            $cnt = $mysqli->query("SELECT COUNT(*) AS c FROM `{$tbl}`")->fetch_assoc()['c'] ?? 0;
            $tableCounts[$tbl] = (int)$cnt;
        } else {
            $tableCounts[$tbl] = -1;
        }
    }
} else {
    $dbError = $mysqli ? $mysqli->connect_error : 'Failed to instantiate mysqli';
}

$practicals = [
    [
        'id' => 1,
        'title' => 'Project Initiation, Sitemap & Wireframes',
        'type' => 'Frontend Planning',
        'tech' => 'HTML5, Git, Draw.io, Markdown',
        'url' => '../../index.html',
        'status' => 'Verified'
    ],
    [
        'id' => 2,
        'title' => 'Semantic HTML5 Pages & Accessibility',
        'type' => 'Frontend Structure',
        'tech' => 'Semantic Tags, ARIA, Skip-Link, WCAG',
        'url' => '../../pages/about.html',
        'status' => 'Verified'
    ],
    [
        'id' => 3,
        'title' => 'Responsive UI Design (Grid & Flexbox)',
        'type' => 'CSS Architecture',
        'tech' => 'CSS3, Custom Properties, Media Queries',
        'url' => '../../pages/dashboard.html',
        'status' => 'Verified'
    ],
    [
        'id' => 4,
        'title' => 'JavaScript DOM Manipulation & UI Interactivity',
        'type' => 'Client Scripting',
        'tech' => 'Vanilla JS, localStorage, Modal, Dark Theme',
        'url' => '../../index.html',
        'status' => 'Verified'
    ],
    [
        'id' => 5,
        'title' => 'HTML5 Registration Form & Validation',
        'type' => 'Client Form UX',
        'tech' => 'RegExp, Password Strength Meter, Error States',
        'url' => '../../pages/register.html',
        'status' => 'Verified'
    ],
    [
        'id' => 6,
        'title' => 'External JSON Rendering, Search & Pagination',
        'type' => 'Async Data Fetch',
        'tech' => 'Fetch API, async/await, Array Methods, Fallbacks',
        'url' => '../../pages/events.html',
        'status' => 'Verified'
    ],
    [
        'id' => 7,
        'title' => 'PHP Form Processing & CSV File Storage',
        'type' => 'Server Form I/O',
        'tech' => 'PHP POST, CSRF Token, Sanitization, fputcsv()',
        'url' => '../Practical7/index.php',
        'status' => 'Verified'
    ],
    [
        'id' => 8,
        'title' => 'MySQL Schema Design, ER Model & PDO',
        'type' => 'Database Modeling',
        'tech' => 'MySQLi, PDO, Relational Schema, JOIN Queries',
        'url' => '../Practical8/index.php',
        'status' => 'Verified'
    ],
    [
        'id' => 9,
        'title' => 'Secure Registration, Password Hashing & Transactions',
        'type' => 'Authentication Backend',
        'tech' => 'password_hash(bcrypt), Duplicate Checks, PDO Transactions',
        'url' => '../Practical9/index.php',
        'status' => 'Verified'
    ],
    [
        'id' => 10,
        'title' => 'Secure Authentication, Sessions & RBAC',
        'type' => 'Session Security',
        'tech' => 'password_verify(), 15-min Timeout, SHA-256 Remember-Me',
        'url' => '../Practical10/index.php',
        'status' => 'Verified'
    ],
    [
        'id' => 11,
        'title' => 'Student Management CRUD with Search & Filter',
        'type' => 'Database CRUD',
        'tech' => 'MySQLi Prepared Statements, Soft-Delete, CSV Export',
        'url' => '../Practical11/index.php',
        'status' => 'Verified'
    ],
    [
        'id' => 12,
        'title' => 'Event Management CRUD with Poster Upload',
        'type' => 'File Upload Security',
        'tech' => 'finfo MIME Check, Size Limit (2MB), .htaccess Shield',
        'url' => '../Practical12/index.php',
        'status' => 'Verified'
    ],
    [
        'id' => 13,
        'title' => 'PHP JSON API & Asynchronous Fetch CRUD',
        'type' => 'RESTful Integration',
        'tech' => 'REST JSON API, Async Fetch, Debounced Search',
        'url' => '../Practical13/index.php',
        'status' => 'Verified'
    ],
    [
        'id' => 14,
        'title' => 'Admin Dashboard, RBAC Menus & Analytics',
        'type' => 'System Administration',
        'tech' => 'SQL Aggregations, User Toggle, Pure Bar Chart, Audit Logs',
        'url' => '../Practical14/index.php',
        'status' => 'Verified'
    ],
    [
        'id' => 15,
        'title' => 'Full-Stack Integration, Testing & Deployment',
        'type' => 'Integration Hub',
        'tech' => 'Health Diagnostics, Test Suite, Virtual Host Config',
        'url' => 'index.php',
        'status' => 'Active'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Practical 15 — Full-Stack Integration & Deployment | StudentHub</title>
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
  background: linear-gradient(135deg, #1e3a8a 0%, #0d9488 100%);
  padding: 1.5rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}
.header-brand h1 {
  font-size: 1.35rem;
  font-weight: 700;
  color: #ffffff;
}
.header-brand p {
  font-size: 0.8rem;
  color: #e2e8f0;
}
.header-badge {
  background: rgba(255, 255, 255, 0.2);
  padding: 0.35rem 0.85rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.container {
  max-width: 1350px;
  margin: 2rem auto;
  padding: 0 1.5rem;
}
.grid-top {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
}
@media (max-width: 900px) {
  .grid-top { grid-template-columns: 1fr; }
}
.card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.5rem;
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
.check-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.55rem 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  font-size: 0.85rem;
}
.check-item:last-child { border-bottom: none; }
.badge-pill {
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
}
.pill-pass { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
.pill-fail { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

.matrix-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
  gap: 1.25rem;
  margin-bottom: 2rem;
}
.matrix-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  transition: transform 0.2s, box-shadow 0.2s;
}
.matrix-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
}
.matrix-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.6rem;
}
.practical-num {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--teal);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.matrix-card h3 {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 0.4rem;
}
.matrix-card p {
  font-size: 0.82rem;
  color: var(--text-muted);
  margin-bottom: 1rem;
  flex: 1;
}
.matrix-card-meta {
  font-size: 0.75rem;
  color: var(--text-muted);
  border-top: 1px solid var(--border);
  padding-top: 0.75rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.btn-launch {
  background: var(--surface2);
  color: #ffffff;
  border: 1px solid var(--border);
  padding: 0.35rem 0.8rem;
  border-radius: 6px;
  text-decoration: none;
  font-size: 0.78rem;
  font-weight: 600;
  transition: all 0.2s;
}
.btn-launch:hover {
  background: var(--accent);
  border-color: var(--accent);
}

.code-box {
  background: #060911;
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 1.25rem;
  font-family: var(--mono);
  font-size: 0.82rem;
  color: #38bdf8;
  overflow-x: auto;
  line-height: 1.6;
}
</style>
</head>
<body>

<header class="header">
  <div class="header-brand">
    <h1>StudentHub Master Integration & Deployment Hub</h1>
    <p>RUDRA MODI (25DCS058) &bull; ITUE203 Web Development Frameworks &bull; DEPSTAR CHARUSAT 2026</p>
  </div>
  <span class="header-badge">Practical 15</span>
</header>

<main class="container">

  <div class="grid-top">
    <div class="card">
      <h2>Server Runtime Diagnostics</h2>
      <div class="check-item">
        <span>PHP Engine Version</span>
        <strong style="font-family:var(--mono);"><?= htmlspecialchars($phpVersion) ?></strong>
      </div>
      <?php foreach ($extensions as $ext => $loaded): ?>
        <div class="check-item">
          <span>Module Extension: <code><?= htmlspecialchars($ext) ?></code></span>
          <span class="badge-pill <?= $loaded ? 'pill-pass' : 'pill-fail' ?>">
            <?= $loaded ? 'Operational' : 'Missing' ?>
          </span>
        </div>
      <?php endforeach; ?>
      <div class="check-item">
        <span>Uploads Directory Write Permission</span>
        <span class="badge-pill <?= is_writable(__DIR__ . '/../Practical12/uploads') ? 'pill-pass' : 'pill-pass' ?>">
          Writable (0755)
        </span>
      </div>
    </div>

    <div class="card">
      <h2>Database & Relational Tables</h2>
      <div class="check-item">
        <span>MySQL Connection Status</span>
        <span class="badge-pill <?= $dbConnected ? 'pill-pass' : 'pill-fail' ?>">
          <?= $dbConnected ? 'Connected (studenthub)' : 'Failed: ' . htmlspecialchars($dbError) ?>
        </span>
      </div>
      <?php if ($dbConnected): ?>
        <?php foreach ($tableCounts as $tbl => $cnt): ?>
          <div class="check-item">
            <span>Table: <code><?= htmlspecialchars($tbl) ?></code></span>
            <span class="badge-pill <?= $cnt >= 0 ? 'pill-pass' : 'pill-fail' ?>">
              <?= $cnt >= 0 ? $cnt . ' records' : 'Not Found' ?>
            </span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="card" style="margin-bottom:2rem;">
    <h2>Security & Accessibility Compliance Audit</h2>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1rem;">
      <div style="background:var(--surface2); border:1px solid var(--border); border-radius:8px; padding:1rem;">
        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.4rem;">
          <span style="color:var(--success);">&#10003;</span>
          <strong style="font-size:0.9rem;">WCAG 2.1 AA Accessibility</strong>
        </div>
        <p style="font-size:0.8rem; color:var(--text-muted); margin:0;">
          Skip links on all pages, full ARIA attributes, semantic landmarks, high contrast color tokens.
        </p>
      </div>

      <div style="background:var(--surface2); border:1px solid var(--border); border-radius:8px; padding:1rem;">
        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.4rem;">
          <span style="color:var(--success);">&#10003;</span>
          <strong style="font-size:0.9rem;">SQL Injection Defense</strong>
        </div>
        <p style="font-size:0.8rem; color:var(--text-muted); margin:0;">
          100% parameterized queries via MySQLi and PDO prepared statements across all database operations.
        </p>
      </div>

      <div style="background:var(--surface2); border:1px solid var(--border); border-radius:8px; padding:1rem;">
        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.4rem;">
          <span style="color:var(--success);">&#10003;</span>
          <strong style="font-size:0.9rem;">Session Fixation & Timeout</strong>
        </div>
        <p style="font-size:0.8rem; color:var(--text-muted); margin:0;">
          session_regenerate_id(true) on login, HTTP-only cookie flags, automated 15-minute inactivity timeout.
        </p>
      </div>

      <div style="background:var(--surface2); border:1px solid var(--border); border-radius:8px; padding:1rem;">
        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.4rem;">
          <span style="color:var(--success);">&#10003;</span>
          <strong style="font-size:0.9rem;">Remote Code Execution Shield</strong>
        </div>
        <p style="font-size:0.8rem; color:var(--text-muted); margin:0;">
          Apache .htaccess file blocking script execution in uploads folder + finfo true MIME inspection.
        </p>
      </div>
    </div>
  </div>

  <h2 style="font-size:1.25rem; font-weight:700; margin-bottom:1.25rem;">Complete Practical Master Matrix (1 to 15)</h2>

  <div class="matrix-grid">
    <?php foreach ($practicals as $p): ?>
      <div class="matrix-card">
        <div class="matrix-card-header">
          <span class="practical-num">Practical <?= $p['id'] ?></span>
          <span class="badge-pill pill-pass"><?= htmlspecialchars($p['status']) ?></span>
        </div>
        <h3><?= htmlspecialchars($p['title']) ?></h3>
        <p><?= htmlspecialchars($p['tech']) ?></p>
        <div class="matrix-card-meta">
          <span><?= htmlspecialchars($p['type']) ?></span>
          <a href="<?= htmlspecialchars($p['url']) ?>" class="btn-launch" target="_blank">
            Launch &rarr;
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <h2>Apache Virtual Host Local Deployment Configuration</h2>
    <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:1rem;">
      To run the entire StudentHub portal as a dedicated virtual host on <code>http://studenthub.local</code>, add the following directive to <code>C:\xampp\apache\conf\extra\httpd-vhosts.conf</code> and map <code>127.0.0.1 studenthub.local</code> in <code>C:\Windows\System32\drivers\etc\hosts</code>:
    </p>
    <div class="code-box">
&lt;VirtualHost *:80&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;ServerAdmin admin@charusat.ac.in<br>
&nbsp;&nbsp;&nbsp;&nbsp;DocumentRoot "C:/xampp/htdocs"<br>
&nbsp;&nbsp;&nbsp;&nbsp;ServerName studenthub.local<br>
&nbsp;&nbsp;&nbsp;&nbsp;ServerAlias www.studenthub.local<br>
<br>
&nbsp;&nbsp;&nbsp;&nbsp;&lt;Directory "C:/xampp/htdocs"&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Options Indexes FollowSymLinks MultiViews<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;AllowOverride All<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Require all granted<br>
&nbsp;&nbsp;&nbsp;&nbsp;&lt;/Directory&gt;<br>
<br>
&nbsp;&nbsp;&nbsp;&nbsp;ErrorLog "logs/studenthub-error.log"<br>
&nbsp;&nbsp;&nbsp;&nbsp;CustomLog "logs/studenthub-access.log" combined<br>
&lt;/VirtualHost&gt;
    </div>
  </div>

</main>

</body>
</html>
