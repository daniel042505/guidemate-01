<?php
/**
 * Admin: update availability for multiple tourist spots at once.
 * POST: destination_ids[] (array of destination_id integers)
 */
session_start();
require_once 'dbconnect.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Admin only']);
    exit;
}

$raw = isset($_POST['destination_ids']) ? $_POST['destination_ids'] : [];
if (!is_array($raw)) $raw = $raw ? [$raw] : [];
$ids = array_filter(array_map('intval', $raw), function ($id) { return $id > 0; });
$ids = array_unique($ids);

if (empty($ids)) {
    echo json_encode(['ok' => false, 'error' => 'No valid spots selected.']);
    exit;
}

$col = $mysqli->query("SHOW COLUMNS FROM destinations LIKE 'is_available'");
if (!$col || $col->num_rows === 0) {
    $mysqli->query("ALTER TABLE destinations ADD COLUMN is_available TINYINT(1) NOT NULL DEFAULT 1");
}

$updated = 0;
foreach ($ids as $destination_id) {
    $stmt = $mysqli->prepare("UPDATE destinations SET is_available = 0 WHERE destination_id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $destination_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $updated++;
        }
        $stmt->close();
    }
}

echo json_encode(['ok' => true, 'updated' => $updated]);