<?php
/**
 * Admin: list tourist spots with destination_id and price for editing.
 */
session_start();
require_once 'dbconnect.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin only']);
    exit;
}
session_write_close();

$col = $mysqli->query("SHOW COLUMNS FROM destinations LIKE 'price'");
$hasPrice = $col && $col->num_rows > 0;
$colAvailability = $mysqli->query("SHOW COLUMNS FROM destinations LIKE 'is_available'");
if (!$colAvailability || $colAvailability->num_rows === 0) {
    $mysqli->query("ALTER TABLE destinations ADD COLUMN is_available TINYINT(1) NOT NULL DEFAULT 1");
    $colAvailability = $mysqli->query("SHOW COLUMNS FROM destinations LIKE 'is_available'");
}
$hasAvailability = $colAvailability && $colAvailability->num_rows > 0;

$select = "destination_id, name, description";
$colImg = $mysqli->query("SHOW COLUMNS FROM destinations LIKE 'image'");
if ($colImg && $colImg->num_rows > 0) $select .= ", image";
$colRat = $mysqli->query("SHOW COLUMNS FROM destinations LIKE 'rating'");
if ($colRat && $colRat->num_rows > 0) $select .= ", rating, review_count";
if ($hasPrice) $select .= ", price";
if ($hasAvailability) $select .= ", is_available";

// Return all tourist spots (no limit) so admin can change price for every one
$query = "SELECT $select FROM destinations ORDER BY name ASC";
$result = $mysqli->query($query);

$spots = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $spots[] = [
            'destination_id' => (int) $row['destination_id'],
            'name' => $row['name'],
            'description' => $row['description'] ?: '',
            'price' => ($hasPrice && isset($row['price']) && $row['price'] !== null && $row['price'] !== '') ? $row['price'] : '',
            'is_available' => ($hasAvailability && isset($row['is_available'])) ? (int) $row['is_available'] : 1,
        ];
    }
}

echo json_encode($spots);
