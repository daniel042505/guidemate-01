<?php
/**
 * Returns reviews for the logged-in guide (for profile page: display and respond).
 */
session_start();
require_once 'dbconnect.php';
require_once 'review_helpers.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'guide' || empty($_SESSION['user_id'])) {
    echo json_encode(['reviews' => []]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$res = $mysqli->query("SELECT guide_id FROM tour_guides WHERE user_id = $user_id");
if (!$res || !$row = $res->fetch_assoc()) {
    echo json_encode(['reviews' => []]);
    exit;
}
$guide_id = (int)$row['guide_id'];
$guideNameRes = $mysqli->query("SELECT first_name, last_name FROM tour_guides WHERE guide_id = $guide_id LIMIT 1");
$guideName = 'Guide';
if ($guideNameRes && $guideNameRow = $guideNameRes->fetch_assoc()) {
    $guideName = trim(($guideNameRow['first_name'] ?? '') . ' ' . ($guideNameRow['last_name'] ?? ''));
    if ($guideName === '') $guideName = 'Guide';
}

gm_ensure_review_replies_table($mysqli);

$sql = "SELECT r.review_id, r.rating, r.comment, r.status, r.created_at,
        t.first_name, t.last_name,
        rr.reply_text, rr.created_at AS reply_at
        FROM reviews r
        LEFT JOIN tourists t ON t.tourist_id = r.tourist_id
        LEFT JOIN review_replies rr ON rr.review_id = r.review_id AND rr.guide_id = r.guide_id
        WHERE r.guide_id = $guide_id
        AND COALESCE(r.status, 'visible') <> 'hidden'
        ORDER BY r.created_at DESC";
$result = $mysqli->query($sql);
$reviews = [];
if ($result) {
    while ($r = $result->fetch_assoc()) {
        $parsed = gm_parse_review_comment($r['comment'] ?? '');
        $reviews[] = [
            'review_id' => (int)$r['review_id'],
            'rating' => (int)$r['rating'],
            'comment' => $parsed['comment'],
            'raw_comment' => $parsed['raw_comment'],
            'location_name' => $parsed['location_name'],
            'guide_name' => $guideName,
            'review_type' => $parsed['review_type'],
            'status' => (string)($r['status'] ?? 'visible'),
            'created_at' => $r['created_at'],
            'tourist_name' => trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')),
            'reply_text' => $r['reply_text'] ?? null,
            'reply_at' => $r['reply_at'] ?? null,
        ];
    }
}
echo json_encode(['reviews' => $reviews]);
