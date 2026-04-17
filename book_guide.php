<?php
session_start();
require_once 'dbconnect.php';
require_once 'guide_booking_helpers.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'tourist' || empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Please sign in as a tourist first.']);
    exit;
}

if (!ensure_guide_bookings_table($mysqli)) {
    echo json_encode(['ok' => false, 'error' => 'Could not prepare booking details.']);
    exit;
}

$tourist_user_id = (int) $_SESSION['user_id'];
$guide_id = isset($_POST['guide_id']) ? (int) $_POST['guide_id'] : 0;

if ($guide_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid guide selected.']);
    exit;
}

$tourist = get_tourist_by_user_id($mysqli, $tourist_user_id);
if (!$tourist) {
    echo json_encode(['ok' => false, 'error' => 'Tourist profile not found.']);
    exit;
}

$hasStatus = guide_bookings_column_exists($mysqli, 'tour_guides', 'status');
$hasSuspended = guide_bookings_column_exists($mysqli, 'tour_guides', 'suspended_until');

$guideQuery = "SELECT guide_id, first_name, last_name FROM tour_guides WHERE guide_id = ?";
if ($hasStatus) {
    $guideQuery .= " AND status = 'Active'";
}
if ($hasSuspended) {
    $guideQuery .= " AND (suspended_until IS NULL OR suspended_until <= CURDATE())";
}

$guideStmt = $mysqli->prepare($guideQuery);
$guideStmt->bind_param('i', $guide_id);
$guideStmt->execute();
$guideResult = $guideStmt->get_result();
$guide = $guideResult ? $guideResult->fetch_assoc() : null;
$guideStmt->close();

if (!$guide) {
    echo json_encode(['ok' => false, 'error' => 'That guide is not available for booking right now.']);
    exit;
}

$dupStmt = $mysqli->prepare("SELECT booking_id, status FROM guide_bookings WHERE tourist_user_id = ? AND guide_id = ? AND status IN ('Pending', 'Approved') LIMIT 1");
$dupStmt->bind_param('ii', $tourist_user_id, $guide_id);
$dupStmt->execute();
$dupResult = $dupStmt->get_result();
$duplicate = $dupResult ? $dupResult->fetch_assoc() : null;
$dupStmt->close();

if ($duplicate) {
    $message = ($duplicate['status'] === 'Approved')
        ? 'You already have an approved booking with this guide.'
        : 'You already sent a booking request to this guide.';
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

$occupiedStmt = $mysqli->prepare("SELECT booking_id FROM guide_bookings WHERE guide_id = ? AND status = 'Approved' LIMIT 1");
$occupiedStmt->bind_param('i', $guide_id);
$occupiedStmt->execute();
$occupiedResult = $occupiedStmt->get_result();
$occupied = $occupiedResult ? $occupiedResult->fetch_assoc() : null;
$occupiedStmt->close();

if ($occupied) {
    echo json_encode(['ok' => false, 'error' => 'This guide has already been booked. Please choose another guide.']);
    exit;
}

$insertStmt = $mysqli->prepare("INSERT INTO guide_bookings (tourist_user_id, guide_id, status) VALUES (?, ?, 'Pending')");
$insertStmt->bind_param('ii', $tourist_user_id, $guide_id);
$ok = $insertStmt->execute();
$insertStmt->close();

if (!$ok) {
    echo json_encode(['ok' => false, 'error' => 'Could not save your booking request.']);
    exit;
}

$guideName = trim(($guide['first_name'] ?? '') . ' ' . ($guide['last_name'] ?? ''));

echo json_encode([
    'ok' => true,
    'message' => $guideName !== ''
        ? 'Booking request sent to ' . $guideName . '. Wait for the guide to accept, then they can message you about the meeting time and place.'
        : 'Booking request sent. Wait for the guide to accept, then they can message you about the meeting time and place.'
]);
?>
