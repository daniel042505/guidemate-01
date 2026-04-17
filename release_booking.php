<?php
session_start();
require_once 'dbconnect.php';
require_once 'guide_booking_helpers.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!ensure_guide_bookings_table($mysqli)) {
    echo json_encode(['ok' => false, 'error' => 'Could not prepare bookings table.']);
    exit;
}

$booking_id = isset($_POST['booking_id']) ? (int) $_POST['booking_id'] : 0;
if ($booking_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid booking_id']);
    exit;
}

$stmt = $mysqli->prepare("UPDATE guide_bookings SET status = 'Completed' WHERE booking_id = ? AND status = 'Approved'");
if (!$stmt) {
    echo json_encode(['ok' => false, 'error' => 'Could not update booking.']);
    exit;
}

$stmt->bind_param('i', $booking_id);
$stmt->execute();
$updated = $stmt->affected_rows > 0;
$stmt->close();

if (!$updated) {
    echo json_encode(['ok' => false, 'error' => 'Booking not found or already released.']);
    exit;
}

echo json_encode(['ok' => true]);
?>
