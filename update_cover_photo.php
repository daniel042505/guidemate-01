<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'dbconnect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$role = isset($_POST['role']) ? trim($_POST['role']) : '';

if ($user_id <= 0 || $role !== 'guide') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid request']);
    exit;
}

if (!isset($_FILES['cover_image']) || $_FILES['cover_image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No cover image uploaded']);
    exit;
}

$file = $_FILES['cover_image'];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowed_types, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid file type']);
    exit;
}

if ($file['size'] > 4 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'File too large']);
    exit;
}

$col = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'cover_image'");
if (!$col || $col->num_rows === 0) {
    $mysqli->query("ALTER TABLE tour_guides ADD COLUMN cover_image VARCHAR(255) DEFAULT NULL");
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'cover_' . $user_id . '_' . time() . '.' . $ext;
$upload_dir = __DIR__ . '/photos/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}
$upload_path = $upload_dir . $filename;
$db_path = 'photos/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Upload failed']);
    exit;
}

$stmt = $mysqli->prepare('UPDATE tour_guides SET cover_image = ? WHERE user_id = ?');
$stmt->bind_param('si', $db_path, $user_id);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save cover photo']);
    exit;
}

echo json_encode(['ok' => true, 'cover_image' => $db_path]);
