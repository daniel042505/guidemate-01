<?php
require_once 'dbconnect.php';
$sql = "UPDATE booking_messages SET sender_role='guide' WHERE sender_role='0' AND (meet_time IS NOT NULL OR meeting_location IS NOT NULL)";
if (!$mysqli->query($sql)) {
    echo 'Error: ' . $mysqli->error;
    exit(1);
}
echo 'Updated rows: ' . $mysqli->affected_rows . "\n";
?>
