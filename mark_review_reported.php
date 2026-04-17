<?php
session_start();
require_once 'dbconnect.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Sign in required']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$reviewId = isset($payload['review_id']) ? (int)$payload['review_id'] : 0;
if ($reviewId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid review.']);
    exit;
}

$check = $mysqli->prepare("SELECT review_id, COALESCE(status, 'visible') AS status FROM reviews WHERE review_id = ? LIMIT 1");
if (!$check) {
    echo json_encode(['ok' => false, 'error' => 'Database error.']);
    exit;
}

$check->bind_param('i', $reviewId);
$check->execute();
$result = $check->get_result();
$row = $result ? $result->fetch_assoc() : null;
$check->close();

if (!$row) {
    echo json_encode(['ok' => false, 'error' => 'Review not found.']);
    exit;
}

$currentStatus = (string)($row['status'] ?? 'visible');
if ($currentStatus === 'hidden') {
    echo json_encode(['ok' => false, 'error' => 'This review is no longer available.']);
    exit;
}

if ($currentStatus === 'reported') {
    echo json_encode(['ok' => true, 'already_reported' => true]);
    exit;
}

$stmt = $mysqli->prepare("UPDATE reviews SET status = 'reported' WHERE review_id = ?");
if (!$stmt) {
    echo json_encode(['ok' => false, 'error' => 'Database error.']);
    exit;
}

$stmt->bind_param('i', $reviewId);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    echo json_encode(['ok' => false, 'error' => 'Could not report review.']);
    exit;
}

echo json_encode(['ok' => true]);
