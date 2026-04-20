<?php
require_once 'dbconnect.php';

// Check if cover_image column exists
$col = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'cover_image'");
if (!$col || $col->num_rows === 0) {
    echo "<h1>Cover Image Column Does Not Exist</h1>";
    echo "<p>The cover_image column hasn't been created yet. It will be created when you upload your first cover photo.</p>";
    exit;
}

// Get all guides with cover photos
$query = "SELECT guide_id, user_id, first_name, last_name, cover_image, cover_image_updated_at FROM tour_guides WHERE cover_image IS NOT NULL ORDER BY cover_image_updated_at DESC";
$result = $mysqli->query($query);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Cover Photos in Database</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        h1 { color: #333; }
        table { border-collapse: collapse; width: 100%; max-width: 900px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        img { max-width: 150px; max-height: 150px; }
        .no-photo { color: #999; }
    </style>
</head>
<body>
    <h1>Cover Photos in Database</h1>
    
    <?php
    if ($result && $result->num_rows > 0) {
        echo "<p><strong>Total guides with cover photos: " . $result->num_rows . "</strong></p>";
        echo "<table>";
        echo "<tr><th>Guide ID</th><th>User ID</th><th>Name</th><th>Cover Photo Path</th><th>Updated At</th><th>Preview</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            $cover_path = htmlspecialchars($row['cover_image']);
            $file_exists = file_exists($cover_path) ? "✓ File exists" : "✗ File not found";
            $preview = file_exists($cover_path) ? "<img src='" . htmlspecialchars($row['cover_image']) . "' alt='Cover'>" : "<span class='no-photo'>No file</span>";
            
            echo "<tr>";
            echo "<td>" . $row['guide_id'] . "</td>";
            echo "<td>" . $row['user_id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
            echo "<td>" . $cover_path . "<br><small>" . $file_exists . "</small></td>";
            echo "<td>" . htmlspecialchars($row['cover_image_updated_at']) . "</td>";
            echo "<td>" . $preview . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='no-photo'><strong>No cover photos found in database.</strong></p>";
        echo "<p>Upload your first cover photo to get started!</p>";
    }
    
    // Also show all guides without cover photos
    $query_no_photo = "SELECT guide_id, user_id, first_name, last_name FROM tour_guides WHERE cover_image IS NULL ORDER BY first_name";
    $result_no_photo = $mysqli->query($query_no_photo);
    
    if ($result_no_photo && $result_no_photo->num_rows > 0) {
        echo "<h2>Guides Without Cover Photos</h2>";
        echo "<table>";
        echo "<tr><th>Guide ID</th><th>User ID</th><th>Name</th></tr>";
        
        while ($row = $result_no_photo->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['guide_id'] . "</td>";
            echo "<td>" . $row['user_id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    ?>
</body>
</html>
