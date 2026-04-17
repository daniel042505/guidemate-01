<?php
session_start();
header('Content-Type: application/json');

require_once 'dbconnect.php';

if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Not signed in.'
    ]);
    exit();
}

$userId = (int)$_SESSION['user_id'];
$role = (string)$_SESSION['role'];
$username = (string)($_SESSION['username'] ?? '');

$response = [
    'success' => true,
    'user_id' => $userId,
    'role' => $role,
    'first_name' => '',
    'last_name' => '',
    'full_name' => $username !== '' ? $username : 'Guest Traveler',
    'profile_image' => 'photos/default.jpg',
    'can_change_profile_image' => true,
    'next_profile_image_change_at' => null,
    'profile_image_cooldown_days' => null
];

$table = '';
$cooldownDays = 0;
$hasProfileImageUpdatedAt = false;

if ($role === 'tourist') {
    $table = 'tourists';
    $cooldownDays = 15;
} elseif ($role === 'guide') {
    $table = 'tour_guides';
    $cooldownDays = 30;
} elseif ($role === 'admin') {
    $table = 'admins';
}

if ($table !== '') {
    $col = $mysqli->query("SHOW COLUMNS FROM {$table} LIKE 'profile_image_updated_at'");
    if ($col && $col->num_rows > 0) {
        $hasProfileImageUpdatedAt = true;
    }
    $response['profile_image_cooldown_days'] = $cooldownDays;
    $select = 'first_name, last_name, profile_image';
    if ($hasProfileImageUpdatedAt) {
        $select .= ', profile_image_updated_at';
    }
    $stmt = $mysqli->prepare("SELECT {$select} FROM {$table} WHERE user_id = ? LIMIT 1");
} else {
    $stmt = null;
}

if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $response['first_name'] = trim((string)($row['first_name'] ?? ''));
        $response['last_name'] = trim((string)($row['last_name'] ?? ''));
        $response['full_name'] = trim($response['first_name'] . ' ' . $response['last_name']) ?: $response['full_name'];
        if (!empty($row['profile_image'])) {
            $response['profile_image'] = (string)$row['profile_image'];
        }
        if ($hasProfileImageUpdatedAt && !empty($row['profile_image_updated_at']) && $cooldownDays > 0) {
            $lastTs = strtotime((string)$row['profile_image_updated_at']);
            $nextAllowed = strtotime('+' . $cooldownDays . ' days', $lastTs);
            if ($lastTs && $nextAllowed && time() < $nextAllowed) {
                $response['can_change_profile_image'] = false;
                $response['next_profile_image_change_at'] = date('Y-m-d', $nextAllowed);
            }
        }
    }
    $stmt->close();
}

echo json_encode($response);
?>
