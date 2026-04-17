<?php
require_once 'dbconnect.php';

$col = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'status'");
$hasStatus = $col && $col->num_rows > 0;
$col2 = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'suspended_until'");
$hasSuspended = $col2 && $col2->num_rows > 0;
$query = "SELECT first_name, last_name, profile_image FROM tour_guides";
if ($hasStatus) {
    $query .= " WHERE status = 'Active'";
    if ($hasSuspended) $query .= " AND (suspended_until IS NULL OR suspended_until <= CURDATE())";
}
$query .= " LIMIT 4";
$result = $mysqli->query($query);

$guides = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $guides[] = [
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'profile_image' => $row['profile_image'] ?: 'photos/default.jpg'
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($guides);
?>