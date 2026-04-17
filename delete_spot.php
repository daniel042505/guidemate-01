<?php
/**
 * Admin: toggle tourist spot availability without deleting the record.
 */
session_start();
require_once 'dbconnect.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Admin only']);
    exit;
}

$destination_id = isset($_POST['destination_id']) ? (int) $_POST['destination_id'] : 0;
$is_available = isset($_POST['is_available']) ? (int) $_POST['is_available'] : 0;

if ($destination_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid spot.']);
    exit;
}

if ($is_available !== 0 && $is_available !== 1) {
    echo json_encode(['ok' => false, 'error' => 'Invalid availability value.']);
    exit;
}

// Create the flag on older databases instead of deleting the spot.
$col = $mysqli->query("SHOW COLUMNS FROM destinations LIKE 'is_available'");
if (!$col || $col->num_rows === 0) {
    $mysqli->query("ALTER TABLE destinations ADD COLUMN is_available TINYINT(1) NOT NULL DEFAULT 1");
}

$stmt = $mysqli->prepare("UPDATE destinations SET is_available = ? WHERE destination_id = ?");
if (!$stmt) {
    echo json_encode(['ok' => false, 'error' => 'Database error.']);
    exit;
}
$stmt->bind_param('ii', $is_available, $destination_id);
if ($stmt->execute()) {
    $updated = $stmt->affected_rows;
    $stmt->close();
    echo json_encode(['ok' => true, 'is_available' => $is_available, 'updated' => $updated]);
} else {
    $stmt->close();
    echo json_encode(['ok' => false, 'error' => 'Could not update availability.']);
}
