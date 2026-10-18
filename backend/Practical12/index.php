<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$dbPath = file_exists(__DIR__ . '/db.php') ? __DIR__ . '/db.php' : __DIR__ . '/../Practical8/db.php';
require_once $dbPath;

$uploadDir = __DIR__ . '/uploads/posters/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$htaccessPath = __DIR__ . '/uploads/.htaccess';
if (!file_exists($htaccessPath)) {
    file_put_contents($htaccessPath, "<FilesMatch \"\\.(php|phtml|php3|php4|php5|php7|phps)$\">\nOrder Deny,Allow\nDeny from all\n</FilesMatch>\nOptions -Indexes\n");
}

$flashMessage = '';
$flashType = '';

function validateAndUploadPoster($file, $uploadDir, &$error) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload failed with error code ' . $file['error'];
        return false;
    }
    $maxSize = 2 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        $error = 'Poster size exceeds maximum limit of 2MB (' . round($file['size'] / 1024 / 1024, 2) . 'MB uploaded).';
        return false;
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes, true)) {
        $error = 'Invalid image type: ' . htmlspecialchars($mimeType) . '. Only JPG, PNG, and WEBP formats are allowed.';
        return false;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowedExts, true)) {
        $error = 'Invalid file extension: .' . htmlspecialchars($ext);
        return false;
    }

    $safeName = 'poster_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $uploadDir . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        $error = 'Failed to save uploaded file to destination directory.';
        return false;
    }

    return 'uploads/posters/' . $safeName;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $flashMessage = 'CSRF validation error. Action aborted.';
        $flashType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
            $description = trim($_POST['description'] ?? '');
            $category = trim(filter_input(INPUT_POST, 'category', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
            $venue = trim(filter_input(INPUT_POST, 'venue', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
            $eventDate = trim($_POST['event_date'] ?? '');
            $status = in_array($_POST['status'] ?? '', ['Open', 'Limited Seats', 'Coming Soon', 'Closed'], true) ? $_POST['status'] : 'Open';
            $createdBy = (int)($_SESSION['user_id'] ?? 1);

            if (empty($title) || empty($category) || empty($venue) || empty($eventDate)) {
                $flashMessage = 'Please complete all required event fields.';
                $flashType = 'error';
            } else {
                $uploadErr = '';
                $posterPath = validateAndUploadPoster($_FILES['poster'] ?? null, $uploadDir, $uploadErr);

                if ($uploadErr !== '') {
                    $flashMessage = $uploadErr;
                    $flashType = 'error';
                } else {
                    $stmt = $mysqli->prepare("
                        INSERT INTO events (title, description, category, venue, event_date, status, poster_path, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("sssssssi", $title, $description, $category, $venue, $eventDate, $status, $posterPath, $createdBy);

                    if ($stmt->execute()) {
                        $newEventId = $mysqli->insert_id;
                        $stmt->close();

                        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                        $logS = $mysqli->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, 'EVENT_CREATED', ?, ?)");
                        if ($logS) {
                            $desc = "Event #{$newEventId} '{$title}' created with status '{$status}'";
                            $logS->bind_param("iss", $createdBy, $desc, $ip);
                            $logS->execute();
                            $logS->close();
                        }

                        $flashMessage = "Event '{$title}' successfully published!";
                        $flashType = 'success';
                    } else {
                        $flashMessage = 'Database insert failed: ' . htmlspecialchars($mysqli->error);
                        $flashType = 'error';
                    }
                }
            }
        } elseif ($action === 'update') {
            $eventId = (int)($_POST['event_id'] ?? 0);
            $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
            $description = trim($_POST['description'] ?? '');
            $category = trim(filter_input(INPUT_POST, 'category', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
            $venue = trim(filter_input(INPUT_POST, 'venue', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
            $eventDate = trim($_POST['event_date'] ?? '');
            $status = in_array($_POST['status'] ?? '', ['Open', 'Limited Seats', 'Coming Soon', 'Closed'], true) ? $_POST['status'] : 'Open';

            if ($eventId <= 0 || empty($title) || empty($category) || empty($venue) || empty($eventDate)) {
                $flashMessage = 'All required event fields must be filled.';
                $flashType = 'error';
            } else {
                $cur = $mysqli->prepare("SELECT poster_path FROM events WHERE id = ? LIMIT 1");
                $cur->bind_param("i", $eventId);
                $cur->execute();
                $curRow = $cur->get_result()->fetch_assoc();
                $cur->close();

                $posterPath = $curRow['poster_path'] ?? null;
                $uploadErr = '';

                if (!empty($_FILES['poster']['name'])) {
                    $newPoster = validateAndUploadPoster($_FILES['poster'], $uploadDir, $uploadErr);
                    if ($uploadErr !== '') {
                        $flashMessage = $uploadErr;
                        $flashType = 'error';
                    } else {
                        if (!empty($posterPath)) {
                            $oldPhysical = __DIR__ . '/' . $posterPath;
                            if (file_exists($oldPhysical)) {
                                @unlink($oldPhysical);
                            }
                        }
                        $posterPath = $newPoster;
                    }
                }

                if ($uploadErr === '') {
                    $stmt = $mysqli->prepare("
                        UPDATE events
                        SET title = ?, description = ?, category = ?, venue = ?, event_date = ?, status = ?, poster_path = ?
                        WHERE id = ?
                    ");
                    $stmt->bind_param("sssssssi", $title, $description, $category, $venue, $eventDate, $status, $posterPath, $eventId);

                    if ($stmt->execute()) {
                        $stmt->close();

                        $adminId = (int)($_SESSION['user_id'] ?? 1);
                        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                        $logS = $mysqli->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, 'EVENT_UPDATED', ?, ?)");
                        if ($logS) {
                            $desc = "Event #{$eventId} '{$title}' updated";
                            $logS->bind_param("iss", $adminId, $desc, $ip);
                            $logS->execute();
                            $logS->close();
                        }

                        $flashMessage = "Event #{$eventId} updated successfully!";
                        $flashType = 'success';
                    } else {
                        $flashMessage = 'Database update failed: ' . htmlspecialchars($mysqli->error);
                        $flashType = 'error';
                    }
                }
            }
        } elseif ($action === 'delete') {
            $eventId = (int)($_POST['event_id'] ?? 0);
            if ($eventId > 0) {
                $cur = $mysqli->prepare("SELECT title, poster_path FROM events WHERE id = ? LIMIT 1");
                $cur->bind_param("i", $eventId);
                $cur->execute();
                $curRow = $cur->get_result()->fetch_assoc();
                $cur->close();

                if ($curRow) {
                    if (!empty($curRow['poster_path'])) {
                        $phys = __DIR__ . '/' . $curRow['poster_path'];
                        if (file_exists($phys)) {
                            @unlink($phys);
                        }
                    }

                    $del = $mysqli->prepare("DELETE FROM events WHERE id = ?");
                    $del->bind_param("i", $eventId);
                    if ($del->execute()) {
                        $del->close();

                        $adminId = (int)($_SESSION['user_id'] ?? 1);
                        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                        $logS = $mysqli->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, 'EVENT_DELETED', ?, ?)");
                        if ($logS) {
                            $desc = "Event #{$eventId} '{$curRow['title']}' deleted";
                            $logS->bind_param("iss", $adminId, $desc, $ip);
                            $logS->execute();
                            $logS->close();
                        }

                        $flashMessage = "Event #{$eventId} has been removed.";
                        $flashType = 'info';
                    } else {
                        $flashMessage = 'Database delete failed: ' . htmlspecialchars($mysqli->error);
                        $flashType = 'error';
                    }
                }
            }
        }
    }
}

$search = trim($_GET['search'] ?? '');
$filterCategory = trim($_GET['category'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$sql = "
    SELECT e.id, e.title, e.description, e.category, e.venue, e.event_date, e.status, e.poster_path, e.created_at,
           u.name AS creator_name,
           COUNT(r.id) AS registration_count
    FROM events e
    LEFT JOIN users u ON u.id = e.created_by
    LEFT JOIN registrations r ON r.event_id = e.id
    WHERE 1=1
";
$types = "";
$params = [];

if ($search !== '') {
    $sql .= " AND (e.title LIKE ? OR e.venue LIKE ? OR e.description LIKE ?)";
    $searchWild = '%' . $search . '%';
    $types .= "sss";
    $params[] = $searchWild;
    $params[] = $searchWild;
    $params[] = $searchWild;
}
if ($filterCategory !== '') {
    $sql .= " AND e.category = ?";
    $types .= "s";
    $params[] = $filterCategory;
}
if ($filterStatus !== '') {
    $sql .= " AND e.status = ?";
    $types .= "s";
    $params[] = $filterStatus;
}

$sql .= " GROUP BY e.id ORDER BY e.event_date ASC";

$stmt = $mysqli->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalEvents = count($events);
$openEvents = 0;
$totalRegs = 0;
$categoriesSet = [];
foreach ($events as $ev) {
    if ($ev['status'] === 'Open') $openEvents++;
    $totalRegs += (int)$ev['registration_count'];
    $categoriesSet[$ev['category']] = true;
}

$csrf = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Practical 12 — Event Management & Poster Upload | StudentHub</title>
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
  background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%);
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
  border-color: var(--accent);
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
.btn-primary { background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%); color: #ffffff; }
.btn-primary:hover { opacity: 0.9; }
.btn-secondary { background: var(--surface2); border: 1px solid var(--border); color: var(--text); }
.btn-secondary:hover { background: var(--surface3); }
.btn-danger { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
.btn-danger:hover { background: rgba(239, 68, 68, 0.3); color: #ffffff; }

.event-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
  gap: 1.5rem;
}
@media (max-width: 768px) {
  .event-grid { grid-template-columns: 1fr; }
}
.event-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: transform 0.2s, box-shadow 0.2s;
}
.event-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
}
.poster-container {
  position: relative;
  width: 100%;
  height: 190px;
  background: var(--surface2);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  cursor: pointer;
}
.poster-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s;
}
.event-card:hover .poster-img {
  transform: scale(1.04);
}
.poster-fallback {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: var(--text-muted);
  gap: 0.5rem;
  font-size: 0.85rem;
}
.badge-status {
  position: absolute;
  top: 12px;
  right: 12px;
  padding: 0.3rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  backdrop-filter: blur(8px);
}
.status-Open          { background: rgba(34, 197, 94, 0.85); color: #ffffff; }
.status-Limited-Seats { background: rgba(245, 158, 11, 0.85); color: #ffffff; }
.status-Coming-Soon   { background: rgba(99, 102, 241, 0.85); color: #ffffff; }
.status-Closed        { background: rgba(239, 68, 68, 0.85); color: #ffffff; }

.event-body {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  flex: 1;
}
.event-category {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  color: #db2777;
  letter-spacing: 0.05em;
  margin-bottom: 0.4rem;
}
.event-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 0.6rem;
}
.event-desc {
  font-size: 0.85rem;
  color: var(--text-muted);
  margin-bottom: 1rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  flex: 1;
}
.event-meta {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  font-size: 0.8rem;
  color: var(--text-muted);
  border-top: 1px solid var(--border);
  padding-top: 0.8rem;
  margin-bottom: 1rem;
}
.meta-row {
  display: flex;
  justify-content: space-between;
}
.event-actions {
  display: flex;
  gap: 0.6rem;
}
.event-actions .btn {
  flex: 1;
}

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
  max-width: 620px;
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
.preview-box {
  width: 100%;
  height: 120px;
  background: var(--surface2);
  border: 1px dashed var(--border);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  margin-top: 0.5rem;
}
.preview-box img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}
</style>
</head>
<body>

<header class="header">
  <div class="header-brand">
    <h1>StudentHub Portal</h1>
    <span class="header-badge">Practical 12</span>
  </div>
  <nav class="header-nav">
    <a href="../Practical7/index.php">P7: Form CSV</a>
    <a href="../Practical8/index.php">P8: DB Model</a>
    <a href="../Practical9/index.php">P9: Register</a>
    <a href="../Practical10/index.php">P10: Auth</a>
    <a href="../Practical11/index.php">P11: Students</a>
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
      <span class="stat-label">Total Events</span>
      <span class="stat-value"><?= $totalEvents ?></span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Open for Registration</span>
      <span class="stat-value" style="color:var(--success);"><?= $openEvents ?></span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Active Categories</span>
      <span class="stat-value" style="color:#db2777;"><?= count($categoriesSet) ?></span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Total Registrations</span>
      <span class="stat-value" style="color:var(--teal);"><?= $totalRegs ?></span>
    </div>
  </div>

  <div class="card">
    <div class="toolbar">
      <form action="index.php" method="GET" class="filter-form">
        <input type="text" name="search" class="input-control" placeholder="Search event title, venue..." value="<?= htmlspecialchars($search) ?>" style="min-width: 240px;">

        <select name="category" class="input-control">
          <option value="">All Categories</option>
          <option value="Technical" <?= $filterCategory === 'Technical' ? 'selected' : '' ?>>Technical</option>
          <option value="Sports"    <?= $filterCategory === 'Sports'    ? 'selected' : '' ?>>Sports</option>
          <option value="Cultural"  <?= $filterCategory === 'Cultural'  ? 'selected' : '' ?>>Cultural</option>
          <option value="Career"    <?= $filterCategory === 'Career'    ? 'selected' : '' ?>>Career</option>
        </select>

        <select name="status" class="input-control">
          <option value="">All Statuses</option>
          <option value="Open"          <?= $filterStatus === 'Open'          ? 'selected' : '' ?>>Open</option>
          <option value="Limited Seats" <?= $filterStatus === 'Limited Seats' ? 'selected' : '' ?>>Limited Seats</option>
          <option value="Coming Soon"   <?= $filterStatus === 'Coming Soon'   ? 'selected' : '' ?>>Coming Soon</option>
          <option value="Closed"        <?= $filterStatus === 'Closed'        ? 'selected' : '' ?>>Closed</option>
        </select>

        <button type="submit" class="btn btn-secondary">Filter Events</button>
        <?php if ($search !== '' || $filterCategory !== '' || $filterStatus !== ''): ?>
          <a href="index.php" class="btn btn-secondary" style="color:var(--text-muted);">Reset</a>
        <?php endif; ?>
      </form>

      <button class="btn btn-primary" onclick="openAddModal()">
        + Publish New Event
      </button>
    </div>
  </div>

  <?php if (empty($events)): ?>
    <div class="card" style="text-align: center; padding: 3rem;">
      <p style="color:var(--text-muted); font-size:1rem;">No events match your search or filter parameters.</p>
    </div>
  <?php else: ?>
    <div class="event-grid">
      <?php foreach ($events as $ev): ?>
        <?php
          $cleanStatus = str_replace(' ', '-', $ev['status']);
          $posterSrc = !empty($ev['poster_path']) && file_exists(__DIR__ . '/' . $ev['poster_path'])
              ? htmlspecialchars($ev['poster_path'])
              : '';
        ?>
        <div class="event-card">
          <div class="poster-container" onclick="openPosterModal('<?= $posterSrc ?>', '<?= htmlspecialchars(addslashes($ev['title'])) ?>')">
            <?php if (!empty($posterSrc)): ?>
              <img src="<?= $posterSrc ?>" alt="<?= htmlspecialchars($ev['title']) ?>" class="poster-img" loading="lazy">
            <?php else: ?>
              <div class="poster-fallback">
                <span style="font-size:2rem;">&#128197;</span>
                <span>No Poster Uploaded</span>
              </div>
            <?php endif; ?>
            <span class="badge-status status-<?= $cleanStatus ?>"><?= htmlspecialchars($ev['status']) ?></span>
          </div>

          <div class="event-body">
            <div class="event-category"><?= htmlspecialchars($ev['category']) ?></div>
            <h2 class="event-title"><?= htmlspecialchars($ev['title']) ?></h2>
            <p class="event-desc"><?= htmlspecialchars($ev['description'] ?? 'No description provided.') ?></p>

            <div class="event-meta">
              <div class="meta-row">
                <span>Date:</span>
                <strong style="color:var(--text); font-family:var(--mono);"><?= htmlspecialchars($ev['event_date']) ?></strong>
              </div>
              <div class="meta-row">
                <span>Venue:</span>
                <span><?= htmlspecialchars($ev['venue'] ?? 'TBA') ?></span>
              </div>
              <div class="meta-row">
                <span>Registrations:</span>
                <strong style="color:var(--teal);"><?= (int)$ev['registration_count'] ?> registered</strong>
              </div>
            </div>

            <div class="event-actions">
              <button class="btn btn-secondary" onclick='openEditModal(<?= json_encode($ev, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>Edit</button>
              <button class="btn btn-danger" onclick="confirmDelete(<?= (int)$ev['id'] ?>, '<?= htmlspecialchars(addslashes($ev['title'])) ?>')">Delete</button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>

<div class="modal" id="addModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Create New Campus Event</h3>
      <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
    </div>
    <form action="index.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="create">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

      <div class="form-grid">
        <div class="form-group full">
          <label>Event Title *</label>
          <input type="text" name="title" class="input-control" style="width:100%;" required placeholder="e.g. AI & Robotics Symposium 2026">
        </div>

        <div class="form-group">
          <label>Category *</label>
          <select name="category" class="input-control" style="width:100%;" required>
            <option value="Technical">Technical</option>
            <option value="Sports">Sports</option>
            <option value="Cultural">Cultural</option>
            <option value="Career">Career</option>
          </select>
        </div>

        <div class="form-group">
          <label>Event Date *</label>
          <input type="date" name="event_date" class="input-control" style="width:100%;" required value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
        </div>

        <div class="form-group full">
          <label>Campus Venue *</label>
          <input type="text" name="venue" class="input-control" style="width:100%;" required placeholder="Auditorium Block A">
        </div>

        <div class="form-group">
          <label>Registration Status *</label>
          <select name="status" class="input-control" style="width:100%;" required>
            <option value="Open">Open</option>
            <option value="Limited Seats">Limited Seats</option>
            <option value="Coming Soon">Coming Soon</option>
            <option value="Closed">Closed</option>
          </select>
        </div>

        <div class="form-group">
          <label>Event Poster (Max 2MB: JPG, PNG, WEBP)</label>
          <input type="file" name="poster" class="input-control" style="width:100%;" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        </div>

        <div class="form-group full">
          <label>Detailed Description</label>
          <textarea name="description" rows="3" class="input-control" style="width:100%; resize:vertical;" placeholder="Outline agenda, rules, eligible batches..."></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Publish Event</button>
      </div>
    </form>
  </div>
</div>

<div class="modal" id="editModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Edit Event Details</h3>
      <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
    </div>
    <form action="index.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="event_id" id="editEventId">

      <div class="form-grid">
        <div class="form-group full">
          <label>Event Title *</label>
          <input type="text" name="title" id="editTitle" class="input-control" style="width:100%;" required>
        </div>

        <div class="form-group">
          <label>Category *</label>
          <select name="category" id="editCategory" class="input-control" style="width:100%;" required>
            <option value="Technical">Technical</option>
            <option value="Sports">Sports</option>
            <option value="Cultural">Cultural</option>
            <option value="Career">Career</option>
          </select>
        </div>

        <div class="form-group">
          <label>Event Date *</label>
          <input type="date" name="event_date" id="editEventDate" class="input-control" style="width:100%;" required>
        </div>

        <div class="form-group full">
          <label>Campus Venue *</label>
          <input type="text" name="venue" id="editVenue" class="input-control" style="width:100%;" required>
        </div>

        <div class="form-group">
          <label>Registration Status *</label>
          <select name="status" id="editStatus" class="input-control" style="width:100%;" required>
            <option value="Open">Open</option>
            <option value="Limited Seats">Limited Seats</option>
            <option value="Coming Soon">Coming Soon</option>
            <option value="Closed">Closed</option>
          </select>
        </div>

        <div class="form-group">
          <label>Replace Poster (Leave blank to keep current)</label>
          <input type="file" name="poster" class="input-control" style="width:100%;" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        </div>

        <div class="form-group full" id="currentPosterPreviewContainer" style="display:none;">
          <label>Current Poster Thumbnail</label>
          <div class="preview-box">
            <img id="currentPosterImg" src="" alt="Poster preview">
          </div>
        </div>

        <div class="form-group full">
          <label>Detailed Description</label>
          <textarea name="description" id="editDescription" rows="3" class="input-control" style="width:100%; resize:vertical;"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Event</button>
      </div>
    </form>
  </div>
</div>

<div class="modal" id="deleteModal">
  <div class="modal-content" style="max-width:440px;">
    <div class="modal-header">
      <h3 style="color:var(--danger);">Confirm Event Deletion</h3>
      <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
    </div>
    <form action="index.php" method="POST">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="event_id" id="delEventId">

      <p style="font-size:0.9rem; color:var(--text-muted); margin-bottom:1.25rem;">
        Are you certain you want to permanently delete <strong id="delEventTitle" style="color:var(--text);"></strong>? The associated poster image and registrations will also be removed.
      </p>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
        <button type="submit" class="btn btn-danger" style="background:#dc2626; color:#ffffff;">Yes, Delete Event</button>
      </div>
    </form>
  </div>
</div>

<div class="modal" id="posterModal">
  <div class="modal-content" style="max-width:700px; padding:1rem; text-align:center;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
      <h3 id="posterModalTitle" style="font-size:1rem;">Event Poster</h3>
      <button class="modal-close" onclick="closeModal('posterModal')">&times;</button>
    </div>
    <img id="posterModalImg" src="" alt="Event Poster" style="max-width:100%; max-height:75vh; border-radius:8px; object-fit:contain;">
  </div>
</div>

<script>
function openAddModal() {
  document.getElementById('addModal').classList.add('open');
}

function openEditModal(ev) {
  document.getElementById('editEventId').value = ev.id;
  document.getElementById('editTitle').value = ev.title;
  document.getElementById('editCategory').value = ev.category;
  document.getElementById('editEventDate').value = ev.event_date;
  document.getElementById('editVenue').value = ev.venue || '';
  document.getElementById('editStatus').value = ev.status;
  document.getElementById('editDescription').value = ev.description || '';

  const previewBox = document.getElementById('currentPosterPreviewContainer');
  const previewImg = document.getElementById('currentPosterImg');
  if (ev.poster_path) {
    previewImg.src = ev.poster_path;
    previewBox.style.display = 'block';
  } else {
    previewBox.style.display = 'none';
  }

  document.getElementById('editModal').classList.add('open');
}

function confirmDelete(id, title) {
  document.getElementById('delEventId').value = id;
  document.getElementById('delEventTitle').textContent = title;
  document.getElementById('deleteModal').classList.add('open');
}

function openPosterModal(src, title) {
  if (!src) return;
  document.getElementById('posterModalImg').src = src;
  document.getElementById('posterModalTitle').textContent = title;
  document.getElementById('posterModal').classList.add('open');
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
