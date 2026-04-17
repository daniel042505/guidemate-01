<?php
session_start();
header('Content-Type: application/json');

require_once 'dbconnect.php';

if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'tourist') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Please sign in as a tourist.'
    ]);
    exit;
}

$payload = [];
$rawInput = file_get_contents('php://input');
if ($rawInput) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}

$destinationId = 0;
if (isset($_POST['destination_id'])) {
    $destinationId = (int)$_POST['destination_id'];
} elseif (isset($payload['destination_id'])) {
    $destinationId = (int)$payload['destination_id'];
}

if ($destinationId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid destination.'
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

$checkDestination = $mysqli->prepare("SELECT destination_id FROM destinations WHERE destination_id = ? LIMIT 1");
if (!$checkDestination) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error while checking destination.'
    ]);
    exit;
}
$checkDestination->bind_param('i', $destinationId);
$checkDestination->execute();
$destinationResult = $checkDestination->get_result();
if (!$destinationResult || $destinationResult->num_rows === 0) {
    $checkDestination->close();
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Destination not found.'
    ]);
    exit;
}
$checkDestination->close();

$checkFavorite = $mysqli->prepare("
    SELECT favorite_id
    FROM tourist_favorite_destinations
    WHERE tourist_user_id = ? AND destination_id = ?
    LIMIT 1
");
if (!$checkFavorite) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error while checking favorites.'
    ]);
    exit;
}
$checkFavorite->bind_param('ii', $touristUserId, $destinationId);
$checkFavorite->execute();
$favoriteResult = $checkFavorite->get_result();
$existing = $favoriteResult ? $favoriteResult->fetch_assoc() : null;
$checkFavorite->close();

if ($existing) {
    $deleteStmt = $mysqli->prepare("
        DELETE FROM tourist_favorite_destinations
        WHERE tourist_user_id = ? AND destination_id = ?
        LIMIT 1
    ");
    if (!$deleteStmt) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Could not remove destination from favorites.'
        ]);
        exit;
    }
    $deleteStmt->bind_param('ii', $touristUserId, $destinationId);
    $ok = $deleteStmt->execute();
    $deleteStmt->close();

    if (!$ok) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Could not remove destination from favorites.'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'is_favorited' => false,
        'destination_id' => $destinationId,
        'message' => 'Removed from favorites.'
    ]);
    exit;
}

$insertStmt = $mysqli->prepare("
    INSERT INTO tourist_favorite_destinations (tourist_user_id, destination_id)
    VALUES (?, ?)
");
if (!$insertStmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Could not add destination to favorites.'
    ]);
    exit;
}
$insertStmt->bind_param('ii', $touristUserId, $destinationId);
$ok = $insertStmt->execute();
$insertStmt->close();

if (!$ok) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Could not add destination to favorites.'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'is_favorited' => true,
    'destination_id' => $destinationId,
    'message' => 'Added to favorites.'
]);
