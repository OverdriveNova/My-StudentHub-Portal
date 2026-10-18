<?php
header('Content-Type: application/json; charset=utf-8');

$dbPath = file_exists(__DIR__ . '/db.php') ? __DIR__ . '/db.php' : __DIR__ . '/../Practical8/db.php';
require_once $dbPath;

function respond($success, $data = null, $message = '', $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success'   => $success,
        'data'      => $data,
        'message'   => $message,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true) ?? [];
$payload = array_merge($_POST, $jsonInput);

if ($method === 'GET') {
    if ($action === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            respond(false, null, 'Invalid or missing event ID.', 400);
        }

        $stmt = $mysqli->prepare("
            SELECT e.id, e.title, e.description, e.category, e.venue, e.event_date, e.status, e.poster_path, e.created_at,
                   u.name AS creator_name,
                   COUNT(r.id) AS registration_count
            FROM events e
            LEFT JOIN users u ON u.id = e.created_by
            LEFT JOIN registrations r ON r.event_id = e.id
            WHERE e.id = ?
            GROUP BY e.id
            LIMIT 1
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $event = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($event) {
            respond(true, $event, 'Event details retrieved successfully.');
        } else {
            respond(false, null, 'Event not found.', 404);
        }
    } else {
        $search = trim($_GET['search'] ?? '');
        $category = trim($_GET['category'] ?? '');
        $status = trim($_GET['status'] ?? '');

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
            $wild = '%' . $search . '%';
            $types .= "sss";
            $params[] = $wild;
            $params[] = $wild;
            $params[] = $wild;
        }
        if ($category !== '') {
            $sql .= " AND e.category = ?";
            $types .= "s";
            $params[] = $category;
        }
        if ($status !== '') {
            $sql .= " AND e.status = ?";
            $types .= "s";
            $params[] = $status;
        }

        $sql .= " GROUP BY e.id ORDER BY e.event_date ASC";

        $stmt = $mysqli->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        respond(true, $events, 'Events list loaded.');
    }
} elseif ($method === 'POST') {
    if ($action === 'create') {
        $title = trim($payload['title'] ?? '');
        $description = trim($payload['description'] ?? '');
        $category = trim($payload['category'] ?? '');
        $venue = trim($payload['venue'] ?? '');
        $eventDate = trim($payload['event_date'] ?? '');
        $status = in_array($payload['status'] ?? '', ['Open', 'Limited Seats', 'Coming Soon', 'Closed'], true) ? $payload['status'] : 'Open';
        $createdBy = 1;

        if (empty($title) || empty($category) || empty($venue) || empty($eventDate)) {
            respond(false, null, 'Title, Category, Venue, and Date are required.', 400);
        }

        $stmt = $mysqli->prepare("
            INSERT INTO events (title, description, category, venue, event_date, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssssi", $title, $description, $category, $venue, $eventDate, $status, $createdBy);

        if ($stmt->execute()) {
            $newId = $mysqli->insert_id;
            $stmt->close();

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $log = $mysqli->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, 'API_EVENT_CREATED', ?, ?)");
            if ($log) {
                $desc = "Async API created event #{$newId} '{$title}'";
                $log->bind_param("iss", $createdBy, $desc, $ip);
                $log->execute();
                $log->close();
            }

            $fetchStmt = $mysqli->prepare("
                SELECT e.*, u.name AS creator_name, 0 AS registration_count
                FROM events e
                LEFT JOIN users u ON u.id = e.created_by
                WHERE e.id = ?
            ");
            $fetchStmt->bind_param("i", $newId);
            $fetchStmt->execute();
            $newEvent = $fetchStmt->get_result()->fetch_assoc();
            $fetchStmt->close();

            respond(true, $newEvent, "Event '{$title}' created successfully via JSON API.", 201);
        } else {
            respond(false, null, 'Database execution error: ' . $mysqli->error, 500);
        }
    } elseif ($action === 'update') {
        $id = (int)($payload['id'] ?? 0);
        $title = trim($payload['title'] ?? '');
        $description = trim($payload['description'] ?? '');
        $category = trim($payload['category'] ?? '');
        $venue = trim($payload['venue'] ?? '');
        $eventDate = trim($payload['event_date'] ?? '');
        $status = in_array($payload['status'] ?? '', ['Open', 'Limited Seats', 'Coming Soon', 'Closed'], true) ? $payload['status'] : 'Open';

        if ($id <= 0 || empty($title) || empty($category) || empty($venue) || empty($eventDate)) {
            respond(false, null, 'Invalid event payload or missing required fields.', 400);
        }

        $stmt = $mysqli->prepare("
            UPDATE events
            SET title = ?, description = ?, category = ?, venue = ?, event_date = ?, status = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssssssi", $title, $description, $category, $venue, $eventDate, $status, $id);

        if ($stmt->execute()) {
            $stmt->close();

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $uid = 1;
            $log = $mysqli->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, 'API_EVENT_UPDATED', ?, ?)");
            if ($log) {
                $desc = "Async API updated event #{$id} '{$title}'";
                $log->bind_param("iss", $uid, $desc, $ip);
                $log->execute();
                $log->close();
            }

            $fetchStmt = $mysqli->prepare("
                SELECT e.*, u.name AS creator_name, COUNT(r.id) AS registration_count
                FROM events e
                LEFT JOIN users u ON u.id = e.created_by
                LEFT JOIN registrations r ON r.event_id = e.id
                WHERE e.id = ?
                GROUP BY e.id
            ");
            $fetchStmt->bind_param("i", $id);
            $fetchStmt->execute();
            $updatedEvent = $fetchStmt->get_result()->fetch_assoc();
            $fetchStmt->close();

            respond(true, $updatedEvent, "Event #{$id} updated successfully via JSON API.");
        } else {
            respond(false, null, 'Failed to update record: ' . $mysqli->error, 500);
        }
    } elseif ($action === 'delete') {
        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) {
            respond(false, null, 'Invalid event ID.', 400);
        }

        $stmt = $mysqli->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $stmt->close();

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $uid = 1;
            $log = $mysqli->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, 'API_EVENT_DELETED', ?, ?)");
            if ($log) {
                $desc = "Async API deleted event #{$id}";
                $log->bind_param("iss", $uid, $desc, $ip);
                $log->execute();
                $log->close();
            }

            respond(true, ['id' => $id], "Event #{$id} deleted successfully.");
        } else {
            respond(false, null, 'Database delete failed: ' . $mysqli->error, 500);
        }
    } else {
        respond(false, null, 'Unsupported action for POST request.', 400);
    }
} else {
    respond(false, null, 'HTTP Method not supported.', 405);
}
