<?php
session_start();
require_once 'dbconnect.php';
require_once 'review_helpers.php';
require_once 'guide_booking_helpers.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'tourist' || empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode([
        'reviews' => [],
        'eligible_guides' => [],
        'error' => 'Please sign in as tourist.'
    ]);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$tourist = get_tourist_by_user_id($mysqli, $userId);
if (!$tourist || empty($tourist['tourist_id'])) {
    echo json_encode([
        'reviews' => [],
        'eligible_guides' => [],
        'error' => 'Tourist account not found.'
    ]);
    exit;
}

$touristId = (int) $tourist['tourist_id'];
ensure_guide_bookings_table($mysqli);

$reviews = [];
$reviewStmt = $mysqli->prepare("
    SELECT
        r.review_id,
        r.rating,
        r.comment,
        r.created_at,
        r.status,
        g.first_name AS guide_first_name,
        g.last_name AS guide_last_name
    FROM reviews r
    LEFT JOIN tour_guides g ON g.guide_id = r.guide_id
    WHERE r.tourist_id = ?
      AND COALESCE(r.status, 'visible') <> 'hidden'
    ORDER BY r.created_at DESC, r.review_id DESC
");
if ($reviewStmt) {
    $reviewStmt->bind_param('i', $touristId);
    $reviewStmt->execute();
    $reviewRes = $reviewStmt->get_result();
    while ($reviewRes && $row = $reviewRes->fetch_assoc()) {
        $parsed = gm_parse_review_comment($row['comment'] ?? '');
        $guideName = trim(($row['guide_first_name'] ?? '') . ' ' . ($row['guide_last_name'] ?? ''));
        $reviews[] = [
            'review_id' => (int) ($row['review_id'] ?? 0),
            'rating' => (int) ($row['rating'] ?? 0),
            'comment' => $parsed['comment'],
            'location_name' => $parsed['location_name'],
            'review_type' => $parsed['review_type'],
            'guide_name' => $guideName !== '' ? $guideName : 'Guide',
            'created_at' => $row['created_at'] ?? null,
            'status' => (string) ($row['status'] ?? 'visible'),
        ];
    }
    $reviewStmt->close();
}

$eligibleGuides = [];
$guideStmt = $mysqli->prepare("
    SELECT DISTINCT
        gb.guide_id,
        TRIM(CONCAT(COALESCE(g.first_name, ''), ' ', COALESCE(g.last_name, ''))) AS guide_name
    FROM guide_bookings gb
    LEFT JOIN tour_guides g ON g.guide_id = gb.guide_id
    WHERE gb.tourist_user_id = ?
      AND gb.status IN ('Approved', 'Completed')
    ORDER BY guide_name ASC, gb.guide_id ASC
");
if ($guideStmt) {
    $guideStmt->bind_param('i', $userId);
    $guideStmt->execute();
    $guideRes = $guideStmt->get_result();
    while ($guideRes && $row = $guideRes->fetch_assoc()) {
        $guideId = (int) ($row['guide_id'] ?? 0);
        if ($guideId <= 0) {
            continue;
        }
        $eligibleGuides[] = [
            'guide_id' => $guideId,
            'name' => trim((string) ($row['guide_name'] ?? '')) ?: ('Guide #' . $guideId),
        ];
    }
    $guideStmt->close();
}

echo json_encode([
    'reviews' => $reviews,
    'eligible_guides' => $eligibleGuides,
]);
?>
