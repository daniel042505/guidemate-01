<?php
session_start();
require_once 'dbconnect.php';
require_once 'guide_booking_helpers.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'guide' || empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!ensure_guide_bookings_table($mysqli)) {
    echo json_encode([]);
    exit;
}

$guideId = get_guide_id_by_user_id($mysqli, (int) $_SESSION['user_id']);
if ($guideId <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $mysqli->prepare("SELECT
        gb.booking_id,
        gb.created_at,
        gb.meeting_location,
        gb.tourist_message,
        TRIM(CONCAT(COALESCE(t.first_name, ''), ' ', COALESCE(t.last_name, ''))) AS tourist_name
    FROM guide_bookings gb
    LEFT JOIN tourists t ON t.user_id = gb.tourist_user_id
    WHERE gb.guide_id = ? AND gb.status = 'Pending'
    ORDER BY gb.booking_id DESC");

if (!$stmt) {
    echo json_encode([]);
    exit;
}

$stmt->bind_param('i', $guideId);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = [
            'booking_id' => (int) ($row['booking_id'] ?? 0),
            'tourist_name' => trim((string) ($row['tourist_name'] ?? '')) ?: 'Tourist',
            'created_at' => (string) ($row['created_at'] ?? ''),
            'meeting_location' => (string) ($row['meeting_location'] ?? ''),
            'tourist_message' => (string) ($row['tourist_message'] ?? '')
        ];
    }
}

$stmt->close();
echo json_encode($bookings);
?>
