<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
$role = isset($_SESSION['role']) ? (string) $_SESSION['role'] : '';
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
if ($role === 'admin') {
    header('Location: signinTouristAdmin.html');
} else {
    header('Location: landingpage.html');
}
exit;
