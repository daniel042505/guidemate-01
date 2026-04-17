<?php
session_start();
require_once 'dbconnect.php'; // Using your project's connection file

// #region agent log
function _debug_log($data) {
    $payload = array_merge(['sessionId'=>'4384f2','timestamp'=>round(microtime(true)*1000),'location'=>'signinTouristAdmin.php'], $data);
    @file_put_contents(__DIR__ . '/debug-4384f2.log', json_encode($payload) . "\n", FILE_APPEND | LOCK_EX);
}
// #endregion

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'] ?? '';

    // #region agent log
    _debug_log(['message'=>'POST received','data'=>['username'=>$username,'username_len'=>strlen($username),'has_password'=>strlen($password)>0],'hypothesisId'=>'H3,H4']);
    // #endregion

    // 1. Fetch user details
    $stmt = $mysqli->prepare("SELECT id, password, role FROM users WHERE username = ? AND status = 'Active'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // #region agent log
    $userByUsername = null;
    $stmtDebug = $mysqli->prepare("SELECT id, role, status FROM users WHERE username = ?");
    if ($stmtDebug) { $stmtDebug->bind_param("s", $username); $stmtDebug->execute(); $resDebug = $stmtDebug->get_result(); $userByUsername = $resDebug->fetch_assoc(); $stmtDebug->close(); }
    _debug_log(['message'=>'lookup result','data'=>['active_row_found'=>($result->num_rows > 0),'user_by_username'=>$userByUsername],'hypothesisId'=>'H1,H2,H5']);
    // #endregion

    if ($user = $result->fetch_assoc()) {
        $userId = $user['id'];
        $role = $user['role'];
        $hashedPassword = $user['password'];

        $isPasswordCorrect = password_verify($password, $hashedPassword);

        if ($isPasswordCorrect) {
            
            // Refresh the session identifier after authentication to reduce fixation risk.
            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $_SESSION['role'] = $role;

            $specificId = '';
            $profileImage = 'photos/default.jpg';
            $firstName = '';
            $lastName = '';

            // 4. Retrieve specific IDs for Tourists or Guides
            if ($role === 'tourist') {
                $tStmt = $mysqli->prepare("SELECT tourist_id, first_name, last_name, profile_image FROM tourists WHERE user_id = ?");
                $tStmt->bind_param("i", $userId);
                $tStmt->execute();
                $tRes = $tStmt->get_result();
                if ($row = $tRes->fetch_assoc()) {
                    $specificId = $row['tourist_id'];
                    $firstName = trim((string)($row['first_name'] ?? ''));
                    $lastName = trim((string)($row['last_name'] ?? ''));
                    if (!empty($row['profile_image'])) {
                        $profileImage = $row['profile_image'];
                    }
                }
                $tStmt->close();
            } elseif ($role === 'guide') {
                $gStmt = $mysqli->prepare("SELECT guide_id, first_name, last_name, profile_image FROM tour_guides WHERE user_id = ?");
                $gStmt->bind_param("i", $userId);
                $gStmt->execute();
                $gRes = $gStmt->get_result();
                if ($row = $gRes->fetch_assoc()) {
                    $specificId = $row['guide_id'];
                    $firstName = trim((string)($row['first_name'] ?? ''));
                    $lastName = trim((string)($row['last_name'] ?? ''));
                    if (!empty($row['profile_image'])) {
                        $profileImage = $row['profile_image'];
                    }
                }
                $gStmt->close();
            } elseif ($role === 'admin') {
                $aStmt = $mysqli->prepare("SELECT admin_id, first_name, last_name, profile_image FROM admins WHERE user_id = ? LIMIT 1");
                $aStmt->bind_param("i", $userId);
                $aStmt->execute();
                $aRes = $aStmt->get_result();
                if ($row = $aRes->fetch_assoc()) {
                    $specificId = $row['admin_id'];
                    $firstName = trim((string)($row['first_name'] ?? ''));
                    $lastName = trim((string)($row['last_name'] ?? ''));
                    if (!empty($row['profile_image'])) {
                        $profileImage = $row['profile_image'];
                    }
                }
                $aStmt->close();
            }

            $fullName = trim($firstName . ' ' . $lastName);
            if ($fullName === '') {
                $fullName = $username;
            }
            $_SESSION['username'] = $fullName;

            $userIdJs = json_encode((string)$userId);
            $roleJs = json_encode((string)$role);
            $firstNameJs = json_encode($firstName);
            $lastNameJs = json_encode($lastName);
            $fullNameJs = json_encode($fullName);
            $profileImageJs = json_encode($profileImage);

            // 5. Redirection Logic
            echo "<script>
                var previousRole = localStorage.getItem('role') || '';
                var previousUserId = localStorage.getItem('userId') || '';
                if (previousRole && previousUserId) {
                    localStorage.removeItem('firstName:' + previousRole + ':' + previousUserId);
                    localStorage.removeItem('lastName:' + previousRole + ':' + previousUserId);
                    localStorage.removeItem('profileName:' + previousRole + ':' + previousUserId);
                    localStorage.removeItem('profileImage:' + previousRole + ':' + previousUserId);
                }
                localStorage.removeItem('touristId');
                localStorage.removeItem('guideId');
                localStorage.removeItem('userReviews');
                localStorage.setItem('userLoggedIn', 'true');
                localStorage.setItem('userId', $userIdJs);
                localStorage.setItem('role', $roleJs);
                localStorage.setItem('firstName', $firstNameJs);
                localStorage.setItem('lastName', $lastNameJs);
                localStorage.setItem('fullName', $fullNameJs);
                localStorage.setItem('firstName:' + $roleJs + ':' + $userIdJs, $firstNameJs);
                localStorage.setItem('lastName:' + $roleJs + ':' + $userIdJs, $lastNameJs);
                localStorage.setItem('profileName:' + $roleJs + ':' + $userIdJs, $fullNameJs);
                localStorage.setItem('profileImage:' + $roleJs + ':' + $userIdJs, $profileImageJs);
                localStorage.setItem('profileImage', $profileImageJs);";

            if ($role === 'admin') {
                echo "window.location.href = 'adminDashboard.php';";
            } elseif ($role === 'guide') {
                echo "localStorage.setItem('guideId', '$specificId');
                      window.location.href = 'tourGuideDashboardNew.html';";
            } else {
                echo "localStorage.setItem('touristId', '$specificId');
                      window.location.href = 'landingpage.html';";
            }
            
            echo "</script>";
            exit();

        } else {
            echo "<script>alert('Invalid password.'); window.history.back();</script>";
        }
    } else {
        // #region agent log
        _debug_log(['message'=>'Account not found or inactive branch','data'=>['username'=>$username],'hypothesisId'=>'H1,H2']);
        // #endregion
        echo "<script>alert('Account not found or inactive.'); window.history.back();</script>";
    }
}
?>