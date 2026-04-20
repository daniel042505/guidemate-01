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

if (!ensure_admin_messages_table($mysqli)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not prepare admin message storage.']);
    exit;
}

$role = trim((string) ($_SESSION['role'] ?? ''));
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($role !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Only admin can list admin conversations.']);
    exit;
}

$sql = "SELECT other_role, other_user_id, MAX(created_at) AS last_at, COUNT(*) AS message_count
        FROM (
            SELECT sender_role AS other_role, sender_user_id AS other_user_id, created_at
            FROM admin_messages
            WHERE recipient_role = 'admin'
            UNION ALL
            SELECT recipient_role AS other_role, recipient_user_id AS other_user_id, created_at
            FROM admin_messages
            WHERE sender_role = 'admin'
        ) AS conv
        WHERE other_role IN ('tourist','guide')
        GROUP BY other_role, other_user_id
        ORDER BY last_at DESC";

$result = $mysqli->query($sql);
if (!$result) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $mysqli->error]);
    exit;
}

$conversations = [];
$guideStmt = $mysqli->prepare("SELECT first_name, last_name FROM tour_guides WHERE user_id = ? LIMIT 1");
$touristStmt = $mysqli->prepare("SELECT first_name, last_name FROM tourists WHERE user_id = ? LIMIT 1");

while ($row = $result->fetch_assoc()) {
    $otherRole = $row['other_role'];
    $otherUserId = (int) $row['other_user_id'];
    $displayName = ucfirst($otherRole) . ' #' . $otherUserId;
    if ($otherRole === 'guide' && $guideStmt) {
        $guideStmt->bind_param('i', $otherUserId);
        $guideStmt->execute();
        $res2 = $guideStmt->get_result();
        $userRow = $res2 ? $res2->fetch_assoc() : null;
        if ($userRow) {
            $displayName = trim(($userRow['first_name'] ?? '') . ' ' . ($userRow['last_name'] ?? '')) ?: $displayName;
        }
    } elseif ($otherRole === 'tourist' && $touristStmt) {
        $touristStmt->bind_param('i', $otherUserId);
        $touristStmt->execute();
        $res2 = $touristStmt->get_result();
        $userRow = $res2 ? $res2->fetch_assoc() : null;
        if ($userRow) {
            $displayName = trim(($userRow['first_name'] ?? '') . ' ' . ($userRow['last_name'] ?? '')) ?: $displayName;
        }
    }
    $conversations[] = [
        'other_role' => $otherRole,
        'other_user_id' => $otherUserId,
        'display_name' => $displayName,
        'last_at' => $row['last_at'],
        'message_count' => (int) $row['message_count']
    ];
}

if ($guideStmt) {
    $guideStmt->close();
}
if ($touristStmt) {
    $touristStmt->close();
}

echo json_encode(['ok' => true, 'conversations' => $conversations]);
