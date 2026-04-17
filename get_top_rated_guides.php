<?php
session_start();
require_once 'dbconnect.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin only']);
    exit;
}

$sort = isset($_GET['sort']) ? trim((string)$_GET['sort']) : 'highest';
$minReviews = isset($_GET['min_reviews']) ? (int)$_GET['min_reviews'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 8;

if ($minReviews < 1) {
    $minReviews = 1;
}
if ($limit < 1) {
    $limit = 8;
}
if ($limit > 25) {
    $limit = 25;
}

$orderBy = "avg_rating DESC, review_count DESC, guide_name ASC";
if ($sort === 'most_reviews') {
    $orderBy = "review_count DESC, avg_rating DESC, guide_name ASC";
} elseif ($sort === 'recent') {
    $orderBy = "last_review_at DESC, avg_rating DESC, guide_name ASC";
} else {
    $sort = 'highest';
}

$sql = "SELECT
            g.guide_id,
            TRIM(CONCAT(COALESCE(g.first_name, ''), ' ', COALESCE(g.last_name, ''))) AS guide_name,
            COUNT(r.review_id) AS review_count,
            ROUND(AVG(r.rating), 1) AS avg_rating,
            MAX(r.created_at) AS last_review_at
        FROM tour_guides g
        INNER JOIN reviews r ON r.guide_id = g.guide_id
            AND COALESCE(r.status, 'visible') <> 'hidden'
        GROUP BY g.guide_id, g.first_name, g.last_name
        HAVING COUNT(r.review_id) >= ?
        ORDER BY {$orderBy}
        LIMIT ?";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    echo json_encode(['guides' => [], 'error' => 'Could not prepare guide ranking query.']);
    exit;
}

$stmt->bind_param('ii', $minReviews, $limit);
$stmt->execute();
$result = $stmt->get_result();

$guides = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $guideName = trim((string)($row['guide_name'] ?? ''));
        if ($guideName === '') {
            $guideName = 'Guide #' . (int)$row['guide_id'];
        }

        $guides[] = [
            'guide_id' => (int)$row['guide_id'],
            'guide_name' => $guideName,
            'avg_rating' => (float)($row['avg_rating'] ?? 0),
            'review_count' => (int)($row['review_count'] ?? 0),
            'last_review_at' => $row['last_review_at'] ?? null,
        ];
    }
}

$stmt->close();

echo json_encode([
    'sort' => $sort,
    'min_reviews' => $minReviews,
    'guides' => $guides,
]);
