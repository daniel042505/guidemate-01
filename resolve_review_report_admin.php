<?php
session_start();
require_once 'dbconnect.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Admin only']);
    exit;
}

$reviewId = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
$action = isset($_POST['action']) ? trim((string)$_POST['action']) : 'dismiss';

if ($reviewId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid review.']);
    exit;
}

if ($action !== 'dismiss') {
    echo json_encode(['ok' => false, 'error' => 'Unsupported action.']);
    exit;
}

$stmt = $mysqli->prepare("UPDATE reviews SET status = 'visible' WHERE review_id = ? AND COALESCE(status, 'visible') = 'reported'");
if (!$stmt) {
    echo json_encode(['ok' => false, 'error' => 'Database error.']);
    exit;
}

$stmt->bind_param('i', $reviewId);
$ok = $stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if (!$ok) {
    echo json_encode(['ok' => false, 'error' => 'Could not update review.']);
    exit;
}

echo json_encode([
    'ok' => true,
    'updated' => $affected > 0,
]);
