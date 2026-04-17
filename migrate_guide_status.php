<?php
/**
 * One-time migration: adds `status` to tour_guides so new guides start as Pending
 * and only appear on the landing page after admin approval.
 * Run once: http://localhost/guidemate1/migrate_guide_status.php
 */
require_once 'dbconnect.php';

$result = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'status'");
if ($result && $result->num_rows > 0) {
    echo "<p><strong>Column <code>status</code> already exists</strong> in tour_guides. No change needed.</p>";
    exit;
}

$mysqli->query("ALTER TABLE tour_guides ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'Pending'");
if ($mysqli->error) {
    echo "<p>Error: " . htmlspecialchars($mysqli->error) . "</p>";
    exit;
}

// Set existing guides to Active so they still show on the landing page
$mysqli->query("UPDATE tour_guides SET status = 'Active' WHERE status = 'Pending' OR status IS NULL OR status = ''");
echo "<p><strong>Done.</strong> Column <code>status</code> added to tour_guides. New guides will be Pending until an admin approves them.</p>";
echo "<p><a href='adminDashboard.php'>Go to Admin Dashboard</a></p>";
?>
