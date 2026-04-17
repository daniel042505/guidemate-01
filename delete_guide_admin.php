<?php
/**
 * Admin: permanently delete a guide if they are not active on landing page.
 * POST: guide_id (int)
 */
session_start();
require_once 'dbconnect.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$guideId = isset($_POST['guide_id']) ? (int) $_POST['guide_id'] : 0;
if ($guideId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid guide_id.']);
    exit;
}

$colStatus = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'status'");
$hasStatus = $colStatus && $colStatus->num_rows > 0;
$colSuspended = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'suspended_until'");
$hasSuspended = $colSuspended && $colSuspended->num_rows > 0;

$checkSql = "SELECT guide_id";
if ($hasStatus) $checkSql .= ", status";
if ($hasSuspended) $checkSql .= ", suspended_until";
$checkSql .= " FROM tour_guides WHERE guide_id = ? LIMIT 1";

$checkStmt = $mysqli->prepare($checkSql);
if (!$checkStmt) {
    echo json_encode(['ok' => false, 'error' => 'Failed to prepare guide lookup.']);
    exit;
}
$checkStmt->bind_param('i', $guideId);
$checkStmt->execute();
$guideResult = $checkStmt->get_result();
$guide = $guideResult ? $guideResult->fetch_assoc() : null;
$checkStmt->close();

if (!$guide) {
    echo json_encode(['ok' => false, 'error' => 'Guide not found.']);
    exit;
}

$status = $hasStatus ? (string)($guide['status'] ?? 'Pending') : 'Pending';
$suspendedUntil = ($hasSuspended && !empty($guide['suspended_until'])) ? (string)$guide['suspended_until'] : null;
$isSuspended = $hasSuspended && $suspendedUntil && $suspendedUntil > date('Y-m-d');
$isActiveNow = ($status === 'Active' && !$isSuspended);

if ($isActiveNow) {
    echo json_encode(['ok' => false, 'error' => 'Cannot delete an active guide. Suspend or deactivate first.']);
    exit;
}

$deleteStmt = $mysqli->prepare("DELETE FROM tour_guides WHERE guide_id = ? LIMIT 1");
if (!$deleteStmt) {
    echo json_encode(['ok' => false, 'error' => 'Failed to prepare deletion.']);
    exit;
}
$deleteStmt->bind_param('i', $guideId);
$deleteStmt->execute();
$affected = $deleteStmt->affected_rows;
$error = $deleteStmt->error;
$deleteStmt->close();

if ($affected > 0) {
    echo json_encode(['ok' => true]);
    exit;
}

if (!empty($error)) {
    echo json_encode([
        'ok' => false,
        'error' => 'Could not delete guide. This guide may still be referenced by bookings or reviews.'
    ]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Guide could not be deleted.']);
?>
