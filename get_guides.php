<?php
require_once 'dbconnect.php';
require_once 'guide_booking_helpers.php';

header('Content-Type: application/json');

// Only Active guides; exclude suspended (punishment) until suspended_until date has passed
$col = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'status'");
$hasStatus = $col && $col->num_rows > 0;
$col2 = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'suspended_until'");
$hasSuspended = $col2 && $col2->num_rows > 0;

if ($hasStatus) {
    $query = "SELECT guide_id, first_name, last_name, profile_image, specialization, experience_years, service_areas FROM tour_guides WHERE status = 'Active'";
    if ($hasSuspended) {
        $query .= " AND (suspended_until IS NULL OR suspended_until <= CURDATE())";
    }
} else {
    $query = "SELECT guide_id, first_name, last_name, profile_image, specialization, experience_years, service_areas FROM tour_guides";
}
$result = $mysqli->query($query);

$bookedGuideIds = [];
if (ensure_guide_bookings_table($mysqli)) {
    $bookedResult = $mysqli->query("SELECT guide_id FROM guide_bookings WHERE status = 'Approved'");
    if ($bookedResult) {
        while ($bookedRow = $bookedResult->fetch_assoc()) {
            $bookedGuideId = (int) ($bookedRow['guide_id'] ?? 0);
            if ($bookedGuideId > 0) {
                $bookedGuideIds[$bookedGuideId] = true;
            }
        }
    }
}

$ratingMap = [];
$ratingResult = $mysqli->query("
    SELECT guide_id, COUNT(*) AS review_count, COALESCE(AVG(rating), 0) AS avg_rating
    FROM reviews
    WHERE COALESCE(status, 'visible') <> 'hidden'
    GROUP BY guide_id
");
if ($ratingResult) {
    while ($ratingRow = $ratingResult->fetch_assoc()) {
        $guideId = (int)($ratingRow['guide_id'] ?? 0);
        if ($guideId <= 0) continue;
        $ratingMap[$guideId] = [
            'review_count' => (int)($ratingRow['review_count'] ?? 0),
            'avg_rating' => round((float)($ratingRow['avg_rating'] ?? 0), 1),
        ];
    }
}

$guides = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $guideId = (int) ($row['guide_id'] ?? 0);
        $years = isset($row['experience_years']) ? (int)$row['experience_years'] : 0;
        $areas = isset($row['service_areas']) ? trim($row['service_areas']) : '';
        $spec = isset($row['specialization']) ? trim($row['specialization']) : '';
        $parts = [];
        if ($spec) $parts[] = $spec;
        if ($years > 0) $parts[] = $years . ' ' . ($years === 1 ? 'year' : 'years') . ' experience';
        if ($areas !== '') $parts[] = $areas;
        $description = count($parts) > 0 ? implode(' · ', $parts) : 'Experienced tour guide.';
        $guideRatings = $ratingMap[$guideId] ?? ['review_count' => 0, 'avg_rating' => 0];
        $guides[] = [
            'guide_id' => $guideId,
            'name' => trim($row['first_name'] . ' ' . $row['last_name']),
            'description' => $description,
            'specialization' => $spec,
            'service_areas' => $areas,
            'image' => !empty($row['profile_image']) ? $row['profile_image'] : 'photos/default.jpg',
            'rating' => (float)$guideRatings['avg_rating'],
            'review_count' => (int)$guideRatings['review_count'],
            'is_booked' => !empty($bookedGuideIds[$guideId])
        ];
    }
}

echo json_encode($guides);
?>