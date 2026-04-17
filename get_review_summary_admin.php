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

$sql = "SELECT review_id, rating, comment, status, created_at
        FROM reviews
        WHERE COALESCE(status, 'visible') <> 'hidden'
        ORDER BY created_at DESC";
$result = $mysqli->query($sql);

$totalReviews = 0;
$ratingSum = 0.0;
$reportedReviews = 0;
$guideReviews = 0;
$locationReviews = 0;
$distribution = [
    '5' => 0,
    '4' => 0,
    '3' => 0,
    '2' => 0,
    '1' => 0,
];
$recentReviews = [];
$latestReviewAt = null;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $totalReviews++;
        $rating = (int)($row['rating'] ?? 0);
        if ($rating >= 1 && $rating <= 5) {
            $distribution[(string)$rating]++;
            $ratingSum += $rating;
        }

        $status = (string)($row['status'] ?? 'visible');
        if ($status === 'reported') {
            $reportedReviews++;
        }

        $parsed = gm_parse_review_comment($row['comment'] ?? '');
        if (($parsed['review_type'] ?? 'location') === 'guide') {
            $guideReviews++;
        } else {
            $locationReviews++;
        }

        if ($latestReviewAt === null) {
            $latestReviewAt = $row['created_at'] ?? null;
        }

        if (count($recentReviews) < 5) {
            $recentReviews[] = [
                'review_id' => (int)$row['review_id'],
                'rating' => $rating,
                'comment' => $parsed['comment'],
                'review_type' => $parsed['review_type'],
                'location_name' => $parsed['location_name'],
                'status' => $status,
                'created_at' => $row['created_at'] ?? null,
            ];
        }
    }
}

$averageRating = $totalReviews > 0 ? round($ratingSum / $totalReviews, 1) : 0;

echo json_encode([
    'total_reviews' => $totalReviews,
    'average_rating' => $averageRating,
    'reported_reviews' => $reportedReviews,
    'guide_reviews' => $guideReviews,
    'location_reviews' => $locationReviews,
    'distribution' => $distribution,
    'latest_review_at' => $latestReviewAt,
    'recent_reviews' => $recentReviews,
]);
