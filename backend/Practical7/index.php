<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$file    = __DIR__ . '/registrations.csv';
$message = '';
$msgType = '';
$errors  = [];
$old     = [];

function sanitize(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = 'Security token mismatch. Please refresh the page and try again.';
        $msgType = 'error';
    } else {
        $name    = trim(filter_input(INPUT_POST, 'name',    FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $email   = trim(filter_input(INPUT_POST, 'email',   FILTER_SANITIZE_EMAIL) ?? '');
        $phone   = trim(filter_input(INPUT_POST, 'phone',   FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $course  = trim(filter_input(INPUT_POST, 'course',  FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $msg     = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

        $old = compact('name', 'email', 'phone', 'course', 'msg');

        if (strlen($name) < 3 || !preg_match('/^[A-Za-z ]+$/', $name))
            $errors[] = 'Name must be at least 3 alphabetic characters.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors[] = 'A valid email address is required.';
        if (!preg_match('/^[6-9]\d{9}$/', $phone))
            $errors[] = 'Phone must be a 10-digit Indian mobile number starting with 6–9.';
        if (!in_array($course, ['CSE', 'IT', 'ECE', 'ME', 'CE'], true))
            $errors[] = 'Please select a valid course.';
        if (strlen($msg) < 5)
            $errors[] = 'Message must be at least 5 characters.';

        if (empty($errors)) {
            $isNew = !file_exists($file) || filesize($file) === 0;
            $handle = fopen($file, 'a');
            if ($handle && flock($handle, LOCK_EX)) {
                if ($isNew) fputcsv($handle, ['Name', 'Email', 'Phone', 'Course', 'Message', 'Submitted At']);
                fputcsv($handle, [$name, $email, $phone, $course, $msg, date('Y-m-d H:i:s')]);
                flock($handle, LOCK_UN);
                fclose($handle);
                $message = "Registration submitted successfully! Welcome, <strong>" . htmlspecialchars($name) . "</strong>.";
                $msgType = 'success';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $old = [];
            } else {
                $message = 'Unable to write to storage file. Please try again.';
                $msgType = 'error';
            }
        } else {
            $message = implode('<br>', array_map(fn($e) => '&#x26A0; ' . $e, $errors));
            $msgType = 'error';
        }
    }
}

$records = [];
if (file_exists($file) && filesize($file) > 0) {
    $handle = fopen($file, 'r');
    if ($handle) {
        $header = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 5) $records[] = $row;
        }
        fclose($handle);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Practical 7 – PHP Server-Side Form Handling & CSV Storage – StudentHub CHARUSAT">
<title>Practical 7 – PHP Registration | StudentHub</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary:   #1b365d;
    --primary-l: #2563eb;
    --accent:    #0d9488;
    --danger:    #dc2626;
    --success:   #16a34a;
    --warn:      #d97706;
    --bg:        #f8fafc;
    --card:      #ffffff;
    --alt:       #f1f5f9;
    --border:    #e2e8f0;
    --text:      #1e293b;
    --text2:     #64748b;
    --mono:      'Courier New', monospace;
    --rad:       10px;
    --shadow:    0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -2px rgba(0,0,0,.1);
}
*,*::before,*::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); line-height: 1.6; }
a { color: var(--primary-l); text-decoration: none; }
a:hover { text-decoration: underline; }

.topbar {
    background: linear-gradient(135deg, var(--primary) 0%, #1e3a8a 100%);
    color: #fff; padding: 1.5rem 2rem;
    display: flex; justify-content: space-between; align-items: center;
    box-shadow: var(--shadow);
}
.topbar h1 { font-size: 1.4rem; font-weight: 700; }
.topbar p  { font-size: .82rem; opacity: .85; margin-top: 3px; }
.badge { background: var(--accent); color: #fff; padding: .35rem .9rem; border-radius: 20px; font-size: .75rem; font-weight: 600; white-space: nowrap; }

.container { max-width: 1100px; margin: 2rem auto; padding: 0 1.5rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1.75rem; }
@media (max-width: 768px) { .container { grid-template-columns: 1fr; } }
.full { grid-column: 1 / -1; }

.card { background: var(--card); border: 1px solid var(--border); border-radius: var(--rad); padding: 1.75rem; box-shadow: 0 1px 3px rgba(0,0,0,.07); }
.card h2 { font-size: 1.1rem; color: var(--primary); margin-bottom: 1.25rem; padding-bottom: .5rem; border-bottom: 2px solid var(--border); }

.alert { padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: .9rem; line-height: 1.6; }
.alert-success { background: #d1fae5; color: #064e3b; border: 1px solid #a7f3d0; }
.alert-error   { background: #fee2e2; color: #7f1d1d; border: 1px solid #fca5a5; }

label { display: block; font-weight: 600; font-size: .875rem; margin-bottom: .3rem; color: var(--text); }
input[type=text], input[type=email], input[type=tel], select, textarea {
    width: 100%; padding: .65rem .9rem; border: 1px solid #cbd5e1; border-radius: 7px;
    font-family: inherit; font-size: .9rem; background: #fff; color: var(--text);
    transition: border-color .2s, box-shadow .2s; margin-bottom: 1rem;
}
input:focus, select:focus, textarea:focus { border-color: var(--primary-l); outline: none; box-shadow: 0 0 0 3px rgba(37,99,235,.15); }
textarea { height: 110px; resize: vertical; }

.btn {
    display: inline-flex; align-items: center; gap: .5rem;
    background: var(--primary); color: #fff; font-weight: 600; font-size: .9rem;
    padding: .7rem 1.5rem; border: none; border-radius: 7px; cursor: pointer;
    transition: background .2s, transform .15s, box-shadow .2s;
    box-shadow: 0 1px 2px rgba(0,0,0,.1);
}
.btn:hover { background: var(--primary-l); transform: translateY(-1px); box-shadow: var(--shadow); }
.btn-secondary { background: #475569; }
.btn-secondary:hover { background: #334155; }

table { width: 100%; border-collapse: collapse; font-size: .875rem; }
thead tr { background: var(--primary); }
th { padding: .75rem 1rem; text-align: left; color: #fff; font-weight: 600; font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; }
td { padding: .75rem 1rem; border-bottom: 1px solid var(--border); color: var(--text); }
tr:last-child td { border-bottom: none; }
tbody tr:hover td { background: #eff6ff; }
tbody tr:nth-child(even) td { background: var(--alt); }
tbody tr:nth-child(even):hover td { background: #eff6ff; }

.empty { text-align: center; color: var(--text2); padding: 2rem; font-style: italic; }
.count-badge { display: inline-block; background: var(--primary); color: #fff; border-radius: 12px; font-size: .72rem; font-weight: 700; padding: .15rem .5rem; margin-left: .4rem; }

.info-list { list-style: none; display: flex; flex-direction: column; gap: .6rem; }
.info-list li { display: flex; align-items: flex-start; gap: .6rem; font-size: .875rem; }
.info-list li::before { content: "✓"; color: var(--success); font-weight: 700; flex-shrink: 0; margin-top: 1px; }

.code-pill { background: #0f172a; color: #a5f3fc; font-family: var(--mono); font-size: .78rem; padding: .15rem .5rem; border-radius: 4px; }

footer { text-align: center; padding: 2rem; color: var(--text2); font-size: .82rem; border-top: 1px solid var(--border); margin-top: 1rem; }
</style>
</head>
<body>

<div class="topbar">
    <div>
        <h1>⚙️ Practical 7 — PHP Server-Side Form Handling &amp; CSV Storage</h1>
        <p>Student: RUDRA MODI (25DCS058) &nbsp;|&nbsp; Course: ITUE203 Web Development Frameworks &nbsp;|&nbsp; DEPSTAR CHARUSAT 2026</p>
    </div>
    <span class="badge">PHP + CSV</span>
</div>

<div class="container">

    <div class="card">
        <h2>📝 Student Registration Form</h2>

        <?php if ($message !== ''): ?>
        <div class="alert alert-<?= $msgType ?>">
            <?= $message ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="name">Full Name *</label>
            <input type="text" id="name" name="name" placeholder="e.g., Rudra Modi" value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

            <label for="email">Email Address *</label>
            <input type="email" id="email" name="email" placeholder="e.g., rudra@charusat.edu.in" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

            <label for="phone">Mobile Number * <small>(10-digit Indian)</small></label>
            <input type="tel" id="phone" name="phone" placeholder="e.g., 8530001234" value="<?= htmlspecialchars($old['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" pattern="^[6-9]\d{9}$" maxlength="10" required>

            <label for="course">Course / Department *</label>
            <select id="course" name="course" required>
                <option value="">-- Select Course --</option>
                <?php foreach (['CSE', 'IT', 'ECE', 'ME', 'CE'] as $c): ?>
                <option value="<?= $c ?>" <?= (($old['course'] ?? '') === $c ? 'selected' : '') ?>><?= $c ?></option>
                <?php endforeach; ?>
            </select>

            <label for="message">Message / Query *</label>
            <textarea id="message" name="message" placeholder="Brief query or reason for registration..."><?= htmlspecialchars($old['msg'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

            <button type="submit" class="btn">🔐 Submit Registration</button>
        </form>
    </div>

    <div style="display:flex; flex-direction:column; gap:1.5rem;">
        <div class="card">
            <h2>🛡️ Server-Side Security Features</h2>
            <ul class="info-list">
                <li>CSRF token: <span class="code-pill">bin2hex(random_bytes(32))</span></li>
                <li>Token verified via <span class="code-pill">hash_equals()</span> (timing-safe)</li>
                <li>Inputs sanitized with <span class="code-pill">filter_input()</span> + <span class="code-pill">trim()</span></li>
                <li>Name validated with <span class="code-pill">/^[A-Za-z ]+$/</span> regex</li>
                <li>Email validated via <span class="code-pill">FILTER_VALIDATE_EMAIL</span></li>
                <li>Phone validated: <span class="code-pill">^[6-9]\d{9}$</span> (Indian)</li>
                <li>Course validated against whitelist array</li>
                <li>Data written with <span class="code-pill">fputcsv()</span> &amp; <span class="code-pill">LOCK_EX</span></li>
                <li>CSRF token rotated after each success</li>
                <li>Sticky form: repopulates values on error</li>
                <li>All output escaped with <span class="code-pill">htmlspecialchars()</span></li>
            </ul>
        </div>

        <div class="card">
            <h2>📂 CSV File I/O — How It Works</h2>
            <p style="font-size:.875rem;color:var(--text2);line-height:1.7">
                On submission, PHP opens <strong>registrations.csv</strong> using <span class="code-pill">fopen($file, 'a')</span>,
                acquires an exclusive lock via <span class="code-pill">flock(LOCK_EX)</span>, writes a new row with
                <span class="code-pill">fputcsv()</span> including a timestamp, then releases the lock and closes the stream.
                Records are read back using <span class="code-pill">fgetcsv()</span> in read mode, skipping the header row.
            </p>
        </div>
    </div>

    <div class="card full">
        <h2>📋 Stored Registrations <span class="count-badge"><?= count($records) ?></span></h2>
        <?php if (empty($records)): ?>
        <p class="empty">No registrations submitted yet. Fill the form above to add the first record.</p>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Course</th>
                    <th>Message</th>
                    <th>Submitted At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $i => $row): ?>
                <tr>
                    <td style="color:var(--text2);font-size:.8rem;"><?= $i + 1 ?></td>
                    <td style="font-weight:600;"><?= htmlspecialchars($row[0], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-size:.82rem;color:var(--text2);"><?= htmlspecialchars($row[1], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-family:monospace;"><?= htmlspecialchars($row[2], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span style="background:#dbeafe;color:#1e40af;padding:.15rem .5rem;border-radius:4px;font-size:.78rem;font-weight:600;"><?= htmlspecialchars($row[3], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($row[4], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($row[4], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-size:.78rem;color:var(--text2);font-family:monospace;"><?= htmlspecialchars($row[5] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

</div>

<footer>
    Practical 7 &mdash; RUDRA MODI (25DCS058) &nbsp;|&nbsp; ITUE203 Web Development Frameworks &nbsp;|&nbsp; DEPSTAR CHARUSAT 2026
</footer>

</body>
</html>
