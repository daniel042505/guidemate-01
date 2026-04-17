<?php
/**
 * Approves a guide so they appear on the landing page. Admin session required.
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

// Keep approval working even on databases that do not have these columns yet.
$statusCol = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'status'");
if (!$statusCol || $statusCol->num_rows === 0) {
    $mysqli->query("ALTER TABLE tour_guides ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'Pending'");
    if ($mysqli->error) {
        echo json_encode(['ok' => false, 'error' => 'Could not prepare guide status for approval.']);
        exit;
    }
}

$hasSuspendedUntil = false;
$suspendedCol = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'suspended_until'");
if ($suspendedCol && $suspendedCol->num_rows > 0) {
    $hasSuspendedUntil = true;
}

$sql = "UPDATE tour_guides
        SET status = 'Active'" . ($hasSuspendedUntil ? ", suspended_until = NULL" : "") . "
        WHERE guide_id = ?
          AND (status = 'Pending' OR status IS NULL OR status = '')";
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    echo json_encode(['ok' => false, 'error' => 'Could not prepare approval query.']);
    exit;
}
$stmt->bind_param('i', $guide_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $stmt->close();
    echo json_encode(['ok' => true]);
} else {
    $stmt->close();
    echo json_encode(['ok' => false, 'error' => 'Guide not found or already approved']);
}
