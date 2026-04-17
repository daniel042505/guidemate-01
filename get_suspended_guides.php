<?php
/**
 * Guides currently suspended (punishment). Admin can re-add them to landing page.
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
if (!$col || $col->num_rows === 0) {
    echo json_encode([]);
    exit;
}
$countCol = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'suspension_count'");
$hasSuspensionCount = $countCol && $countCol->num_rows > 0;

$sql = "SELECT guide_id, first_name, last_name, email, suspended_until";
if ($hasSuspensionCount) $sql .= ", suspension_count";
$sql .= " FROM tour_guides WHERE status = 'Active' AND suspended_until IS NOT NULL AND suspended_until > CURDATE() ORDER BY suspended_until ASC";

$stmt = $mysqli->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$guides = [];
while ($row = $result->fetch_assoc()) {
    $guides[] = [
        'guide_id' => (int) $row['guide_id'],
        'name' => trim($row['first_name'] . ' ' . $row['last_name']),
        'email' => $row['email'],
        'suspended_until' => $row['suspended_until'],
        'suspension_count' => ($hasSuspensionCount && isset($row['suspension_count'])) ? (int) $row['suspension_count'] : 0,
    ];
}
$stmt->close();
echo json_encode($guides);
