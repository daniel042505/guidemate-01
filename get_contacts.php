<?php
require_once 'dbconnect.php';
header('Content-Type: application/json');

$colPhone = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'phone_number'");
$hasPhone = $colPhone && $colPhone->num_rows > 0;
$colStatus = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'status'");
$hasStatus = $colStatus && $colStatus->num_rows > 0;

$select = "SELECT guide_id, first_name, last_name, email";
if ($hasPhone) {
    $select .= ", phone_number";
}
$sql = $select . " FROM tour_guides";
if ($hasStatus) {
    $sql .= " WHERE status = 'Active'";
}
$sql .= " ORDER BY last_name, first_name";

$result = $mysqli->query($sql);
$contacts = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $contacts[] = [
            'guide_id' => (int) $row['guide_id'],
            'name' => trim($row['first_name'] . ' ' . $row['last_name']),
            'email' => $row['email'] ?? '',
            'phone_number' => $row['phone_number'] ?? '',
            'description' => 'Contact this guide for bookings, tours, and local assistance.',
        ];
    }
}

echo json_encode($contacts);
