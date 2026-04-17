<?php
/**
 * Re-add a suspended guide to the landing page immediately (clear punishment).
 * POST: guide_id (int)
 */
session_start();
require_once 'dbconnect.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$guide_id = isset($_POST['guide_id']) ? (int) $_POST['guide_id'] : 0;
if ($guide_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid guide_id']);
    exit;
}

$stmt = $mysqli->prepare("UPDATE tour_guides SET suspended_until = NULL WHERE guide_id = ?");
$stmt->bind_param('i', $guide_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $stmt->close();
    echo json_encode(['ok' => true]);
} else {
    $stmt->close();
    echo json_encode(['ok' => true]); // already not suspended
}
