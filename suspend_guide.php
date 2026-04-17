<?php
/**
 * Punish a guide (e.g. did not appear on exact time): remove from landing page for 1–3 days.
 * After that date they automatically appear again. Admin can re-add earlier with pardon_guide.php.
 * POST: guide_id (int), days (1, 2, or 3)
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
$days = isset($_POST['days']) ? (int) $_POST['days'] : 0;
if ($guide_id <= 0 || !in_array($days, [1, 2, 3], true)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid guide_id or days (use 1, 2, or 3).']);
    exit;
}

// Ensure suspended_until column exists
$col = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'suspended_until'");
if (!$col || $col->num_rows === 0) {
    $mysqli->query("ALTER TABLE tour_guides ADD COLUMN suspended_until DATE DEFAULT NULL");
    if ($mysqli->error) {
        echo json_encode(['ok' => false, 'error' => 'Database update failed.']);
        exit;
    }
}

// Track repeated suspensions for admin moderation decisions.
$countCol = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'suspension_count'");
if (!$countCol || $countCol->num_rows === 0) {
    $mysqli->query("ALTER TABLE tour_guides ADD COLUMN suspension_count INT UNSIGNED NOT NULL DEFAULT 0");
    if ($mysqli->error) {
        echo json_encode(['ok' => false, 'error' => 'Database update failed.']);
        exit;
    }
}

$stmt = $mysqli->prepare("UPDATE tour_guides SET suspended_until = DATE_ADD(CURDATE(), INTERVAL ? DAY), suspension_count = COALESCE(suspension_count, 0) + 1 WHERE guide_id = ? AND status = 'Active'");
$stmt->bind_param('ii', $days, $guide_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $stmt->close();
    echo json_encode(['ok' => true, 'days' => $days]);
} else {
    $stmt->close();
    echo json_encode(['ok' => false, 'error' => 'Guide not found or not on landing page.']);
}
