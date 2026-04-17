<?php
/**
 * Returns guides with status Pending (for admin to approve). Admin session required.
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

// If status column doesn't exist, add it so existing guides show as Pending for admin to approve
$col = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'status'");
if (!$col || $col->num_rows === 0) {
    $mysqli->query("ALTER TABLE tour_guides ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'Pending'");
    if ($mysqli->error) {
        echo json_encode([]);
        exit;
    }
}

$stmt = $mysqli->prepare("
    SELECT guide_id, first_name, last_name, email, phone_number, profile_image
    FROM tour_guides
    WHERE (status = 'Pending' OR status IS NULL OR status = '')
    ORDER BY guide_id DESC
");
$stmt->execute();
$result = $stmt->get_result();
$guides = [];
while ($row = $result->fetch_assoc()) {
    $guides[] = [
        'guide_id' => (int) $row['guide_id'],
        'name' => trim($row['first_name'] . ' ' . $row['last_name']),
        'email' => $row['email'],
        'phone_number' => $row['phone_number'] ?? '',
        'profile_image' => $row['profile_image'] ?: 'photos/default.jpg'
    ];
}
$stmt->close();

echo json_encode($guides);
