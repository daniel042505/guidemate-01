<?php
session_start();
header('Content-Type: application/json');
require_once 'dbconnect.php';
require_once 'guide_booking_helpers.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request body']);
    exit;
}

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'tourist' || empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Please sign in as tourist.']);
    exit;
}

$locationName = trim((string)($data['location_name'] ?? ''));
$guideName = trim((string)($data['guide_name'] ?? ''));
$guideId = isset($data['guide_id']) ? (int)$data['guide_id'] : 0;
$reviewType = strtolower(trim((string)($data['review_type'] ?? '')));
$comment = trim((string)($data['comment'] ?? ''));
$rating = isset($data['rating']) ? (int)$data['rating'] : 0;

if (!in_array($reviewType, ['location', 'guide'], true)) {
    $reviewType = 'location';
}

if ($locationName === '' || $guideName === '' || $comment === '' || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid fields']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

if (!ensure_guide_bookings_table($mysqli)) {
    echo json_encode(['success' => false, 'message' => 'Could not verify your booking history']);
    exit;
}

$touristStmt = $mysqli->prepare('SELECT tourist_id FROM tourists WHERE user_id = ? LIMIT 1');
if (!$touristStmt) {
    echo json_encode(['success' => false, 'message' => 'Could not prepare tourist lookup']);
    exit;
}
$touristStmt->bind_param('i', $userId);
$touristStmt->execute();
$touristRes = $touristStmt->get_result();
$touristRow = $touristRes ? $touristRes->fetch_assoc() : null;
$touristStmt->close();

if (!$touristRow || empty($touristRow['tourist_id'])) {
    echo json_encode(['success' => false, 'message' => 'Tourist account not found']);
    exit;
}
$touristId = (int)$touristRow['tourist_id'];

if ($guideId > 0) {
    $guideStmt = $mysqli->prepare("
        SELECT guide_id, TRIM(CONCAT(first_name, ' ', last_name)) AS guide_name
        FROM tour_guides
        WHERE guide_id = ?
        LIMIT 1
    ");
    if (!$guideStmt) {
        echo json_encode(['success' => false, 'message' => 'Could not prepare guide lookup']);
        exit;
    }
    $guideStmt->bind_param('i', $guideId);
} else {
    $guideStmt = $mysqli->prepare("
        SELECT guide_id, TRIM(CONCAT(first_name, ' ', last_name)) AS guide_name
        FROM tour_guides
        WHERE TRIM(CONCAT(first_name, ' ', last_name)) = ?
        LIMIT 1
    ");
    if (!$guideStmt) {
        echo json_encode(['success' => false, 'message' => 'Could not prepare guide lookup']);
        exit;
    }
    $guideStmt->bind_param('s', $guideName);
}
$guideStmt->execute();
$guideRes = $guideStmt->get_result();
$guideRow = $guideRes ? $guideRes->fetch_assoc() : null;
$guideStmt->close();

if (!$guideRow || empty($guideRow['guide_id'])) {
    echo json_encode(['success' => false, 'message' => 'Selected guide was not found']);
    exit;
}
$guideId = (int)$guideRow['guide_id'];
$guideName = trim((string)($guideRow['guide_name'] ?? $guideName));

$bookingStmt = $mysqli->prepare("
    SELECT booking_id
    FROM guide_bookings
    WHERE tourist_user_id = ?
      AND guide_id = ?
      AND status IN ('Approved', 'Completed')
    LIMIT 1
");
if (!$bookingStmt) {
    echo json_encode(['success' => false, 'message' => 'Could not verify your booking']);
    exit;
}
$bookingStmt->bind_param('ii', $userId, $guideId);
$bookingStmt->execute();
$bookingRes = $bookingStmt->get_result();
$bookingRow = $bookingRes ? $bookingRes->fetch_assoc() : null;
$bookingStmt->close();

if (!$bookingRow) {
    echo json_encode(['success' => false, 'message' => 'You can only review guides that you have booked.']);
    exit;
}

// Keep location context in comment for existing schema compatibility.
$finalComment = "Type: {$reviewType}\nLocation: {$locationName}\nReview: {$comment}";

$insertStmt = $mysqli->prepare('INSERT INTO reviews (tourist_id, guide_id, rating, comment) VALUES (?, ?, ?, ?)');
if (!$insertStmt) {
    echo json_encode(['success' => false, 'message' => 'Could not prepare insert']);
    exit;
}
$insertStmt->bind_param('iiis', $touristId, $guideId, $rating, $finalComment);
$ok = $insertStmt->execute();
$insertStmt->close();

if (!$ok) {
    echo json_encode(['success' => false, 'message' => 'Could not save review']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Review submitted successfully',
    'review' => [
        'location_name' => $locationName,
        'guide_id' => $guideId,
        'guide_name' => $guideName,
        'review_type' => $reviewType,
        'rating' => $rating,
        'comment' => $comment
    ]
]);
