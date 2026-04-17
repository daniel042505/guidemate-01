<?php
/**
 * Admin: update price for a tourist spot (destination).
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
$price = isset($_POST['price']) ? trim($_POST['price']) : '';

if ($destination_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid spot.']);
    exit;
}

// Ensure price column exists
$col = $mysqli->query("SHOW COLUMNS FROM destinations LIKE 'price'");
if (!$col || $col->num_rows === 0) {
    $mysqli->query("ALTER TABLE destinations ADD COLUMN price VARCHAR(30) DEFAULT NULL");
}

$stmt = $mysqli->prepare("UPDATE destinations SET price = ? WHERE destination_id = ?");
if (!$stmt) {
    echo json_encode(['ok' => false, 'error' => 'Database error.']);
    exit;
}
$stmt->bind_param('si', $price, $destination_id);
if ($stmt->execute()) {
    $stmt->close();
    echo json_encode(['ok' => true]);
} else {
    $stmt->close();
    echo json_encode(['ok' => false, 'error' => 'Could not update.']);
}
