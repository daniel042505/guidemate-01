<?php
/**
 * Admin: revenue statistics from bookings.
 */
session_start();
require_once 'dbconnect.php';
require_once 'guide_booking_helpers.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin only']);
    exit;
}
session_write_close();

if (!ensure_guide_bookings_table($mysqli)) {
    echo json_encode(['error' => 'Could not prepare bookings table']);
    exit;
}

$stats = [
    'total_bookings' => 0,
    'total_revenue' => 0, // Placeholder, since no price field
    'average_revenue' => 0,
];

// Total bookings
$r = $mysqli->query("SELECT COUNT(*) AS c FROM guide_bookings");
if ($r && $row = $r->fetch_assoc()) {
    $stats['total_bookings'] = (int) $row['c'];
}

// For now, revenue is placeholder
$stats['total_revenue'] = 'N/A'; // Since no price
$stats['average_revenue'] = 'N/A';

echo json_encode($stats);