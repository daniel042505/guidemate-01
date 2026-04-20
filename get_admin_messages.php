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

if ($role === 'admin') {
    $otherRole = trim((string) ($_GET['user_role'] ?? ''));
    $otherUserId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
    if ($otherRole === '' || $otherUserId <= 0) {
        echo json_encode(['ok' => true, 'messages' => [], 'admin' => true]);
        exit;
    }
    $stmt = $mysqli->prepare("SELECT message_id, sender_role, sender_user_id, recipient_role, recipient_user_id, message_text, created_at
            FROM admin_messages
            WHERE (sender_role = 'admin' AND recipient_role = ? AND recipient_user_id = ?)
               OR (sender_role = ? AND sender_user_id = ? AND recipient_role = 'admin')
            ORDER BY created_at ASC, message_id ASC");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $mysqli->error]);
        exit;
    }
    $stmt->bind_param('siss', $otherRole, $otherUserId, $otherRole, $otherUserId);
} elseif (in_array($role, ['tourist', 'guide'], true)) {
    $stmt = $mysqli->prepare("SELECT message_id, sender_role, sender_user_id, recipient_role, recipient_user_id, message_text, created_at
            FROM admin_messages
            WHERE (sender_role = ? AND sender_user_id = ? AND recipient_role = 'admin')
               OR (sender_role = 'admin' AND recipient_role = ? AND recipient_user_id = ?)
            ORDER BY created_at ASC, message_id ASC");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $mysqli->error]);
        exit;
    }
    $stmt->bind_param('siss', $role, $userId, $role, $userId);
} else {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();
$messages = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'message_id' => (int) ($row['message_id'] ?? 0),
            'sender_role' => (string) ($row['sender_role'] ?? ''),
            'sender_user_id' => (int) ($row['sender_user_id'] ?? 0),
            'recipient_role' => (string) ($row['recipient_role'] ?? ''),
            'recipient_user_id' => (int) ($row['recipient_user_id'] ?? 0),
            'message_text' => (string) ($row['message_text'] ?? ''),
            'created_at' => $row['created_at'] ?? ''
        ];
    }
}
$stmt->close();

echo json_encode(['ok' => true, 'messages' => $messages]);
