<?php
/**
 * Admin: system statistics (total users, total guides, total destinations).
 */
session_start();
require_once 'dbconnect.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin only']);
    exit;
}
session_write_close();

$stats = [
    'total_users'     => 0,
    'total_guides'    => 0,
    'total_destinations' => 0,
];

// Total users = tourists + guides (from users table, excluding admin)
$r = $mysqli->query("SELECT COUNT(*) AS c FROM users WHERE role IN ('tourist', 'guide') AND status = 'Active'");
if ($r && $row = $r->fetch_assoc()) {
    $stats['total_users'] = (int) $row['c'];
}

// Total guides (all tour_guides, or only Active – using all for "total guides")
$r = $mysqli->query("SELECT COUNT(*) AS c FROM tour_guides");
if ($r && $row = $r->fetch_assoc()) {
    $stats['total_guides'] = (int) $row['c'];
}

// Total destinations
$r = $mysqli->query("SELECT COUNT(*) AS c FROM destinations");
if ($r && $row = $r->fetch_assoc()) {
    $stats['total_destinations'] = (int) $row['c'];
}

echo json_encode($stats);
