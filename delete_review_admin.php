<?php
/**
 * Admin: delete a tourist review (e.g. inappropriate content).
 */
session_start();
require_once 'dbconnect.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Admin only']);
    exit;
}

$review_id = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;

if ($review_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid review.']);
    exit;
}

// Delete replies first (if table exists)
$check = $mysqli->query("SHOW TABLES LIKE 'review_replies'");
if ($check && $check->num_rows > 0) {
    $del = $mysqli->prepare("DELETE FROM review_replies WHERE review_id = ?");
    if ($del) {
        $del->bind_param('i', $review_id);
        $del->execute();
        $del->close();
    }
}

$stmt = $mysqli->prepare("DELETE FROM reviews WHERE review_id = ?");
if (!$stmt) {
    echo json_encode(['ok' => false, 'error' => 'Database error.']);
    exit;
}
$stmt->bind_param('i', $review_id);
if ($stmt->execute()) {
    $stmt->close();
    echo json_encode(['ok' => true]);
} else {
    $stmt->close();
    echo json_encode(['ok' => false, 'error' => 'Could not delete.']);
}
