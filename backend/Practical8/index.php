<?php
/* ============================================================
   Practical 8 — index.php
   MySQL Schema Design, ER Model, PDO Connectivity & Prepared Statements
   Student: RUDRA MODI (25DCS058) | ITUE203
   ============================================================ */

require_once __DIR__ . '/db.php';

// --- Connection test ---
$connStatus  = 'success';
$connMessage = 'Connected to studenthub database successfully via MySQLi.';
$serverInfo  = $mysqli->server_info;
$hostInfo    = $mysqli->host_info;

// --- PDO connection test ---
try {
    $pdo         = getPDO();
    $pdoStatus   = 'success';
    $pdoMessage  = 'PDO connection with utf8mb4 charset established successfully.';
} catch (PDOException $e) {
    $pdoStatus   = 'error';
    $pdoMessage  = 'PDO Error: ' . htmlspecialchars($e->getMessage());
}

// --- Fetch table row counts using prepared statement ---
$tables = ['users', 'students', 'events', 'registrations', 'audit_logs'];
$counts = [];
foreach ($tables as $table) {
    $stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM `$table`");
    $stmt->execute();
    $result = $stmt->get_result();
    $row    = $result->fetch_assoc();
    $counts[$table] = $row['cnt'];
    $stmt->close();
}

// --- Fetch recent students with JOIN (prepared statement) ---
$stmt = $mysqli->prepare(
    "SELECT u.name, s.enrollment_no, s.course, s.semester, s.cgpa
     FROM students s
     JOIN users u ON u.id = s.user_id
     ORDER BY s.id
     LIMIT 10"
);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- Fetch recent events ---
$stmt = $mysqli->prepare(
    "SELECT title, category, venue, event_date, status
     FROM events
     ORDER BY event_date
     LIMIT 5"
);
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Practical 8 — MySQL Schema & PDO | StudentHub</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #0f1117; --surface: #1a1d2e; --surface2: #242740;
    --accent: #6c63ff; --accent2: #00d4aa; --danger: #ff4757;
    --success: #2ed573; --warning: #ffa502;
    --text: #e2e8f0; --text2: #94a3b8; --border: #2d3748;
    --radius: 12px; --mono: 'JetBrains Mono', monospace;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

  .topbar { background: linear-gradient(135deg, var(--accent), #4f46e5);
    padding: 1.5rem 2rem; display: flex; align-items: center; gap: 1rem; }
  .topbar h1 { font-size: 1.4rem; font-weight: 700; }
  .badge { background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem;
    border-radius: 20px; font-size: 0.75rem; font-weight: 600; }

  .container { max-width: 1100px; margin: 2rem auto; padding: 0 1.5rem; }

  .section { background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 1.5rem; margin-bottom: 1.5rem; }
  .section h2 { font-size: 1rem; font-weight: 700; color: var(--accent2);
    margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }

  .status-card { display: flex; align-items: center; gap: 1rem;
    padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 0.75rem; }
  .status-card.ok  { background: rgba(46,213,115,0.1); border: 1px solid rgba(46,213,115,0.3); }
  .status-card.err { background: rgba(255,71,87,0.1);  border: 1px solid rgba(255,71,87,0.3); }
  .status-dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; }
  .dot-ok  { background: var(--success); box-shadow: 0 0 8px var(--success); }
  .dot-err { background: var(--danger);  box-shadow: 0 0 8px var(--danger); }
  .status-card .label { font-weight: 600; font-size: 0.9rem; }
  .status-card .msg   { font-size: 0.82rem; color: var(--text2); margin-top: 2px; }
  .meta { font-family: var(--mono); font-size: 0.75rem; color: var(--accent2); margin-top: 4px; }

  .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1rem; }
  .stat { background: var(--surface2); border-radius: 10px; padding: 1.25rem;
    text-align: center; border: 1px solid var(--border); }
  .stat .num  { font-size: 2rem; font-weight: 700; color: var(--accent); }
  .stat .name { font-size: 0.78rem; color: var(--text2); margin-top: 4px; text-transform: uppercase; letter-spacing: 0.05em; }

  table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
  thead tr { background: var(--surface2); }
  th { padding: 0.75rem 1rem; text-align: left; font-weight: 600;
    color: var(--text2); font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; }
  td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); }
  tr:last-child td { border-bottom: none; }
  tr:hover td { background: var(--surface2); }

  .pill { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 20px;
    font-size: 0.72rem; font-weight: 600; }
  .pill-open    { background: rgba(46,213,115,0.15); color: var(--success); }
  .pill-limited { background: rgba(255,165,2,0.15);  color: var(--warning); }
  .pill-soon    { background: rgba(108,99,255,0.15); color: var(--accent); }
  .pill-closed  { background: rgba(255,71,87,0.15);  color: var(--danger); }

  .code-block { background: #0d1117; border: 1px solid var(--border); border-radius: 8px;
    padding: 1rem 1.25rem; font-family: var(--mono); font-size: 0.78rem;
    color: #a9d1f7; overflow-x: auto; line-height: 1.7; }
  .kw { color: #c792ea; } .fn { color: #82aaff; } .str { color: #c3e88d; }
  .cm { color: #546e7a; } .tp { color: #ffcb6b; }

  footer { text-align: center; padding: 2rem; color: var(--text2); font-size: 0.82rem; }
</style>
</head>
<body>

<div class="topbar">
  <div>
    <h1>⚡ Practical 8 — MySQL Schema Design &amp; PDO Connectivity</h1>
    <p style="font-size:0.82rem;opacity:0.85;margin-top:4px">
      Student: RUDRA MODI (25DCS058) &nbsp;|&nbsp; Course: ITUE203 Web Development Frameworks
    </p>
  </div>
  <span class="badge">StudentHub DB</span>
</div>

<div class="container">

  <!-- Connection Status -->
  <div class="section">
    <h2>🔌 Database Connection Status</h2>

    <div class="status-card <?= $connStatus === 'success' ? 'ok' : 'err' ?>">
      <span class="status-dot <?= $connStatus === 'success' ? 'dot-ok' : 'dot-err' ?>"></span>
      <div>
        <div class="label">MySQLi Connection</div>
        <div class="msg"><?= htmlspecialchars($connMessage) ?></div>
        <div class="meta">Server: <?= htmlspecialchars($serverInfo) ?> &nbsp;|&nbsp; Host: <?= htmlspecialchars($hostInfo) ?> &nbsp;|&nbsp; DB: studenthub</div>
      </div>
    </div>

    <div class="status-card <?= $pdoStatus === 'success' ? 'ok' : 'err' ?>">
      <span class="status-dot <?= $pdoStatus === 'success' ? 'dot-ok' : 'dot-err' ?>"></span>
      <div>
        <div class="label">PDO Connection (Intermediate Extension)</div>
        <div class="msg"><?= htmlspecialchars($pdoMessage) ?></div>
        <div class="meta">Mode: PDO::ERRMODE_EXCEPTION &nbsp;|&nbsp; Fetch: FETCH_ASSOC &nbsp;|&nbsp; Emulate Prepares: OFF</div>
      </div>
    </div>
  </div>

  <!-- Table Record Counts -->
  <div class="section">
    <h2>📊 Database Tables — Record Counts</h2>
    <div class="stats-grid">
      <?php foreach ($counts as $tbl => $cnt): ?>
      <div class="stat">
        <div class="num"><?= $cnt ?></div>
        <div class="name"><?= $tbl ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Schema Design -->
  <div class="section">
    <h2>🗄️ Schema Design — Table Relationships</h2>
    <div class="code-block">
<span class="cm">-- ER Relationships:</span>
<span class="cm">-- users        (PK: id)  &lt;─── students.user_id      [1:1]  One user = one student profile</span>
<span class="cm">-- users        (PK: id)  &lt;─── events.created_by      [1:N]  Admin creates many events</span>
<span class="cm">-- students     (PK: id)  &lt;─── registrations.student_id[N:M]  Students register for events</span>
<span class="cm">-- events       (PK: id)  &lt;─── registrations.event_id  [N:M]  Events have many registrants</span>
<span class="cm">-- users        (PK: id)  &lt;─── audit_logs.user_id      [1:N]  Track all user actions</span>

<span class="kw">CREATE TABLE</span> <span class="tp">students</span> (
    id             <span class="tp">INT UNSIGNED</span>   <span class="kw">AUTO_INCREMENT PRIMARY KEY</span>,
    user_id        <span class="tp">INT UNSIGNED</span>   <span class="kw">NOT NULL</span>,
    enrollment_no  <span class="tp">VARCHAR</span>(<span class="str">20</span>)    <span class="kw">NOT NULL UNIQUE</span>,
    course         <span class="tp">VARCHAR</span>(<span class="str">10</span>)    <span class="kw">NOT NULL</span>,
    cgpa           <span class="tp">DECIMAL</span>(<span class="str">3,2</span>)  <span class="kw">NOT NULL DEFAULT</span> <span class="str">0.00</span>,
    <span class="kw">FOREIGN KEY</span> (user_id) <span class="kw">REFERENCES</span> <span class="tp">users</span>(id)
        <span class="kw">ON DELETE CASCADE ON UPDATE CASCADE</span>
) <span class="kw">ENGINE</span>=<span class="tp">InnoDB</span>;
    </div>
  </div>

  <!-- Student Records via Prepared Statement -->
  <div class="section">
    <h2>👨‍🎓 Student Records (via MySQLi Prepared Statement + JOIN)</h2>
    <table>
      <thead>
        <tr><th>Name</th><th>Enrollment No</th><th>Course</th><th>Semester</th><th>CGPA</th></tr>
      </thead>
      <tbody>
        <?php foreach ($students as $s): ?>
        <tr>
          <td><?= htmlspecialchars($s['name']) ?></td>
          <td style="font-family:var(--mono);font-size:0.8rem"><?= htmlspecialchars($s['enrollment_no']) ?></td>
          <td><?= htmlspecialchars($s['course']) ?></td>
          <td><?= $s['semester'] ?></td>
          <td style="color:var(--accent2);font-weight:600"><?= number_format($s['cgpa'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Events Table -->
  <div class="section">
    <h2>🎯 Campus Events (via Prepared Statement)</h2>
    <table>
      <thead>
        <tr><th>Title</th><th>Category</th><th>Venue</th><th>Date</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($events as $e):
          $statusClass = match($e['status']) {
            'Open'          => 'pill-open',
            'Limited Seats' => 'pill-limited',
            'Coming Soon'   => 'pill-soon',
            default         => 'pill-closed'
          };
        ?>
        <tr>
          <td><?= htmlspecialchars($e['title']) ?></td>
          <td><?= htmlspecialchars($e['category']) ?></td>
          <td><?= htmlspecialchars($e['venue']) ?></td>
          <td style="font-family:var(--mono);font-size:0.8rem"><?= $e['event_date'] ?></td>
          <td><span class="pill <?= $statusClass ?>"><?= htmlspecialchars($e['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- PDO Prepared Statement Demo -->
  <div class="section">
    <h2>🔐 PDO Prepared Statement — Advanced Extension Demo</h2>
    <?php
    try {
        $pdoConn = getPDO();
        $q = $pdoConn->prepare("SELECT name, email, role FROM users WHERE role = :role ORDER BY id LIMIT 5");
        $q->execute([':role' => 'student']);
        $pdoRows = $q->fetchAll();
    } catch (PDOException $ex) {
        $pdoRows = [];
    }
    ?>
    <p style="color:var(--text2);font-size:0.83rem;margin-bottom:1rem">
      Query: <code style="font-family:var(--mono);color:var(--accent)">SELECT name, email, role FROM users WHERE role = :role LIMIT 5</code>
    </p>
    <table>
      <thead>
        <tr><th>Name</th><th>Email</th><th>Role</th></tr>
      </thead>
      <tbody>
        <?php foreach ($pdoRows as $row): ?>
        <tr>
          <td><?= htmlspecialchars($row['name']) ?></td>
          <td style="font-size:0.82rem;color:var(--text2)"><?= htmlspecialchars($row['email']) ?></td>
          <td><span class="pill pill-open"><?= htmlspecialchars($row['role']) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</div>

<footer>
  Practical 8 &mdash; RUDRA MODI (25DCS058) &nbsp;|&nbsp; ITUE203 Web Development Frameworks &nbsp;|&nbsp; DEPSTAR CHARUSAT 2026
</footer>

</body>
</html>
