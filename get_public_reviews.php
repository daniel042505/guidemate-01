<?php
/**
 * Public endpoint for landing page testimonials.
 * Returns the latest visible/reported reviews with tourist display names.
 */
require_once 'dbconnect.php';
require_once 'review_helpers.php';

header('Content-Type: application/json');

$sql = "SELECT r.review_id, r.rating, r.comment, r.created_at,
        t.first_name AS t_first, t.last_name AS t_last
        FROM reviews r
        LEFT JOIN tourists t ON t.tourist_id = r.tourist_id
        WHERE COALESCE(r.status, 'visible') <> 'hidden'
        ORDER BY r.created_at DESC, r.review_id DESC
        LIMIT 12";

$result = $mysqli->query($sql);
$reviews = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $touristName = trim(($row['t_first'] ?? '') . ' ' . ($row['t_last'] ?? ''));
        if ($touristName === '') {
            $touristName = 'Traveler';
        }

        $parsed = gm_parse_review_comment($row['comment'] ?? '');
        $reviews[] = [
            'review_id' => (int)($row['review_id'] ?? 0),
            'tourist_name' => $touristName,
            'rating' => (int)($row['rating'] ?? 0),
            'comment' => $parsed['comment'],
            'created_at' => $row['created_at'] ?? null,
        ];
    }
}

echo json_encode(['reviews' => $reviews]);
?>
