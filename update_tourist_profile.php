<?php
session_start();
header('Content-Type: application/json');

require_once 'dbconnect.php';

if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'tourist') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Not authorized.'
    ]);
    exit();
}

$userId = (int)$_SESSION['user_id'];
$firstName = trim((string)($_POST['first_name'] ?? ''));
$lastName = trim((string)($_POST['last_name'] ?? ''));

if ($firstName === '' || $lastName === '') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'First name and last name are required.'
    ]);
    exit();
}

if (mb_strlen($firstName) > 50 || mb_strlen($lastName) > 50) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Names must be 50 characters or less.'
    ]);
    exit();
}

$col = $mysqli->query("SHOW COLUMNS FROM tourists LIKE 'profile_image_updated_at'");
if (!$col || $col->num_rows === 0) {
    $mysqli->query("ALTER TABLE tourists ADD COLUMN profile_image_updated_at DATE DEFAULT NULL");
}

$currentStmt = $mysqli->prepare("SELECT profile_image, profile_image_updated_at FROM tourists WHERE user_id = ? LIMIT 1");
if (!$currentStmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Could not prepare profile lookup.'
    ]);
    exit();
}

$currentStmt->bind_param('i', $userId);
$currentStmt->execute();
$currentResult = $currentStmt->get_result();
$currentRow = $currentResult ? $currentResult->fetch_assoc() : null;
$currentStmt->close();

if (!$currentRow) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Tourist profile not found.'
    ]);
    exit();
}

$currentImage = (string)($currentRow['profile_image'] ?? 'photos/default.jpg');
$lastUpdatedAt = !empty($currentRow['profile_image_updated_at']) ? (string)$currentRow['profile_image_updated_at'] : null;
$cooldownDays = 15;
$nextAllowed = null;

if ($lastUpdatedAt) {
    $lastTs = strtotime($lastUpdatedAt);
    if ($lastTs) {
        $nextAllowed = strtotime('+' . $cooldownDays . ' days', $lastTs);
    }
}

$hasUpload = isset($_FILES['profile_image']) && (int)($_FILES['profile_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
$newImagePath = $currentImage;

if ($hasUpload) {
    $file = $_FILES['profile_image'];

    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Photo upload failed.'
        ]);
        exit();
    }

    if ($nextAllowed && time() < $nextAllowed) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'You can change your profile photo once every 15 days. Next change allowed on ' . date('F j, Y', $nextAllowed) . '.',
            'can_change_profile_image' => false,
            'next_profile_image_change_at' => date('Y-m-d', $nextAllowed),
            'profile_image_cooldown_days' => $cooldownDays
        ]);
        exit();
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $detectedType = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $detectedType = (string)finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        }
    }
    $fileType = $detectedType !== '' ? $detectedType : (string)($file['type'] ?? '');

    if (!in_array($fileType, $allowedTypes, true)) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid file type. Please upload JPG, PNG, or GIF.'
        ]);
        exit();
    }

    if ((int)$file['size'] > 2 * 1024 * 1024) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'File too large. Maximum size is 2MB.'
        ]);
        exit();
    }

    $extension = strtolower((string)pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    if ($extension === '') {
        $extension = $fileType === 'image/png' ? 'png' : ($fileType === 'image/gif' ? 'gif' : 'jpg');
    }

    $filename = 'tourist_profile_' . $userId . '_' . time() . '.' . $extension;
    $uploadDir = __DIR__ . '/photos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $uploadPath = $uploadDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Could not save uploaded photo.'
        ]);
        exit();
    }

    $newImagePath = 'photos/' . $filename;
}

if ($hasUpload) {
    $updateStmt = $mysqli->prepare("UPDATE tourists SET first_name = ?, last_name = ?, profile_image = ?, profile_image_updated_at = CURDATE() WHERE user_id = ?");
} else {
    $updateStmt = $mysqli->prepare("UPDATE tourists SET first_name = ?, last_name = ? WHERE user_id = ?");
}

if (!$updateStmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Could not prepare profile update.'
    ]);
    exit();
}

if ($hasUpload) {
    $updateStmt->bind_param('sssi', $firstName, $lastName, $newImagePath, $userId);
} else {
    $updateStmt->bind_param('ssi', $firstName, $lastName, $userId);
}

if (!$updateStmt->execute()) {
    if ($updateStmt) {
        $updateStmt->close();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Could not update profile.'
    ]);
    exit();
}
$updateStmt->close();

$fullName = trim($firstName . ' ' . $lastName);
$_SESSION['username'] = $fullName;

$responseNextAllowed = null;
$canChangeProfileImage = true;
if ($hasUpload) {
    $responseNextAllowed = date('Y-m-d', strtotime('+' . $cooldownDays . ' days'));
    $canChangeProfileImage = false;
} elseif ($nextAllowed && time() < $nextAllowed) {
    $responseNextAllowed = date('Y-m-d', $nextAllowed);
    $canChangeProfileImage = false;
}

echo json_encode([
    'success' => true,
    'message' => $hasUpload
        ? 'Profile updated. Your next photo change will be available in 15 days.'
        : 'Profile updated successfully.',
    'user_id' => $userId,
    'role' => 'tourist',
    'first_name' => $firstName,
    'last_name' => $lastName,
    'full_name' => $fullName,
    'profile_image' => $newImagePath,
    'can_change_profile_image' => $canChangeProfileImage,
    'next_profile_image_change_at' => $responseNextAllowed,
    'profile_image_cooldown_days' => $cooldownDays
]);
?>
