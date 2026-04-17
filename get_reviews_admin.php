<?php
/**
 * Admin: list all tourist reviews (for moderation).
 */
session_start();
require_once 'dbconnect.php';
require_once 'review_helpers.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin only']);
    exit;
}
session_write_close();

// All non-hidden reviews: tourist name, guide/location, rating, comment, reply, date
$sql = "SELECT r.review_id, r.tourist_id, r.guide_id, r.rating, r.comment, r.status, r.created_at,
        t.first_name AS t_first, t.last_name AS t_last,
        g.first_name AS g_first, g.last_name AS g_last,
        rr.reply_text
        FROM reviews r
        LEFT JOIN tourists t ON t.tourist_id = r.tourist_id
        LEFT JOIN tour_guides g ON g.guide_id = r.guide_id
        LEFT JOIN review_replies rr ON rr.review_id = r.review_id AND rr.guide_id = r.guide_id
        WHERE COALESCE(r.status, 'visible') <> 'hidden'
        ORDER BY r.created_at DESC";
$result = $mysqli->query($sql);

$reviews = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $touristName = trim(($row['t_first'] ?? '') . ' ' . ($row['t_last'] ?? ''));
        if ($touristName === '') $touristName = 'Tourist #' . (int)$row['tourist_id'];

        $guideId = isset($row['guide_id']) && $row['guide_id'] !== null ? (int)$row['guide_id'] : null;
        $guideName = 'Guide';
        if ($guideId) {
            $guideName = trim(($row['g_first'] ?? '') . ' ' . ($row['g_last'] ?? ''));
            if ($guideName === '') $guideName = 'Guide #' . $guideId;
        }

        $parsed = gm_parse_review_comment($row['comment'] ?? '');
        $subject = gm_review_subject($guideName, $parsed['location_name']);

        $reviews[] = [
            'review_id'   => (int)$row['review_id'],
            'tourist_name'=> $touristName,
            'subject'     => $subject,
            'subject_type'=> 'guide',
            'guide_name'  => $guideName,
            'location_name' => $parsed['location_name'],
            'review_type' => $parsed['review_type'],
            'rating'      => (int)$row['rating'],
            'comment'     => $parsed['comment'],
            'status'      => (string)($row['status'] ?? 'visible'),
            'reply_text'  => $row['reply_text'] ?? null,
            'created_at'  => $row['created_at'],
        ];
    }
}

echo json_encode($reviews);
