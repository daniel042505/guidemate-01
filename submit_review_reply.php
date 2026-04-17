<?php
/**
 * Guide submits a reply to a tourist review. Requires session (guide).
 */
session_start();
require_once 'dbconnect.php';
require_once 'review_helpers.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'guide' || empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Not authorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$res = $mysqli->query("SELECT guide_id FROM tour_guides WHERE user_id = $user_id");
if (!$res || !$row = $res->fetch_assoc()) {
    echo json_encode(['ok' => false, 'error' => 'Guide not found']);
    exit;
}
$guide_id = (int)$row['guide_id'];

if (!gm_ensure_review_replies_table($mysqli)) {
    echo json_encode(['ok' => false, 'error' => 'Could not prepare replies storage']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['review_id']) || !isset($input['reply_text'])) {
    echo json_encode(['ok' => false, 'error' => 'Missing review_id or reply_text']);
    exit;
}
$review_id = (int)$input['review_id'];
$reply_text = trim($input['reply_text']);
if ($reply_text === '') {
    echo json_encode(['ok' => false, 'error' => 'Reply cannot be empty']);
    exit;
}

// Ensure the review belongs to this guide
$check = $mysqli->prepare("SELECT review_id FROM reviews WHERE review_id = ? AND guide_id = ?");
$check->bind_param('ii', $review_id, $guide_id);
$check->execute();
$rev = $check->get_result();
if (!$rev || $rev->num_rows === 0) {
    $check->close();
    echo json_encode(['ok' => false, 'error' => 'Review not found']);
    exit;
}
$check->close();

// One reply per review per guide: update if exists, else insert
$ex = $mysqli->prepare("SELECT reply_id FROM review_replies WHERE review_id = ? AND guide_id = ?");
$ex->bind_param('ii', $review_id, $guide_id);
$ex->execute();
$existing = $ex->get_result();
$ex->close();

if ($existing && $existing->num_rows > 0) {
    $replyRow = $existing->fetch_assoc();
    $reply_id = (int)$replyRow['reply_id'];
    $stmt = $mysqli->prepare("UPDATE review_replies SET reply_text = ?, created_at = NOW() WHERE reply_id = ?");
    $stmt->bind_param('si', $reply_text, $reply_id);
} else {
    $stmt = $mysqli->prepare("INSERT INTO review_replies (review_id, guide_id, reply_text) VALUES (?, ?, ?)");
    $stmt->bind_param('iis', $review_id, $guide_id, $reply_text);
}
$ok = $stmt->execute();
$stmt->close();
echo json_encode(['ok' => $ok]);
