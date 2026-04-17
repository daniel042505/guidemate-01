<?php
session_start();

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Admin only']);
    exit;
}

$logPath = __DIR__ . DIRECTORY_SEPARATOR . 'debug-4384f2.log';

if (!is_file($logPath)) {
    if (@file_put_contents($logPath, '') === false) {
        echo json_encode(['ok' => false, 'error' => 'Could not create debug log file.']);
        exit;
    }
    echo json_encode(['ok' => true, 'message' => 'Debug log created and ready.']);
    exit;
}

if (!is_writable($logPath)) {
    echo json_encode(['ok' => false, 'error' => 'Debug log file is not writable.']);
    exit;
}

if (@file_put_contents($logPath, '') === false) {
    echo json_encode(['ok' => false, 'error' => 'Could not clear debug log file.']);
    exit;
}

echo json_encode(['ok' => true, 'message' => 'Debug log cleared.']);
?>
