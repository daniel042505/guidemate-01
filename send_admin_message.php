<?php
session_start();
require_once 'dbconnect.php';
require_once 'guide_booking_helpers.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'You must be signed in to message admin.']);
    exit;
}

if (!ensure_admin_messages_table($mysqli)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not prepare admin message storage.']);
    exit;
}

$senderRole = trim((string) ($_SESSION['role'] ?? ''));
$senderUserId = (int) ($_SESSION['user_id'] ?? 0);
$messageText = trim((string) ($_POST['message_text'] ?? ''));
$recipientRole = trim((string) ($_POST['recipient_role'] ?? ''));
$recipientUserId = isset($_POST['recipient_user_id']) ? (int) $_POST['recipient_user_id'] : 0;

if ($messageText === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Message text is required.']);
    exit;
}

if (mb_strlen($messageText) > 1000) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Message is too long. Keep it under 1000 characters.']);
    exit;
}

if ($senderRole === 'admin') {
    if (!in_array($recipientRole, ['tourist', 'guide'], true) || $recipientUserId <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Admin must select a valid guide or tourist to message.']);
        exit;
    }
} elseif (in_array($senderRole, ['tourist', 'guide'], true)) {
    $recipientRole = 'admin';
    $recipientUserId = 0;
} else {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Only tourists, guides, and admins can use admin messaging.']);
    exit;
}

$stmt = $mysqli->prepare("INSERT INTO admin_messages (sender_role, sender_user_id, recipient_role, recipient_user_id, message_text) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not prepare message.']);
    exit;
}
$stmt->bind_param('sisss', $senderRole, $senderUserId, $recipientRole, $recipientUserId, $messageText);
$ok = $stmt->execute();
$messageId = $ok ? (int) $stmt->insert_id : 0;
$stmt->close();

if (!$ok) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save the message.']);
    exit;
}

echo json_encode([
    'ok' => true,
    'message' => 'Message sent successfully.',
    'sent_message' => [
        'message_id' => $messageId,
        'sender_role' => $senderRole,
        'sender_user_id' => $senderUserId,
        'recipient_role' => $recipientRole,
        'recipient_user_id' => $recipientUserId,
        'message_text' => $messageText,
        'created_at' => date('Y-m-d H:i:s')
    ]
]);
