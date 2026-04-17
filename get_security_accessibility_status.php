<?php
session_start();

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin only']);
    exit;
}

session_write_close();

function read_project_file($relativePath) {
    $fullPath = __DIR__ . DIRECTORY_SEPARATOR . $relativePath;
    if (!is_file($fullPath)) {
        return '';
    }

    $content = @file_get_contents($fullPath);
    return is_string($content) ? $content : '';
}

function has_all($content, $needles) {
    foreach ($needles as $needle) {
        if ($needle === '') {
            continue;
        }
        if (strpos($content, $needle) === false) {
            return false;
        }
    }
    return true;
}

function has_any($content, $needles) {
    foreach ($needles as $needle) {
        if ($needle !== '' && strpos($content, $needle) !== false) {
            return true;
        }
    }
    return false;
}

function status_from_ratio($passed, $total) {
    if ($total <= 0 || $passed <= 0) {
        return 'Needs attention';
    }
    if ($passed >= $total) {
        return 'Implemented';
    }
    return 'Partial';
}

$signinTouristAdmin = read_project_file('signinTouristAdmin.php');
$signinLegacy = read_project_file('signin.php');
$signinForm = read_project_file('signinTouristAdmin.html');
$landingPage = read_project_file('landingpage.html');
$styleCss = read_project_file('style.css');
$signinCss = read_project_file('signinTouristAdmin.css');
$signupHandler = read_project_file('SignupTouristAdmin.php');
$addAdmin = read_project_file('add_admin.php');
$adminDashboard = read_project_file('adminDashboard.php');
$adminStats = read_project_file('get_admin_stats.php');
$pendingGuides = read_project_file('get_pending_guides.php');
$tourGuideDashboardHtml = read_project_file('tourGuideDashboardNew.html');
$tourGuideDashboardPhp = read_project_file('tourGuideDashboardNew.php');
$adminAutoLogin = read_project_file('admin_auto_login.php');
$createAdminUser = read_project_file('create_admin_user.php');

$secureLoginChecks = 0;
$secureLoginTotal = 4;
if (!has_any($signinTouristAdmin, ['$isAdminDefault', 'password optional'])) {
    $secureLoginChecks++;
}
if (has_any($signinTouristAdmin . $signinLegacy, ['session_regenerate_id(true);'])) {
    $secureLoginChecks++;
}
if (has_all($signinForm, ['id="signin-username"', 'for="signin-username"', 'id="signin-password"', 'for="signin-password"', 'required'])) {
    $secureLoginChecks++;
}
if (
    has_all($adminAutoLogin, ['http_response_code(403);']) &&
    has_all($createAdminUser, ['http_response_code(403);'])
) {
    $secureLoginChecks++;
}
$secureLoginStatus = status_from_ratio($secureLoginChecks, $secureLoginTotal);

$passwordHashChecks = 0;
$passwordHashTotal = 3;
if (has_any($signupHandler, ['password_hash('])) {
    $passwordHashChecks++;
}
if (has_any($addAdmin, ['password_hash('])) {
    $passwordHashChecks++;
}
if (has_any($signinTouristAdmin . $signinLegacy, ['password_verify('])) {
    $passwordHashChecks++;
}
$passwordHashStatus = status_from_ratio($passwordHashChecks, $passwordHashTotal);

$rbacChecks = 0;
$rbacTotal = 4;
if (has_all($adminDashboard, ["\$_SESSION['role']", "!== 'admin'"])) {
    $rbacChecks++;
}
if (has_all($adminStats, ["\$_SESSION['role']", "!== 'admin'"])) {
    $rbacChecks++;
}
if (has_all($pendingGuides, ["\$_SESSION['role']", "!== 'admin'"])) {
    $rbacChecks++;
}
if (!has_any($tourGuideDashboardHtml . $tourGuideDashboardPhp, ["localStorage.getItem('role')", 'localStorage.getItem("role")'])) {
    $rbacChecks++;
}
$rbacStatus = status_from_ratio($rbacChecks, $rbacTotal);

