<?php
session_start();
$prefill_user_id = '';
$prefill_role = '';
require_once 'dbconnect.php';
if (!empty($_GET['user_id']) && !empty($_GET['role'])) {
    $prefill_user_id = (int)$_GET['user_id'];
    $prefill_role = preg_replace('/[^a-z]/', '', $_GET['role']);
} elseif (!empty($_SESSION['role']) && $_SESSION['role'] === 'guide' && !empty($_SESSION['user_id'])) {
    $prefill_user_id = (int)$_SESSION['user_id'];
    $prefill_role = 'guide';
}
$is_change_flow = ($prefill_role === 'guide' && !empty($_GET['change']));
if (!empty($_GET['change']) && ($prefill_role !== 'guide' || empty($prefill_user_id))) {
    header('Location: signinTouristAdmin.html');
    exit;
}

$is_blocked = false;
$blocked_message = '';
if ($is_change_flow && !empty($prefill_user_id)) {
    $col = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'profile_image_updated_at'");
    if (!$col || $col->num_rows === 0) {
        $mysqli->query("ALTER TABLE tour_guides ADD COLUMN profile_image_updated_at DATE DEFAULT NULL");
    }
    $stmt = $mysqli->prepare("SELECT profile_image_updated_at FROM tour_guides WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $prefill_user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && ($row = $res->fetch_assoc()) && !empty($row['profile_image_updated_at'])) {
            $lastTs = strtotime((string)$row['profile_image_updated_at']);
            $nextAllowed = strtotime('+30 days', $lastTs);
            if (time() < $nextAllowed) {
                $is_blocked = true;
                $daysLeft = (int)ceil(($nextAllowed - time()) / 86400);
                $blocked_message = "You can change your profile picture once every 30 days. Please try again in " .
                    ($daysLeft === 1 ? "1 day" : ($daysLeft . " days")) .
                    " (next allowed on " . date('F j, Y', $nextAllowed) . ").";
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GuideMate | <?php echo $is_change_flow ? 'Change' : 'Select'; ?> Profile Picture</title>
    <script src="https://kit.fontawesome.com/ed5caa5a8f.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="SignupTouristAdmin.css">
</head>
<body>

    <div class="signup-wrapper">
        <div class="main-card">
            <div class="image-panel">
                <div class="overlay"></div>
                <div class="panel-content">
                    <div class="brand-logo">
                        <i class="fa-solid fa-earth-americas"></i>
                        <span>GuideMate</span>
                    </div>
                    <div class="hero-text">
                        <h1>Choose Your Avatar</h1>
                        <p>Upload a profile picture to personalize your GuideMate experience.</p>
                    </div>
                </div>
            </div>

            <div class="form-panel">
                <div class="form-header">
                    <h2><?php echo $is_change_flow ? 'Change' : 'Select'; ?> Profile Picture</h2>
                    <p>
                        <?php
                            if ($is_change_flow) {
                                echo $is_blocked ? htmlspecialchars($blocked_message) : 'You can change your profile picture once every 30 days.';
                            } else {
                                echo 'Upload an image or skip to use the default.';
                            }
                        ?>
                    </p>
                </div>

                <form id="profile-pic-form" action="update_profile_pic.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="user_id" id="user_id" value="<?php echo (int)$prefill_user_id; ?>">
                    <input type="hidden" name="role" id="role" value="<?php echo htmlspecialchars($prefill_role); ?>">

                    <div class="input-box">
                        <label><i class="fa-solid fa-camera"></i> Profile Picture</label>
                        <input type="file" name="profile_image" accept="image/*" <?php echo $is_blocked ? 'disabled' : 'required'; ?>>
                        <small>Supported formats: JPG, PNG, GIF. Max size: 2MB.</small>
                    </div>

                    <button type="submit" class="primary-btn" <?php echo $is_blocked ? 'disabled style="margin-top: 20px; opacity: 0.55; cursor: not-allowed;"' : 'style="margin-top: 20px;"'; ?>>
                        <?php echo $is_change_flow ? 'Upload new picture' : 'Upload & Continue'; ?>
                    </button>
                    <button type="button" onclick="skipProfilePic()" class="primary-btn" style="margin-top: 10px; background: #ccc; color: #000;"><?php echo $is_change_flow ? 'Cancel' : 'Skip for Now'; ?></button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // URL params override for signup flow
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('user_id')) document.getElementById('user_id').value = urlParams.get('user_id');
        if (urlParams.get('role')) document.getElementById('role').value = urlParams.get('role');

        function skipProfilePic() {
            if (document.getElementById('role').value === 'guide' && window.location.search.indexOf('change') !== -1) {
                window.location.href = 'tourGuideDashboardNew.html';
            } else {
                window.location.href = 'signinTouristAdmin.html';
            }
        }
    </script>
</body>
</html>