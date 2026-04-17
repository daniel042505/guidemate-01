<?php
session_start();
header('Content-Type: application/json');

require_once 'dbconnect.php';

if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'tourist') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Please sign in as a tourist.',
        'favorites' => []
    ]);
    exit;
}

$touristUserId = (int)$_SESSION['user_id'];

$createTableSql = "
CREATE TABLE IF NOT EXISTS tourist_favorite_destinations (
    favorite_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tourist_user_id INT NOT NULL,
    destination_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_tourist_destination (tourist_user_id, destination_id),
    KEY idx_tourist_user_id (tourist_user_id),
    KEY idx_destination_id (destination_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$mysqli->query($createTableSql);

$colImage = $mysqli->query("SHOW COLUMNS FROM destinations LIKE 'image'");
$hasImage = $colImage && $colImage->num_rows > 0;
$colAddress = $mysqli->query("SHOW COLUMNS FROM destinations LIKE 'address'");
$hasAddress = $colAddress && $colAddress->num_rows > 0;
$colRating = $mysqli->query("SHOW COLUMNS FROM destinations LIKE 'rating'");
$hasRating = $colRating && $colRating->num_rows > 0;
$colReviewCount = $mysqli->query("SHOW COLUMNS FROM destinations LIKE 'review_count'");
$hasReviewCount = $colReviewCount && $colReviewCount->num_rows > 0;
$colAvailability = $mysqli->query("SHOW COLUMNS FROM destinations LIKE 'is_available'");
$hasAvailability = $colAvailability && $colAvailability->num_rows > 0;

$select = "
    d.destination_id,
    d.name,
    d.description,
    f.created_at AS favorited_at
";
if ($hasAddress) $select .= ", d.address";
if ($hasImage) $select .= ", d.image";
if ($hasRating) $select .= ", d.rating";
if ($hasReviewCount) $select .= ", d.review_count";

$sql = "
    SELECT {$select}
    FROM tourist_favorite_destinations f
    INNER JOIN destinations d ON d.destination_id = f.destination_id
    WHERE f.tourist_user_id = ?
";
if ($hasAvailability) {
    $sql .= " AND d.is_available = 1";
}
$sql .= " ORDER BY f.created_at DESC";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Could not load favorite destinations.',
        'favorites' => []
    ]);
    exit;
}

$stmt->bind_param('i', $touristUserId);
$stmt->execute();
$result = $stmt->get_result();

$favorites = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $favorites[] = [
            'destination_id' => (int)($row['destination_id'] ?? 0),
            'name' => (string)($row['name'] ?? ''),
            'description' => (string)($row['description'] ?? ''),
            'address' => $hasAddress ? (string)($row['address'] ?? '') : '',
            'image' => ($hasImage && !empty($row['image'])) ? (string)$row['image'] : 'photos/default.jpg',
            'rating' => $hasRating ? (float)($row['rating'] ?? 0) : 0,
            'review_count' => $hasReviewCount ? (int)($row['review_count'] ?? 0) : 0,
            'favorited_at' => (string)($row['favorited_at'] ?? '')
        ];
    }
}
$stmt->close();

echo json_encode([
    'success' => true,
    'favorites' => $favorites
]);
?>
