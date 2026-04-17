<?php
session_start();

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin only']);
    exit;
}

require_once 'dbconnect.php';
require_once 'guide_booking_helpers.php';

session_write_close();

function maintenance_status_from_ratio($passed, $total)
{
    if ($total <= 0 || $passed <= 0) {
        return 'Needs attention';
    }
    if ($passed >= $total) {
        return 'Implemented';
    }
    return 'Partial';
}

function maintenance_format_bytes($bytes)
{
    $bytes = (float) $bytes;
    if ($bytes <= 0) {
        return '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB'];
    $power = (int) floor(log($bytes, 1024));
    $power = max(0, min($power, count($units) - 1));
    $value = $bytes / pow(1024, $power);
    $precision = $power === 0 ? 0 : 1;

    return number_format($value, $precision) . ' ' . $units[$power];
}

$projectRoot = __DIR__;
$debugLogPath = $projectRoot . DIRECTORY_SEPARATOR . 'debug-4384f2.log';
$dbconnectPath = $projectRoot . DIRECTORY_SEPARATOR . 'dbconnect.php';
$bookingHelpersPath = $projectRoot . DIRECTORY_SEPARATOR . 'guide_booking_helpers.php';
$clearLogEndpointPath = $projectRoot . DIRECTORY_SEPARATOR . 'clear_debug_log.php';

$logExists = is_file($debugLogPath);
$logReadable = $logExists && is_readable($debugLogPath);
$logWritable = $logExists
    ? is_writable($debugLogPath)
    : is_writable($projectRoot);
$logSizeBytes = ($logExists && $logReadable) ? (int) filesize($debugLogPath) : 0;
$logModifiedAt = ($logExists && $logReadable && filemtime($debugLogPath) !== false)
    ? date('M j, Y g:i A', filemtime($debugLogPath))
    : null;

$items = [];

$logAvailabilityChecks = 0;
$logAvailabilityTotal = 2;
if ($logExists) {
    $logAvailabilityChecks++;
}
if ($logReadable || !$logExists) {
    $logAvailabilityChecks++;
}
$logAvailabilityStatus = maintenance_status_from_ratio($logAvailabilityChecks, $logAvailabilityTotal);
$logAvailabilityDetail = $logExists
    ? 'Debug log file is present' . ($logReadable ? ' and readable.' : ' but not readable.')
    : 'Debug log file has not been created yet. It will appear after the first logged event.';
$items[] = [
    'label' => 'Debug log availability',
    'status' => $logAvailabilityStatus,
    'detail' => $logAvailabilityDetail,
];

$logWriteChecks = 0;
$logWriteTotal = 2;
if ($logWritable) {
    $logWriteChecks++;
}
if (is_file($clearLogEndpointPath)) {
    $logWriteChecks++;
}
$logWriteStatus = maintenance_status_from_ratio($logWriteChecks, $logWriteTotal);
$items[] = [
    'label' => 'Debug log maintenance action',
    'status' => $logWriteStatus,
    'detail' => $logWritable
        ? 'The admin dashboard can clear the current debug log contents.'
        : 'The debug log path is not writable, so cleanup cannot run safely.',
];

$bookingReady = ensure_guide_bookings_table($mysqli);
$statusCounts = [
    'pending' => 0,
    'approved' => 0,
    'completed' => 0,
    'other' => 0,
];

if ($bookingReady) {
    $result = $mysqli->query("SELECT status, COUNT(*) AS count FROM guide_bookings GROUP BY status");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $status = strtolower(trim((string) ($row['status'] ?? '')));
            $count = (int) ($row['count'] ?? 0);
            if (array_key_exists($status, $statusCounts)) {
                $statusCounts[$status] = $count;
            } else {
                $statusCounts['other'] += $count;
            }
        }
    }
}

$bookingChecks = 0;
$bookingTotal = 2;
if ($bookingReady) {
    $bookingChecks++;
}
if ($statusCounts['other'] === 0) {
    $bookingChecks++;
}
$bookingStatus = maintenance_status_from_ratio($bookingChecks, $bookingTotal);
$bookingDetail = !$bookingReady
    ? 'The guide bookings table could not be prepared.'
    : 'Pending: ' . $statusCounts['pending'] .
        ', Approved: ' . $statusCounts['approved'] .
        ', Completed: ' . $statusCounts['completed'] .
        ($statusCounts['other'] > 0 ? ', Other: ' . $statusCounts['other'] : '');
$items[] = [
    'label' => 'Guide booking maintenance check',
    'status' => $bookingStatus,
    'detail' => $bookingDetail,
];

$projectChecks = 0;
$projectTotal = 3;
if (is_file($dbconnectPath) && is_readable($dbconnectPath)) {
    $projectChecks++;
}
if (is_file($bookingHelpersPath) && is_readable($bookingHelpersPath)) {
    $projectChecks++;
}
if (is_file($clearLogEndpointPath) && is_readable($clearLogEndpointPath)) {
    $projectChecks++;
}
$projectStatus = maintenance_status_from_ratio($projectChecks, $projectTotal);
$items[] = [
    'label' => 'Maintenance file readiness',
    'status' => $projectStatus,
    'detail' => 'Core maintenance files checked: db connection, booking helpers, and debug-log cleanup endpoint.',
];

$summary = [
    'implemented' => 0,
    'partial' => 0,
    'needs_attention' => 0,
    'updated_at' => date('M j, Y g:i A'),
    'recommendations' => [],
];

foreach ($items as $item) {
    if ($item['status'] === 'Implemented') {
        $summary['implemented']++;
    } elseif ($item['status'] === 'Partial') {
        $summary['partial']++;
    } else {
        $summary['needs_attention']++;
    }
}

if (!$logExists) {
    $summary['recommendations'][] = [
        'title' => 'Debug log not created yet',
        'detail' => 'No debug log file was found. This is safe, but cleanup and inspection will stay idle until a log entry is written.',
        'variant' => '',
    ];
}

if ($logSizeBytes > 1024 * 1024) {
    $summary['recommendations'][] = [
        'title' => 'Debug log is getting large',
        'detail' => 'The current log size is ' . maintenance_format_bytes($logSizeBytes) . '. Consider clearing it after reviewing the latest entries.',
        'variant' => 'alert',
    ];
}

if ($statusCounts['other'] > 0) {
    $summary['recommendations'][] = [
        'title' => 'Unexpected booking states found',
        'detail' => 'Some guide bookings use a status outside Pending, Approved, or Completed. Review those rows before adding more booking automation.',
        'variant' => 'alert',
    ];
}

if ($bookingReady && $statusCounts['approved'] > 0) {
    $summary['recommendations'][] = [
        'title' => 'Approved bookings need manual release',
        'detail' => 'Guides with approved bookings stay unavailable until an admin marks them available again after the trip.',
        'variant' => '',
    ];
}

echo json_encode([
    'items' => $items,
    'summary' => $summary,
    'log' => [
        'exists' => $logExists,
        'readable' => $logReadable,
        'writable' => $logWritable,
        'size_bytes' => $logSizeBytes,
        'size_human' => maintenance_format_bytes($logSizeBytes),
        'modified_at' => $logModifiedAt,
        'can_clear' => $logWritable,
    ],
    'booking_counts' => $statusCounts,
]);
?>
