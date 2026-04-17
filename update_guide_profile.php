<?php
/**
 * Update guide's experience_years and service_areas (location). Guide must be logged in (session).
 */
session_start();
require_once 'dbconnect.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'guide' || empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Not authorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$experience_years = isset($_POST['experience_years']) ? (int)$_POST['experience_years'] : 0;
$service_areas = isset($_POST['service_areas']) ? trim($_POST['service_areas']) : '';
$specialization = isset($_POST['specialization']) ? trim($_POST['specialization']) : '';

if ($experience_years < 0) $experience_years = 0;
if ($experience_years > 70) $experience_years = 70;

$stmt = $mysqli->prepare("UPDATE tour_guides SET experience_years = ?, service_areas = ?, specialization = ? WHERE user_id = ?");
$stmt->bind_param('issi', $experience_years, $service_areas, $specialization, $user_id);

if ($stmt->execute()) {
    $stmt->close();
    echo json_encode(['ok' => true]);
} else {
    $stmt->close();
    echo json_encode(['ok' => false, 'error' => 'Update failed']);
}
