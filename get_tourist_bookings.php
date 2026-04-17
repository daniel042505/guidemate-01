<?php
session_start();
require_once 'dbconnect.php';
require_once 'guide_booking_helpers.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'tourist' || empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!ensure_guide_bookings_table($mysqli)) {
    echo json_encode([]);
    exit;
}

$touristUserId = (int) $_SESSION['user_id'];

$stmt = $mysqli->prepare("SELECT
        gb.booking_id,
        gb.status,
        gb.created_at,
        gb.approved_at,
        gb.meet_time,
        gb.meeting_location,
        gb.tourist_message,
        TRIM(CONCAT(COALESCE(g.first_name, ''), ' ', COALESCE(g.last_name, ''))) AS guide_name
    FROM guide_bookings gb
    LEFT JOIN tour_guides g ON g.guide_id = gb.guide_id
    WHERE gb.tourist_user_id = ?
      AND gb.status IN ('Pending', 'Approved', 'Completed')
    ORDER BY COALESCE(gb.meet_time, gb.approved_at, gb.created_at) DESC, gb.booking_id DESC");

if (!$stmt) {
    echo json_encode([]);
    exit;
}

$stmt->bind_param('i', $touristUserId);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = [
            'booking_id' => (int) $row['booking_id'],
            'status' => $row['status'] ?? 'Pending',
            'guide_name' => trim($row['guide_name'] ?? '') ?: 'Guide',
            'created_at' => $row['created_at'] ?? '',
            'approved_at' => $row['approved_at'] ?? '',
            'meet_time' => $row['meet_time'] ?? '',
            'meeting_location' => $row['meeting_location'] ?? '',
            'tourist_message' => $row['tourist_message'] ?? ''
        ];
    }
}

$stmt->close();

echo json_encode($bookings);
?>
