<?php
/**
 * Guides currently on the landing page (Active, not suspended). For admin to apply punishment.
 */
session_start();
require_once 'dbconnect.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
session_write_close();

$col = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'suspended_until'");
$hasSuspended = $col && $col->num_rows > 0;

$col2 = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'status'");
$hasStatus = $col2 && $col2->num_rows > 0;

if (!$hasStatus) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT guide_id, first_name, last_name, email FROM tour_guides WHERE status = 'Active'";
if ($hasSuspended) {
    $sql .= " AND (suspended_until IS NULL OR suspended_until <= CURDATE())";
}
$sql .= " ORDER BY last_name, first_name";

$result = $mysqli->query($sql);
$guides = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $guides[] = [
            'guide_id' => (int) $row['guide_id'],
            'name' => trim($row['first_name'] . ' ' . $row['last_name']),
            'email' => $row['email'],
        ];
    }
}
echo json_encode($guides);
