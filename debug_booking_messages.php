<?php
require_once 'dbconnect.php';
$res = $mysqli->query("SELECT booking_id, message_id, sender_role, message_text, meet_time, meeting_location, created_at FROM booking_messages ORDER BY message_id DESC LIMIT 10");
if (!$res) {
    echo 'Query failed: ' . $mysqli->error;
    exit(1);
}
while ($r = $res->fetch_assoc()) {
    echo json_encode($r, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}
?>
