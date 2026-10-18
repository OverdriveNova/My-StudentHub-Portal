<?php
/* ============================================================
   db.php — Database Connection (MySQLi)
   Practical 8: MySQL Schema Design, ER Model, PDO Connectivity
   Student: RUDRA MODI (25DCS058) | ITUE203
   ============================================================ */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'studenthub');
define('DB_PORT', 3306);

/* ----------------------------------------------------------
   MySQLi Connection
   ---------------------------------------------------------- */
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($mysqli->connect_errno) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'error'   => 'Database connection failed: ' . $mysqli->connect_error
    ]));
}

$mysqli->set_charset('utf8mb4');


/* ----------------------------------------------------------
   PDO Connection (for Intermediate/Advanced extension)
   ---------------------------------------------------------- */
function getPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn  = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4;port=' . DB_PORT;
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
    }
    return $pdo;
}
?>
