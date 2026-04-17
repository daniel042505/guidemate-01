<?php
session_start();
require_once 'dbconnect.php';
require_once 'review_helpers.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin only']);
    exit;
}

$sql = "SELECT r.review_id, r.tourist_id, r.guide_id, r.rating, r.comment, r.status, r.created_at,
        t.first_name AS t_first, t.last_name AS t_last,
        g.first_name AS g_first, g.last_name AS g_last,
        rr.reply_text
        FROM reviews r
        LEFT JOIN tourists t ON t.tourist_id = r.tourist_id
        LEFT JOIN tour_guides g ON g.guide_id = r.guide_id
        LEFT JOIN review_replies rr ON rr.review_id = r.review_id AND rr.guide_id = r.guide_id
        WHERE COALESCE(r.status, 'visible') = 'reported'
        ORDER BY r.created_at DESC";
$result = $mysqli->query($sql);

$reviews = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $touristName = trim((string)($row['t_first'] ?? '') . ' ' . (string)($row['t_last'] ?? ''));
        if ($touristName === '') {
            $touristName = 'Tourist #' . (int)$row['tourist_id'];
        }

        $guideName = trim((string)($row['g_first'] ?? '') . ' ' . (string)($row['g_last'] ?? ''));
        if ($guideName === '') {
            $guideId = (int)($row['guide_id'] ?? 0);
            $guideName = $guideId > 0 ? 'Guide #' . $guideId : 'Guide';
        }

        $parsed = gm_parse_review_comment($row['comment'] ?? '');

        $reviews[] = [
            'review_id' => (int)$row['review_id'],
            'guide_id' => isset($row['guide_id']) ? (int)$row['guide_id'] : 0,
            'tourist_name' => $touristName,
            'guide_name' => $guideName,
            'location_name' => $parsed['location_name'],
            'review_type' => $parsed['review_type'],
            'subject' => gm_review_subject($guideName, $parsed['location_name']),
            'rating' => (int)($row['rating'] ?? 0),
            'comment' => $parsed['comment'],
            'reply_text' => $row['reply_text'] ?? null,
            'status' => (string)($row['status'] ?? 'reported'),
            'created_at' => $row['created_at'] ?? null,
        ];
    }
}

echo json_encode(['reviews' => $reviews]);
