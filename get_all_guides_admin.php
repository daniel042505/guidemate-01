<?php
/**
 * Admin: list ALL tour guides (pending, active, suspended) with their status.
 */
session_start();
require_once 'dbconnect.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
session_write_close();

$colStatus = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'status'");
$hasStatus = $colStatus && $colStatus->num_rows > 0;
$colSuspended = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'suspended_until'");
$hasSuspended = $colSuspended && $colSuspended->num_rows > 0;

$sql = "SELECT guide_id, first_name, last_name, email, status";
if ($hasSuspended) $sql .= ", suspended_until";
$sql .= " FROM tour_guides ORDER BY status, last_name, first_name";

$result = $mysqli->query($sql);
$guides = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $status = $hasStatus ? ($row['status'] ?? 'Pending') : 'Pending';
        $suspendedUntil = ($hasSuspended && !empty($row['suspended_until'])) ? $row['suspended_until'] : null;
        $isSuspended = $hasSuspended && $suspendedUntil && $suspendedUntil > date('Y-m-d');
        $isActiveNow = ($status === 'Active' && !$isSuspended);
        $displayStatus = $isSuspended ? 'Suspended' : $status;
        $guides[] = [
            'guide_id' => (int) $row['guide_id'],
            'name' => trim($row['first_name'] . ' ' . $row['last_name']),
            'email' => $row['email'],
            'status' => $displayStatus,
            'is_on_landing' => $isActiveNow,
            'can_delete' => !$isActiveNow,
        ];
    }
}
echo json_encode($guides);
