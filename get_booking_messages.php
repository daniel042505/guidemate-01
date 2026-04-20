<?php
session_start();
require_once 'dbconnect.php';
require_once 'guide_booking_helpers.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!ensure_guide_bookings_table($mysqli) || !ensure_booking_messages_table($mysqli)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not prepare messaging tables.']);
    exit;
}

$bookingId = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;
if ($bookingId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Invalid booking selected.']);
    exit;
}

$role = (string) $_SESSION['role'];
$userId = (int) $_SESSION['user_id'];
$booking = null;

if ($role === 'guide') {
    $guideId = get_guide_id_by_user_id($mysqli, $userId);
    if ($guideId <= 0) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Guide profile not found.']);
        exit;
    }

    $stmt = $mysqli->prepare("SELECT
            gb.booking_id,
            gb.status,
            gb.guide_id,
            gb.tourist_user_id,
            TRIM(CONCAT(COALESCE(t.first_name, ''), ' ', COALESCE(t.last_name, ''))) AS tourist_name,
            TRIM(CONCAT(COALESCE(g.first_name, ''), ' ', COALESCE(g.last_name, ''))) AS guide_name
        FROM guide_bookings gb
        LEFT JOIN tourists t ON t.user_id = gb.tourist_user_id
        LEFT JOIN tour_guides g ON g.guide_id = gb.guide_id
        WHERE gb.booking_id = ?
          AND gb.guide_id = ?
          AND gb.status IN ('Approved', 'Completed')
        LIMIT 1");
    $stmt->bind_param('ii', $bookingId, $guideId);
} elseif ($role === 'tourist') {
    $stmt = $mysqli->prepare("SELECT
            gb.booking_id,
            gb.status,
            gb.guide_id,
            gb.tourist_user_id,
            TRIM(CONCAT(COALESCE(t.first_name, ''), ' ', COALESCE(t.last_name, ''))) AS tourist_name,
            TRIM(CONCAT(COALESCE(g.first_name, ''), ' ', COALESCE(g.last_name, ''))) AS guide_name
        FROM guide_bookings gb
        LEFT JOIN tourists t ON t.user_id = gb.tourist_user_id
        LEFT JOIN tour_guides g ON g.guide_id = gb.guide_id
        WHERE gb.booking_id = ?
          AND gb.tourist_user_id = ?
          AND gb.status IN ('Approved', 'Completed')
        LIMIT 1");
    $stmt->bind_param('ii', $bookingId, $userId);
} else {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not load booking.']);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();
$booking = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$booking) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Booking conversation not found.']);
    exit;
}

$msgStmt = $mysqli->prepare("SELECT
        message_id,
        sender_role,
        sender_user_id,
        message_text,
        meet_time,
        meeting_location,
        created_at
    FROM booking_messages
    WHERE booking_id = ?
    ORDER BY created_at ASC, message_id ASC");

$messages = [];
if ($msgStmt) {
    $msgStmt->bind_param('i', $bookingId);
    $msgStmt->execute();
    $msgResult = $msgStmt->get_result();
    if ($msgResult) {
        while ($row = $msgResult->fetch_assoc()) {
            $messages[] = [
                'message_id' => (int) ($row['message_id'] ?? 0),
                'sender_role' => (string) ($row['sender_role'] ?? 'guide'),
                'sender_user_id' => (int) ($row['sender_user_id'] ?? 0),
                'message_text' => (string) ($row['message_text'] ?? ''),
                'meet_time' => $row['meet_time'] ?? null,
                'meeting_location' => $row['meeting_location'] ?? null,
                'created_at' => $row['created_at'] ?? ''
            ];
        }
    }
    $msgStmt->close();
}

echo json_encode([
    'ok' => true,
    'booking_id' => (int) $booking['booking_id'],
    'status' => (string) ($booking['status'] ?? 'Approved'),
    'guide_name' => trim((string) ($booking['guide_name'] ?? '')) ?: 'Guide',
    'tourist_name' => trim((string) ($booking['tourist_name'] ?? '')) ?: 'Tourist',
    'can_send' => in_array((string) ($booking['status'] ?? ''), ['Approved', 'Completed'], true),
    'viewer_role' => $role,
    'messages' => $messages
]);
?>
