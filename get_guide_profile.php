<?php
/**
 * Returns the current guide's profile (experience_years, service_areas) for the dashboard.
 * Uses session if guide is logged in, or guide_id from request (e.g. after registration).
 */
session_start();
require_once 'dbconnect.php';

header('Content-Type: application/json');

$has_profile_image_updated_at = false;
$col = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'profile_image_updated_at'");
if ($col && $col->num_rows > 0) {
    $has_profile_image_updated_at = true;
}

$guide_id = isset($_GET['guide_id']) ? (int)$_GET['guide_id'] : (isset($_POST['guide_id']) ? (int)$_POST['guide_id'] : 0);

$user_id = null;
if (!empty($_SESSION['role']) && $_SESSION['role'] === 'guide' && !empty($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
}

if ($user_id) {
    $select = "guide_id, first_name, last_name, experience_years, service_areas, specialization";
    if ($has_profile_image_updated_at) $select .= ", profile_image_updated_at";
    $stmt = $mysqli->prepare("SELECT $select FROM tour_guides WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
} elseif ($guide_id > 0) {
    $select = "guide_id, first_name, last_name, experience_years, service_areas, specialization";
    if ($has_profile_image_updated_at) $select .= ", profile_image_updated_at";
    $stmt = $mysqli->prepare("SELECT $select FROM tour_guides WHERE guide_id = ?");
    $stmt->bind_param('i', $guide_id);
} else {
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();
if (!$result || $result->num_rows === 0) {
    $stmt->close();
    echo json_encode(['experience_years' => 0, 'service_areas' => '', 'specialization' => '', 'first_name' => '', 'last_name' => '', 'avg_rating' => 0, 'review_count' => 0]);
    exit;
}

$row = $result->fetch_assoc();
$gid = (int)$row['guide_id'];
$stmt->close();

// Average rating and review count from active reviews only.
$ratingRow = $mysqli->query("SELECT COUNT(*) AS cnt, COALESCE(AVG(rating), 0) AS avg_rating FROM reviews WHERE guide_id = " . $gid . " AND COALESCE(status, 'visible') <> 'hidden'");
$review_count = 0;
$avg_rating = 0;
if ($ratingRow && $r = $ratingRow->fetch_assoc()) {
    $review_count = (int)$r['cnt'];
    $avg_rating = round((float)$r['avg_rating'], 1);
}

echo json_encode([
    'guide_id' => $gid,
    'first_name' => $row['first_name'] ?? '',
    'last_name' => $row['last_name'] ?? '',
    'experience_years' => (int)($row['experience_years'] ?? 0),
    'service_areas' => $row['service_areas'] ?? '',
    'specialization' => $row['specialization'] ?? '',
    'avg_rating' => $avg_rating,
    'review_count' => $review_count,
    'profile_image_updated_at' => $has_profile_image_updated_at ? ($row['profile_image_updated_at'] ?? null) : null,
]);
