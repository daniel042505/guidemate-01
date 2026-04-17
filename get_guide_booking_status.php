<?php
session_start();
require_once 'dbconnect.php';
require_once 'guide_booking_helpers.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'guide' || empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['booked' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!ensure_guide_bookings_table($mysqli)) {
    echo json_encode(['booked' => false, 'error' => 'Could not prepare bookings table.']);
    exit;
}

$guide_id = get_guide_id_by_user_id($mysqli, (int) $_SESSION['user_id']);
if ($guide_id <= 0) {
    echo json_encode(['booked' => false, 'error' => 'Guide profile not found.']);
    exit;
}

$stmt = $mysqli->prepare("SELECT
        gb.booking_id,
        gb.created_at,
        gb.meet_time,
        gb.meeting_location,
        gb.tourist_message,
        gb.approved_at,
        TRIM(CONCAT(COALESCE(t.first_name, ''), ' ', COALESCE(t.last_name, ''))) AS tourist_name
    FROM guide_bookings gb
    LEFT JOIN tourists t ON t.user_id = gb.tourist_user_id
    WHERE gb.guide_id = ? AND gb.status = 'Approved'
    ORDER BY gb.approved_at DESC, gb.booking_id DESC
    LIMIT 1");
$stmt->bind_param('i', $guide_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    echo json_encode(['booked' => false]);
    exit;
}

echo json_encode([
    'booked' => true,
    'booking_id' => (int) $row['booking_id'],
    'tourist_name' => trim($row['tourist_name'] ?? '') ?: 'Tourist',
    'created_at' => $row['created_at'] ?? null,
    'meet_time' => $row['meet_time'] ?? null,
    'meeting_location' => $row['meeting_location'] ?? '',
    'tourist_message' => $row['tourist_message'] ?? '',
    'approved_at' => $row['approved_at'] ?? null
]);
?>
