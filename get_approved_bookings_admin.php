<?php
session_start();
require_once 'dbconnect.php';
require_once 'guide_booking_helpers.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!ensure_guide_bookings_table($mysqli)) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT
            gb.booking_id,
            gb.approved_at,
            gb.created_at,
            gb.meet_time,
            TRIM(CONCAT(COALESCE(t.first_name, ''), ' ', COALESCE(t.last_name, ''))) AS tourist_name,
            TRIM(CONCAT(COALESCE(g.first_name, ''), ' ', COALESCE(g.last_name, ''))) AS guide_name
        FROM guide_bookings gb
        LEFT JOIN tourists t ON t.user_id = gb.tourist_user_id
        LEFT JOIN tour_guides g ON g.guide_id = gb.guide_id
        WHERE gb.status = 'Approved'
        ORDER BY gb.approved_at DESC, gb.booking_id DESC";

$result = $mysqli->query($sql);
$bookings = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = [
            'booking_id' => (int) $row['booking_id'],
            'tourist_name' => trim($row['tourist_name'] ?? '') ?: 'Tourist',
            'guide_name' => trim($row['guide_name'] ?? '') ?: 'Guide',
            'approved_at' => $row['approved_at'] ?? '',
            'created_at' => $row['created_at'] ?? '',
            'meet_time' => $row['meet_time'] ?? ''
        ];
    }
}

echo json_encode($bookings);
?>
