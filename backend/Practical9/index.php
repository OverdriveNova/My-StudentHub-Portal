<?php
/* ============================================================
   Practical 9 — index.php
   Secure User Registration: DB Insert, Duplicate Email Check,
   Password Hashing with password_hash()
   Student: RUDRA MODI (25DCS058) | ITUE203
   ============================================================ */

session_start();

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include DB (path relative to Practical9 — uses Practical8 db.php)
require_once __DIR__ . '/../Practical8/db.php';

$message     = '';
$messageType = '';
$hashedPreview = '';
$formData    = [];

/* ----------------------------------------------------------
   PROCESS REGISTRATION
   ---------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* --- CSRF Check --- */
    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $message     = 'Invalid CSRF token. Please refresh and try again.';
        $messageType = 'error';
    } else {

        /* --- Collect & Sanitize Inputs --- */
        $name           = trim(filter_input(INPUT_POST, 'name',     FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $email          = trim(filter_input(INPUT_POST, 'email',    FILTER_SANITIZE_EMAIL) ?? '');
        $password       = trim($_POST['password']       ?? '');
        $confirmPass    = trim($_POST['confirm_pass']   ?? '');
        $enrollmentNo   = strtoupper(trim(filter_input(INPUT_POST, 'enrollment_no', FILTER_SANITIZE_SPECIAL_CHARS) ?? ''));
        $course         = trim(filter_input(INPUT_POST, 'course',   FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $mobile         = trim(filter_input(INPUT_POST, 'mobile',   FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $semester       = (int) ($_POST['semester'] ?? 0);

        // Keep for repopulating form on error
        $formData = compact('name','email','enrollmentNo','course','mobile','semester');

        $errors = [];

        /* --- Backend Validation --- */
        // Name
        if ($name === '') {
            $errors[] = 'Full name is required.';
        } elseif (!preg_match('/^[A-Za-z ]{2,100}$/', $name)) {
            $errors[] = 'Name must be 2–100 characters, letters only.';
        }

        // Email
        if ($email === '') {
            $errors[] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }

        // Password
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        } elseif (!preg_match('/[\W]/', $password)) {
            $errors[] = 'Password must contain at least one special character.';
        }

        // Confirm password
        if ($password !== $confirmPass) {
            $errors[] = 'Passwords do not match.';
        }

        // Enrollment No
        if ($enrollmentNo === '') {
            $errors[] = 'Enrollment number is required.';
        } elseif (!preg_match('/^\d{2}D[A-Z]{2,3}\d{3}$/', $enrollmentNo)) {
            $errors[] = 'Enrollment number format invalid (e.g., 25DCS058).';
        }

        // Course
        $allowedCourses = ['CSE','IT','ECE','ME','CE'];
        if (!in_array($course, $allowedCourses, true)) {
            $errors[] = 'Please select a valid course.';
        }

        // Mobile
        if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
            $errors[] = 'Enter a valid 10-digit Indian mobile number.';
        }

        // Semester
        if ($semester < 1 || $semester > 8) {
            $errors[] = 'Please select a valid semester (1–8).';
        }


        if (empty($errors)) {

            /* --- Duplicate Email Check (prepared statement) --- */
            $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();
            $emailExists = $stmt->num_rows > 0;
            $stmt->close();

            /* --- Duplicate Enrollment Check --- */
            $stmt = $mysqli->prepare("SELECT id FROM students WHERE enrollment_no = ?");
            $stmt->bind_param('s', $enrollmentNo);
            $stmt->execute();
            $stmt->store_result();
            $enrollExists = $stmt->num_rows > 0;
            $stmt->close();

            if ($emailExists) {
                $errors[] = "Email address already registered. <a href='../Practical8/index.php'>View existing records</a>";
            }
            if ($enrollExists) {
                $errors[] = "Enrollment number '$enrollmentNo' is already registered.";
            }

            if (empty($errors)) {

                /* --- Hash password with password_hash() --- */
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $hashedPreview  = $hashedPassword;

                /* --- Insert into users --- */
                $stmt = $mysqli->prepare(
                    "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')"
                );
                $stmt->bind_param('sss', $name, $email, $hashedPassword);

                if ($stmt->execute()) {
                    $newUserId = $mysqli->insert_id;
                    $stmt->close();

                    /* --- Derive year from semester --- */
                    $year = (int) ceil($semester / 2);

                    /* --- Insert into students --- */
                    $stmt = $mysqli->prepare(
                        "INSERT INTO students (user_id, enrollment_no, course, semester, year, mobile, cgpa)
                         VALUES (?, ?, ?, ?, ?, ?, 0.00)"
                    );
                    $stmt->bind_param('issiis', $newUserId, $enrollmentNo, $course, $semester, $year, $mobile);
                    $stmt->execute();
                    $stmt->close();

                    /* --- Audit Log --- */
                    $ip   = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                    $desc = "New student registered: $name ($email) — Enrollment: $enrollmentNo";
                    $stmt = $mysqli->prepare(
                        "INSERT INTO audit_logs (user_id, action, description, ip_address)
                         VALUES (?, 'USER_REGISTERED', ?, ?)"
                    );
                    $stmt->bind_param('iss', $newUserId, $desc, $ip);
                    $stmt->execute();
                    $stmt->close();

                    /* --- Rotate CSRF token --- */
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    $message     = "Registration successful! Welcome, " . htmlspecialchars($name) . ". Your account has been created.";
                    $messageType = 'success';
                    $formData    = [];  // clear form

                } else {
                    $stmt->close();
                    $message     = 'Database error: ' . htmlspecialchars($mysqli->error);
                    $messageType = 'error';
                }

            } else {
                $message     = implode('<br>', $errors);
                $messageType = 'error';
            }

        } else {
            $message     = implode('<br>', $errors);
            $messageType = 'error';
        }
    }
}

// Fetch recent registrants for display
$stmt = $mysqli->prepare(
    "SELECT u.name, u.email, s.enrollment_no, s.course, s.semester, u.created_at
     FROM users u JOIN students s ON s.user_id = u.id
     ORDER BY u.id DESC LIMIT 8"
);
$stmt->execute();
$recent = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$csrf = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
$f    = fn($k) => htmlspecialchars($formData[$k] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Practical 9 — Secure Registration | StudentHub</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:#0f1117;--surface:#1a1d2e;--surface2:#242740;
    --accent:#6c63ff;--accent2:#00d4aa;--danger:#ff4757;
    --success:#2ed573;--warning:#ffa502;
    --text:#e2e8f0;--text2:#94a3b8;--border:#2d3748;--radius:12px;
    --mono:'JetBrains Mono',monospace;
  }
  *{box-sizing:border-box;margin:0;padding:0;}
  body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}

  .topbar{background:linear-gradient(135deg,#7c3aed,var(--accent));padding:1.5rem 2rem;display:flex;align-items:center;gap:1rem;}
  .topbar h1{font-size:1.4rem;font-weight:700;}
  .badge{background:rgba(255,255,255,0.2);padding:.25rem .75rem;border-radius:20px;font-size:.75rem;font-weight:600;}

  .layout{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;max-width:1100px;margin:2rem auto;padding:0 1.5rem;}
  @media(max-width:768px){.layout{grid-template-columns:1fr;}}

  .card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:1.5rem;}
  .card h2{font-size:1rem;font-weight:700;color:var(--accent2);margin-bottom:1.25rem;display:flex;align-items:center;gap:.5rem;}

  label{display:block;font-size:.82rem;font-weight:500;color:var(--text2);margin-bottom:.3rem;margin-top:1rem;}
  input,select{width:100%;padding:.65rem .85rem;background:var(--surface2);border:1px solid var(--border);
    border-radius:8px;color:var(--text);font-size:.88rem;font-family:'Inter',sans-serif;transition:.2s;}
  input:focus,select:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(108,99,255,.15);}

  .btn{width:100%;padding:.85rem;margin-top:1.25rem;background:linear-gradient(135deg,#7c3aed,var(--accent));
    color:#fff;border:none;border-radius:8px;font-size:.95rem;font-weight:600;cursor:pointer;transition:.2s;}
  .btn:hover{opacity:.9;transform:translateY(-1px);}

  .alert{padding:1rem 1.25rem;border-radius:8px;margin-bottom:1rem;font-size:.88rem;line-height:1.6;}
  .alert-success{background:rgba(46,213,115,.1);border:1px solid rgba(46,213,115,.3);color:var(--success);}
  .alert-error  {background:rgba(255,71,87,.1);border:1px solid rgba(255,71,87,.3);color:var(--danger);}

  .hash-box{background:#0d1117;border:1px solid var(--border);border-radius:8px;padding:1rem;
    font-family:var(--mono);font-size:.72rem;color:var(--accent2);word-break:break-all;margin-top:.75rem;}

  .security-list li{list-style:none;padding:.4rem 0;font-size:.83rem;color:var(--text2);}
  .security-list li::before{content:"✅ ";font-size:.9rem;}

  table{width:100%;border-collapse:collapse;font-size:.82rem;}
  th{padding:.65rem 1rem;text-align:left;font-weight:600;color:var(--text2);font-size:.75rem;
    text-transform:uppercase;letter-spacing:.04em;background:var(--surface2);}
  td{padding:.65rem 1rem;border-bottom:1px solid var(--border);}
  tr:last-child td{border-bottom:none;}
  tr:hover td{background:var(--surface2);}

  .pill{display:inline-block;padding:.18rem .55rem;border-radius:20px;font-size:.7rem;font-weight:600;}
  .pill-cse{background:rgba(108,99,255,.15);color:var(--accent);}
  .pill-it {background:rgba(0,212,170,.15);color:var(--accent2);}
  .pill-ece{background:rgba(255,165,2,.15);color:var(--warning);}
  .pill-other{background:rgba(255,71,87,.15);color:var(--danger);}

  .full-width{grid-column:1/-1;}
  footer{text-align:center;padding:2rem;color:var(--text2);font-size:.82rem;}

  /* Strength meter */
  #strengthBar{height:4px;border-radius:2px;background:var(--border);margin-top:.4rem;transition:.3s;}
  #strengthBar .bar{height:100%;border-radius:2px;transition:.3s;width:0%;}
  #strengthLabel{font-size:.72rem;color:var(--text2);margin-top:.2rem;}

  /* Row 2 cols inside form */
  .form-row{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;}
  @media(max-width:480px){.form-row{grid-template-columns:1fr;}}
</style>
</head>
<body>

<div class="topbar">
  <div>
    <h1>🔐 Practical 9 — Secure User Registration</h1>
    <p style="font-size:.82rem;opacity:.85;margin-top:4px">
      DB Insert &middot; Duplicate Email Check &middot; password_hash() &middot; MySQLi Prepared Statements
      &nbsp;|&nbsp; RUDRA MODI (25DCS058)
    </p>
  </div>
  <span class="badge">ITUE203</span>
</div>

<div class="layout">

  <!-- Registration Form -->
  <div class="card">
    <h2>📝 Student Registration Form</h2>

    <?php if ($message !== ''): ?>
    <div class="alert alert-<?= $messageType ?>">
      <?= $message ?>
    </div>
    <?php endif; ?>

    <?php if ($hashedPreview !== ''): ?>
    <p style="font-size:.82rem;color:var(--text2);margin-bottom:.5rem">🔑 <strong>Hashed Password</strong> stored in DB (bcrypt cost=12):</p>
    <div class="hash-box"><?= htmlspecialchars($hashedPreview) ?></div>
    <?php endif; ?>

    <form method="POST" action="" id="regForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

      <label for="name">Full Name *</label>
      <input type="text" id="name" name="name" placeholder="e.g., Rudra Modi"
             value="<?= $f('name') ?>" required minlength="2" maxlength="100">

      <label for="email">Email Address *</label>
      <input type="email" id="email" name="email" placeholder="e.g., rudra@charusat.edu.in"
             value="<?= $f('email') ?>" required>

      <div class="form-row">
        <div>
          <label for="password">Password *</label>
          <input type="password" id="password" name="password" placeholder="Min 8 chars" required minlength="8">
          <div id="strengthBar"><div class="bar" id="sBar"></div></div>
          <div id="strengthLabel">Enter a password</div>
        </div>
        <div>
          <label for="confirm_pass">Confirm Password *</label>
          <input type="password" id="confirm_pass" name="confirm_pass" placeholder="Re-enter password" required>
          <div id="matchMsg" style="font-size:.72rem;margin-top:.3rem;"></div>
        </div>
      </div>

      <div class="form-row">
        <div>
          <label for="enrollment_no">Enrollment Number *</label>
          <input type="text" id="enrollment_no" name="enrollment_no"
                 placeholder="e.g., 25DCS058" value="<?= $f('enrollmentNo') ?>"
                 required pattern="^\d{2}D[A-Z]{2,3}\d{3}$" maxlength="10">
        </div>
        <div>
          <label for="mobile">Mobile Number *</label>
          <input type="tel" id="mobile" name="mobile"
                 placeholder="10-digit Indian number" value="<?= $f('mobile') ?>"
                 required pattern="^[6-9]\d{9}$" maxlength="10">
        </div>
      </div>

      <div class="form-row">
        <div>
          <label for="course">Course *</label>
          <select id="course" name="course" required>
            <option value="">-- Select Course --</option>
            <?php foreach (['CSE','IT','ECE','ME','CE'] as $c): ?>
            <option value="<?= $c ?>" <?= ($f('course') === $c ? 'selected' : '') ?>><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="semester">Semester *</label>
          <select id="semester" name="semester" required>
            <option value="">-- Select --</option>
            <?php for ($i=1; $i<=8; $i++): ?>
            <option value="<?= $i ?>" <?= (($formData['semester'] ?? '') == $i ? 'selected' : '') ?>>Semester <?= $i ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>

      <button type="submit" class="btn">🔐 Register Securely</button>
    </form>
  </div>

  <!-- Security Info Panel -->
  <div style="display:flex;flex-direction:column;gap:1.5rem;">

    <div class="card">
      <h2>🛡️ Security Implementation Checklist</h2>
      <ul class="security-list">
        <li>CSRF token generated with bin2hex(random_bytes(32))</li>
        <li>CSRF token validated with hash_equals() (timing-safe)</li>
        <li>Inputs sanitized with filter_input() + trim()</li>
        <li>Server-side regex validation for all fields</li>
        <li>Email validated with filter_var(FILTER_VALIDATE_EMAIL)</li>
        <li>Mobile validated: ^[6-9]\d{9}$ (Indian format)</li>
        <li>Duplicate email checked via prepared statement</li>
        <li>Duplicate enrollment_no checked independently</li>
        <li>Password hashed with password_hash(BCrypt, cost=12)</li>
        <li>Raw password never stored or logged</li>
        <li>DB inserts via MySQLi prepared statements</li>
        <li>All output escaped with htmlspecialchars()</li>
        <li>Audit log entry created on every registration</li>
        <li>CSRF token rotated after each successful submit</li>
      </ul>
    </div>

    <div class="card">
      <h2>🔑 Password Hashing Info</h2>
      <p style="font-size:.83rem;color:var(--text2);line-height:1.7">
        PHP <strong style="color:var(--accent)">password_hash()</strong> uses BCrypt algorithm with
        <strong>cost factor 12</strong> — each hash takes ~250ms to compute, making brute-force attacks
        computationally expensive.<br><br>
        <strong style="color:var(--accent2)">Verification</strong> uses
        <code style="font-family:var(--mono)">password_verify()</code> which is timing-safe.<br><br>
        Hash format: <code style="font-family:var(--mono);font-size:.75rem;color:var(--warning)">$2y$12$[22-char salt][31-char hash]</code>
      </p>
    </div>
  </div>

  <!-- Recent Registrants -->
  <div class="card full-width">
    <h2>📋 Recently Registered Students</h2>
    <table>
      <thead>
        <tr><th>Name</th><th>Email</th><th>Enrollment No</th><th>Course</th><th>Sem</th><th>Registered At</th></tr>
      </thead>
      <tbody>
        <?php if (empty($recent)): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--text2);padding:2rem">No students registered yet.</td></tr>
        <?php else: foreach ($recent as $r):
          $pillClass = match($r['course']) {
            'CSE' => 'pill-cse', 'IT' => 'pill-it', 'ECE' => 'pill-ece', default => 'pill-other'
          };
        ?>
        <tr>
          <td style="font-weight:500"><?= htmlspecialchars($r['name']) ?></td>
          <td style="font-size:.78rem;color:var(--text2)"><?= htmlspecialchars($r['email']) ?></td>
          <td style="font-family:var(--mono);font-size:.78rem"><?= htmlspecialchars($r['enrollment_no']) ?></td>
          <td><span class="pill <?= $pillClass ?>"><?= htmlspecialchars($r['course']) ?></span></td>
          <td><?= (int)$r['semester'] ?></td>
          <td style="font-size:.75rem;color:var(--text2)"><?= htmlspecialchars($r['created_at']) ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

</div>

<footer>
  Practical 9 &mdash; RUDRA MODI (25DCS058) &nbsp;|&nbsp; ITUE203 Web Development Frameworks &nbsp;|&nbsp; DEPSTAR CHARUSAT 2026
</footer>

<script>
/* --- Live Password Strength Meter --- */
const pwdInput = document.getElementById('password');
const sBar     = document.getElementById('sBar');
const sLabel   = document.getElementById('strengthLabel');
const confInput= document.getElementById('confirm_pass');
const matchMsg = document.getElementById('matchMsg');

pwdInput.addEventListener('input', () => {
  const v = pwdInput.value;
  let score = 0;
  if (v.length >= 8) score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[\W]/.test(v)) score++;
  const levels = [
    {pct:'0%', color:'transparent', label:'Enter a password'},
    {pct:'25%',color:'#ff4757', label:'Weak'},
    {pct:'50%',color:'#ffa502', label:'Fair'},
    {pct:'75%',color:'#2ed573', label:'Strong'},
    {pct:'100%',color:'#00d4aa',label:'Very Strong 🔒'},
  ];
  const l = levels[score];
  sBar.style.width = l.pct;
  sBar.style.background = l.color;
  sLabel.textContent = l.label;
  sLabel.style.color = l.color;
  checkMatch();
});

confInput.addEventListener('input', checkMatch);
function checkMatch() {
  if (confInput.value === '') { matchMsg.textContent=''; return; }
  if (pwdInput.value === confInput.value) {
    matchMsg.textContent = '✅ Passwords match';
    matchMsg.style.color = '#2ed573';
  } else {
    matchMsg.textContent = '❌ Passwords do not match';
    matchMsg.style.color = '#ff4757';
  }
}
</script>
</body>
</html>