$responsiveChecks = 0;
$responsiveTotal = 3;
if (has_any($landingPage, ['name="viewport"'])) {
    $responsiveChecks++;
}
if (has_any($styleCss, ['@media'])) {
    $responsiveChecks++;
}
if (has_any($signinCss, ['@media'])) {
    $responsiveChecks++;
}
$responsiveStatus = status_from_ratio($responsiveChecks, $responsiveTotal);

$accessibilityChecks = 0;
$accessibilityTotal = 4;
if (has_all($signinForm, ['for="signin-username"', 'for="signin-password"'])) {
    $accessibilityChecks++;
}
if (has_all($landingPage, ['<button type="button" class="sign-out-link"', 'aria-label="'])) {
    $accessibilityChecks++;
}
if (has_any($styleCss, [':focus-visible'])) {
    $accessibilityChecks++;
}
if (has_any($signinCss, [':focus-visible', 'box-shadow: 0 0 0 4px'])) {
    $accessibilityChecks++;
}
$accessibilityStatus = status_from_ratio($accessibilityChecks, $accessibilityTotal);

$items = [
    [
        'label' => 'Secure login authentication',
        'status' => $secureLoginStatus,
        'detail' => $secureLoginStatus === 'Implemented'
            ? 'Passwordless admin shortcuts are disabled and successful logins now refresh the session identifier.'
            : 'Authentication still has inconsistent checks or unsecured entry points that should be closed.'
    ],
    [
        'label' => 'Password encryption',
        'status' => $passwordHashStatus,
        'detail' => $passwordHashStatus === 'Implemented'
            ? 'User creation uses password_hash() and sign-in uses password_verify() for credential checks.'
            : 'Some account paths are missing modern password hashing or verification.'
    ],
    [
        'label' => 'Role-based access control',
        'status' => $rbacStatus,
        'detail' => $rbacStatus === 'Implemented'
            ? 'Protected admin pages and APIs enforce server-side role checks.'
            : 'Core admin routes are protected, but some UI pages still rely on localStorage for client-side role state.'
    ],
    [
        'label' => 'Responsive design',
        'status' => $responsiveStatus,
        'detail' => $responsiveStatus === 'Implemented'
            ? 'Shared pages include viewport support and mobile layout rules for smaller screens.'
            : 'Some pages still need mobile-specific layout handling.'
    ],
    [
        'label' => 'Web browser accessibility',
        'status' => $accessibilityStatus,
        'detail' => $accessibilityStatus === 'Implemented'
            ? 'Form labels, keyboard-safe controls, and visible focus states are present on key entry pages.'
            : 'Some controls or focus treatments still need accessibility improvements.'
    ],
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

if ($rbacStatus !== 'Implemented') {
    $summary['recommendations'][] = [
        'title' => 'Review client-side role checks',
        'detail' => 'Treat localStorage role values as UI state only and keep protected actions behind server-side session checks.',
        'variant' => 'alert',
    ];
}

if ($accessibilityStatus !== 'Implemented') {
    $summary['recommendations'][] = [
        'title' => 'Continue accessibility pass',
        'detail' => 'Extend the new focus and keyboard updates to the remaining guide and tourist pages for consistency.',
        'variant' => '',
    ];
}

if ($secureLoginStatus === 'Implemented') {
    $summary['recommendations'][] = [
        'title' => 'Authentication hardened',
        'detail' => 'The sign-in flow now requires a password for every account and rotates the PHP session ID after login.',
        'variant' => 'success',
    ];
}

if ($responsiveStatus === 'Implemented') {
    $summary['recommendations'][] = [
        'title' => 'Mobile support updated',
        'detail' => 'Landing and admin pages now include responsive layout coverage for smaller viewports.',
        'variant' => 'success',
    ];
}

echo json_encode([
    'items' => $items,
    'summary' => $summary,
]);
