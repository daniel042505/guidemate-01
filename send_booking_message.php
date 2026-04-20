<?php
session_start();
require_once 'dbconnect.php';
require_once 'guide_booking_helpers.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'You are not allowed to send booking messages.']);
    exit;
}

if (!ensure_guide_bookings_table($mysqli) || !ensure_booking_messages_table($mysqli)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not prepare messaging tables.']);
    exit;
}

$bookingId = isset($_POST['booking_id']) ? (int) $_POST['booking_id'] : 0;
$messageText = trim((string) ($_POST['message_text'] ?? ''));
$meetTime = trim((string) ($_POST['meet_time'] ?? ''));
$meetingLocation = trim((string) ($_POST['meeting_location'] ?? ''));

if ($bookingId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Invalid booking selected.']);
    exit;
}

if ($messageText === '' && $meetTime === '' && $meetingLocation === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Message cannot be empty unless you are updating booking details.']);
    exit;
}

if (mb_strlen($messageText) > 1000) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Message is too long. Keep it under 1000 characters.']);
    exit;
}

$role = (string) $_SESSION['role'];
$senderUserId = (int) $_SESSION['user_id'];
$booking = null;

if ($role === 'guide') {
    $guideId = get_guide_id_by_user_id($mysqli, $senderUserId);
    if ($guideId <= 0) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Guide profile not found.']);
        exit;
    }

    $bookingStmt = $mysqli->prepare("SELECT
            gb.booking_id,
            gb.guide_id,
            gb.tourist_user_id,
            gb.status,
            TRIM(CONCAT(COALESCE(t.first_name, ''), ' ', COALESCE(t.last_name, ''))) AS tourist_name,
            TRIM(CONCAT(COALESCE(g.first_name, ''), ' ', COALESCE(g.last_name, ''))) AS guide_name
        FROM guide_bookings gb
        LEFT JOIN tourists t ON t.user_id = gb.tourist_user_id
        LEFT JOIN tour_guides g ON g.guide_id = gb.guide_id
        WHERE gb.booking_id = ?
          AND gb.guide_id = ?
          AND gb.status IN ('Approved', 'Completed')
        LIMIT 1");
    if (!$bookingStmt) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Could not load booking.']);
        exit;
    }

    $bookingStmt->bind_param('ii', $bookingId, $guideId);
    $bookingStmt->execute();
    $bookingResult = $bookingStmt->get_result();
    $booking = $bookingResult ? $bookingResult->fetch_assoc() : null;
    $bookingStmt->close();

    if (!$booking) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'You can only message tourists assigned to your bookings.']);
        exit;
    }

    if ($meetTime !== '' || $meetingLocation !== '') {
        if ($meetTime !== '' && $meetingLocation !== '') {
            $stmtUpdate = $mysqli->prepare('UPDATE guide_bookings SET meet_time = ?, meeting_location = ? WHERE booking_id = ?');
            if ($stmtUpdate) {
                $stmtUpdate->bind_param('ssi', $meetTime, $meetingLocation, $bookingId);
                $stmtUpdate->execute();
                $stmtUpdate->close();
            }
        } elseif ($meetTime !== '') {
            $stmtUpdate = $mysqli->prepare('UPDATE guide_bookings SET meet_time = ? WHERE booking_id = ?');
            if ($stmtUpdate) {
                $stmtUpdate->bind_param('si', $meetTime, $bookingId);
                $stmtUpdate->execute();
                $stmtUpdate->close();
            }
        } elseif ($meetingLocation !== '') {
            $stmtUpdate = $mysqli->prepare('UPDATE guide_bookings SET meeting_location = ? WHERE booking_id = ?');
            if ($stmtUpdate) {
                $stmtUpdate->bind_param('si', $meetingLocation, $bookingId);
                $stmtUpdate->execute();
                $stmtUpdate->close();
            }
        }
    }
} elseif ($role === 'tourist') {
    $bookingStmt = $mysqli->prepare("SELECT
            gb.booking_id,
            gb.guide_id,
            gb.tourist_user_id,
            gb.status,
            TRIM(CONCAT(COALESCE(t.first_name, ''), ' ', COALESCE(t.last_name, ''))) AS tourist_name,
            TRIM(CONCAT(COALESCE(g.first_name, ''), ' ', COALESCE(g.last_name, ''))) AS guide_name
        FROM guide_bookings gb
        LEFT JOIN tourists t ON t.user_id = gb.tourist_user_id
        LEFT JOIN tour_guides g ON g.guide_id = gb.guide_id
        WHERE gb.booking_id = ?
          AND gb.tourist_user_id = ?
          AND gb.status IN ('Approved', 'Completed')
        LIMIT 1");
    if (!$bookingStmt) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Could not load booking.']);
        exit;
    }

    $bookingStmt->bind_param('ii', $bookingId, $senderUserId);
    $bookingStmt->execute();
    $bookingResult = $bookingStmt->get_result();
    $booking = $bookingResult ? $bookingResult->fetch_assoc() : null;
    $bookingStmt->close();

    if (!$booking) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'You can only message guides assigned to your bookings.']);
        exit;
    }

    $guideId = (int) ($booking['guide_id'] ?? 0);
} else {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Only tourists and guides can send booking messages.']);
    exit;
}

$insertStmt = $mysqli->prepare("INSERT INTO booking_messages (
        booking_id,
        guide_id,
        tourist_user_id,
        sender_role,
        sender_user_id,
        message_text,
        meet_time,
        meeting_location
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

if (!$insertStmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not prepare message.']);
    exit;
}

$touristUserId = (int) $booking['tourist_user_id'];
$meetTimeDb = ($meetTime !== '') ? normalize_booking_meet_time($meetTime) : null;
$meetingLocationDb = ($meetingLocation !== '') ? normalize_booking_location($meetingLocation) : null;
$insertStmt->bind_param('iiisisss', $bookingId, $guideId, $touristUserId, $role, $senderUserId, $messageText, $meetTimeDb, $meetingLocationDb);
$ok = $insertStmt->execute();
$messageId = $ok ? (int) $insertStmt->insert_id : 0;
$insertStmt->close();

if (!$ok) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not send message right now.']);
    exit;
}

echo json_encode([
    'ok' => true,
    'message' => $role === 'guide'
        ? 'Message sent to ' . (trim((string) ($booking['tourist_name'] ?? '')) ?: 'the tourist') . '.'
        : 'Message sent to ' . (trim((string) ($booking['guide_name'] ?? '')) ?: 'the guide') . '.',
    'sent_message' => [
        'message_id' => $messageId,
        'sender_role' => $role,
        'sender_user_id' => $senderUserId,
        'message_text' => $messageText,
        'meet_time' => $meetTimeDb,
        'meeting_location' => $meetingLocationDb,
        'created_at' => date('Y-m-d H:i:s')
    ]
]);
?>
