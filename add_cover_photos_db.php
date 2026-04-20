<?php
require_once 'dbconnect.php';

// Check and create cover_image column if it doesn't exist
$col = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'cover_image'");
if (!$col || $col->num_rows === 0) {
    echo "Creating cover_image column...<br>";
    $mysqli->query("ALTER TABLE tour_guides ADD COLUMN cover_image VARCHAR(255) DEFAULT NULL");
}

// Check and create cover_image_updated_at column if it doesn't exist
$colUpdated = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'cover_image_updated_at'");
if (!$colUpdated || $colUpdated->num_rows === 0) {
    echo "Creating cover_image_updated_at column...<br>";
    $mysqli->query("ALTER TABLE tour_guides ADD COLUMN cover_image_updated_at DATE DEFAULT NULL");
}

echo "<h2>Adding Cover Photos to Database</h2>";

// Add sample cover photos to guides that don't have them
$sample_covers = [
    'photos/cover_sample1.jpg',
    'photos/cover_sample2.jpg',
    'photos/cover_sample3.jpg',
    'photos/cover_sample4.jpg',
];

$guides = $mysqli->query("SELECT guide_id, user_id, first_name, last_name FROM tour_guides WHERE cover_image IS NULL LIMIT 10");

$counter = 0;
if ($guides && $guides->num_rows > 0) {
    while ($guide = $guides->fetch_assoc()) {
        $cover = $sample_covers[$counter % count($sample_covers)];
        $stmt = $mysqli->prepare('UPDATE tour_guides SET cover_image = ?, cover_image_updated_at = CURDATE() WHERE guide_id = ?');
        $stmt->bind_param('si', $cover, $guide['guide_id']);
        $stmt->execute();
        echo "✓ Added cover to " . htmlspecialchars($guide['first_name'] . ' ' . $guide['last_name']) . " (Guide ID: " . $guide['guide_id'] . ") - Path: " . $cover . "<br>";
        $counter++;
    }
} else {
    echo "All guides already have cover photos!<br>";
}

// Show all cover photos in database
echo "<h2>Cover Photos in Database:</h2>";
$result = $mysqli->query("SELECT guide_id, first_name, last_name, cover_image, cover_image_updated_at FROM tour_guides WHERE cover_image IS NOT NULL ORDER BY guide_id");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Guide</th><th>Cover Photo Path</th><th>Updated</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['cover_image']) . "</td>";
        echo "<td>" . $row['cover_image_updated_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No cover photos in database.";
}

echo "<br><br><a href='view_cover_photos.php'>View Full Details</a>";
?>
