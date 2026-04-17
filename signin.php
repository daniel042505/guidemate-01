<?php
session_start();
require_once 'dbconnect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'] ?? '';

    // Fetch user and role
    $stmt = $mysqli->prepare("SELECT id, password, role FROM users WHERE username = ? AND status = 'Active'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            // Refresh the session identifier after authentication to reduce fixation risk.
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            $role = $user['role'];
            $specificId = '';
            $profileImage = 'photos/default.jpg';
            $firstName = '';
            $lastName = '';

            // 1. If Tourist, get tourist_id
            if ($role === 'tourist') {
                $tStmt = $mysqli->prepare("SELECT tourist_id, first_name, last_name, profile_image FROM tourists WHERE user_id = ?");
                $tStmt->bind_param("i", $user['id']);
                $tStmt->execute();
                $tRes = $tStmt->get_result();
                if ($tRow = $tRes->fetch_assoc()) {
                    $specificId = $tRow['tourist_id'];
                    $firstName = trim((string)($tRow['first_name'] ?? ''));
                    $lastName = trim((string)($tRow['last_name'] ?? ''));
                    if (!empty($tRow['profile_image'])) {
                        $profileImage = $tRow['profile_image'];
                    }
                }
                $tStmt->close();
            } 
            // 2. If Guide, get guide_id
            elseif ($role === 'guide') {
                $gStmt = $mysqli->prepare("SELECT guide_id, first_name, last_name, profile_image FROM tour_guides WHERE user_id = ?");
                $gStmt->bind_param("i", $user['id']);
                $gStmt->execute();
                $gRes = $gStmt->get_result();
                if ($gRow = $gRes->fetch_assoc()) {
                    $specificId = $gRow['guide_id'];
                    $firstName = trim((string)($gRow['first_name'] ?? ''));
                    $lastName = trim((string)($gRow['last_name'] ?? ''));
                    if (!empty($gRow['profile_image'])) {
                        $profileImage = $gRow['profile_image'];
                    }
                }
                $gStmt->close();
            } elseif ($role === 'admin') {
                $aStmt = $mysqli->prepare("SELECT admin_id, first_name, last_name, profile_image FROM admins WHERE user_id = ? LIMIT 1");
                $aStmt->bind_param("i", $user['id']);
                $aStmt->execute();
                $aRes = $aStmt->get_result();
                if ($aRow = $aRes->fetch_assoc()) {
                    $specificId = $aRow['admin_id'];
                    $firstName = trim((string)($aRow['first_name'] ?? ''));
                    $lastName = trim((string)($aRow['last_name'] ?? ''));
                    if (!empty($aRow['profile_image'])) {
                        $profileImage = $aRow['profile_image'];
                    }
                }
                $aStmt->close();
            }

            $fullName = trim($firstName . ' ' . $lastName);
            if ($fullName === '') {
                $fullName = $username;
            }
            $_SESSION['username'] = $fullName;

            $userIdJs = json_encode((string)$user['id']);
            $roleJs = json_encode((string)$role);
            $firstNameJs = json_encode($firstName);
            $lastNameJs = json_encode($lastName);
            $fullNameJs = json_encode($fullName);
            $profileImageJs = json_encode($profileImage);

            // 3. Handle JavaScript LocalStorage and Redirection
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
                localStorage.setItem('userId', " . $userIdJs . ");
                localStorage.setItem('role', " . $roleJs . ");
                localStorage.setItem('firstName', " . $firstNameJs . ");
                localStorage.setItem('lastName', " . $lastNameJs . ");
                localStorage.setItem('fullName', " . $fullNameJs . ");
                localStorage.setItem('firstName:' + " . $roleJs . " + ':' + " . $userIdJs . ", " . $firstNameJs . ");
                localStorage.setItem('lastName:' + " . $roleJs . " + ':' + " . $userIdJs . ", " . $lastNameJs . ");
                localStorage.setItem('profileName:' + " . $roleJs . " + ':' + " . $userIdJs . ", " . $fullNameJs . ");
                localStorage.setItem('profileImage:' + " . $roleJs . " + ':' + " . $userIdJs . ", " . $profileImageJs . ");
                localStorage.setItem('profileImage', " . $profileImageJs . ");";

            if ($role === 'admin') {
                echo "window.location.href = 'adminDashboard.php';";
            } elseif ($role === 'guide') {
                echo "localStorage.setItem('guideId', '" . $specificId . "');
                      window.location.href = 'tourGuideDashboardNew.html';";
            } else {
                echo "localStorage.setItem('touristId', '" . $specificId . "');
                      window.location.href = 'landingpage.html';";
            }
            echo "</script>";
            exit();

        } else {
            echo "<script>alert('Invalid password.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('User not found or account inactive.'); window.history.back();</script>";
    }
}
?>