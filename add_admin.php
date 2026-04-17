<?php
/**
 * Add another admin user. Only works when an existing admin is logged in (session).
 */
session_start();
require_once 'dbconnect.php';

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: signinTouristAdmin.html');
    exit;
}
$adminName = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $check = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param('s', $username);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'That username is already taken.';
            $check->close();
        } else {
            $check->close();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role   = 'admin';
            $status = 'Active';

            $mysqli->begin_transaction();
            try {
                $stmt = $mysqli->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, ?)");
                if (!$stmt) {
                    throw new Exception('Could not prepare admin credentials.');
                }
                $stmt->bind_param('ssss', $username, $hash, $role, $status);
                if (!$stmt->execute()) {
                    throw new Exception('Could not create admin credentials.');
                }
                $newUserId = (int)$mysqli->insert_id;
                $stmt->close();

                $firstName = $username;
                $lastName = '';
                $email = '';
                $adminStmt = $mysqli->prepare("INSERT INTO admins (user_id, first_name, last_name, email, profile_image) VALUES (?, ?, ?, ?, NULL)");
                if (!$adminStmt) {
                    throw new Exception('Could not prepare admin profile.');
                }
                $adminStmt->bind_param('isss', $newUserId, $firstName, $lastName, $email);
                if (!$adminStmt->execute()) {
                    throw new Exception('Could not create admin profile.');
                }
                $adminStmt->close();

                $mysqli->commit();
                $message = "Admin \"{$username}\" created. They can sign in with that username and password.";
            } catch (Throwable $e) {
                $mysqli->rollback();
                if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                    $stmt->close();
                }
                if (isset($adminStmt) && $adminStmt instanceof mysqli_stmt) {
                    $adminStmt->close();
                }
                $error = $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Admin | GuideMate</title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="adminDashboard.css">
    <link rel="stylesheet" href="add_admin.css">
</head>
<body>
    <nav class="glass-nav">
        <div class="logo">GuideMate Admin</div>
        <div class="nav-links">
            <a href="adminDashboard.php" style="color:inherit;text-decoration:none;font-size:0.9rem;">REAL-TIME MAP</a>
            <a href="adminDashboard.php" style="color:inherit;text-decoration:none;font-size:0.9rem;">FLEET STATUS</a>
            <a href="adminDashboard.php" style="color:inherit;text-decoration:none;font-size:0.9rem;">REVENUE</a>
            <span class="nav-active">ADD ADMIN</span>
            <a href="logout.php" class="logout-link">LOGOUT</a>
        </div>
        <div class="user-id"><i data-feather="shield"></i> Super_Admin: <b><?= htmlspecialchars($adminName) ?></b></div>
    </nav>

    <header class="dashboard-header">
        <p>ADMIN MANAGEMENT</p>
        <h1>Add new administrator</h1>
    </header>

    <section class="add-admin-section">
        <div class="panel add-admin-panel">
            <div class="panel-head">
                <h3><i data-feather="user-plus"></i> New admin account</h3>
                <span class="panel-subtitle">Create credentials for another admin. They will use these to sign in to the dashboard.</span>
            </div>
            <?php if ($message): ?>
                <div class="alert-msg ok"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert-msg err"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="add_admin.php" class="add-admin-form">
                <div class="form-row">
                    <label for="username"><i data-feather="user"></i> Username</label>
                    <input type="text" id="username" name="username" placeholder="e.g. Jane D." required autocomplete="username" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                </div>
                <div class="form-row">
                    <label for="password"><i data-feather="lock"></i> Password</label>
                    <input type="password" id="password" name="password" placeholder="Choose a secure password" required autocomplete="new-password">
                </div>
                <div class="form-actions">
                    <button type="submit" class="cmd-btn btn-primary"><i data-feather="user-plus"></i> Create admin</button>
                    <a href="adminDashboard.php" class="cmd-btn btn-back"><i data-feather="arrow-left"></i> Back to dashboard</a>
                </div>
            </form>
        </div>
    </section>

    <script src="logout_modal.js"></script>
    <script>feather.replace();</script>
    <script>
    (function() {
        var logoutLink = document.querySelector('a.logout-link');
        if (logoutLink && typeof showLogoutConfirm === 'function') {
            logoutLink.addEventListener('click', function(e) {
                e.preventDefault();
                showLogoutConfirm(function() { window.location.href = 'logout.php'; });
            });
        }
    })();
    </script>
</body>
</html>
