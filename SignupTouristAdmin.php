<?php
require_once 'dbconnect.php'; 

/**
 * Helper function to handle errors and rollback transactions
 */
function handleError($mysqli, $message) {
    if ($mysqli && $mysqli->connect_errno === 0) {
        // Rollback any changes if a transaction was started
        $mysqli->rollback();
    }
    // Alert the user and send them back to the form
    die("<script>alert('" . addslashes($message) . "'); window.history.back();</script>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Collect and sanitize inputs
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $contact    = trim($_POST['contact'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';
    $role       = $_POST['role'] ?? 'tourist'; 

    // 2. Security: Block any manual 'admin' registration attempts via this script
    if ($role === 'admin') {
        handleError($mysqli, "Unauthorized registration role.");
    }

    // 3. Validation: Ensure required fields are not empty
    if (empty($username) || empty($password) || empty($email)) {
        handleError($mysqli, "Please fill in all required fields.");
    }

    // 4. Check if username exists (Prevention of duplicate accounts)
    $checkUser = $mysqli->prepare("SELECT username FROM users WHERE username = ?");
    $checkUser->bind_param("s", $username);
    $checkUser->execute();
    if ($checkUser->get_result()->num_rows > 0) {
        handleError($mysqli, "Username is already taken.");
    }

    // Hash password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Start Transaction to ensure data is saved in both tables or none at all
    $mysqli->begin_transaction();

    try {
        // 5. Insert into main 'users' table
        $stmt1 = $mysqli->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, 'Active')");
        $stmt1->bind_param('sss', $username, $hashed_password, $role);
        $stmt1->execute();
        
        // Retrieve the generated user_id for the profile tables
        $user_id = $mysqli->insert_id;

        // 6. Role-based insertion into specific profile tables
        if ($role === 'guide') {
            // Insert into tour_guides (include status if column exists so admin can add guide to landing page)
            $hasStatus = $mysqli->query("SHOW COLUMNS FROM tour_guides LIKE 'status'");
            $hasStatus = $hasStatus && $hasStatus->num_rows > 0;
            if ($hasStatus) {
                $status = 'Pending';
                $stmt2 = $mysqli->prepare("INSERT INTO tour_guides (user_id, first_name, last_name, email, phone_number, status) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt2) {
                    $stmt2->bind_param('isssss', $user_id, $first_name, $last_name, $email, $contact, $status);
                }
            }
            if (!isset($stmt2) || !$stmt2) {
                $stmt2 = $mysqli->prepare("INSERT INTO tour_guides (user_id, first_name, last_name, email, phone_number) VALUES (?, ?, ?, ?, ?)");
                if ($stmt2) {
                    $stmt2->bind_param('issss', $user_id, $first_name, $last_name, $email, $contact);
                }
            }
        } else {
            // Default: Insert into tourists table
            $stmt2 = $mysqli->prepare("INSERT INTO tourists (user_id, first_name, last_name, email, phone_number) VALUES (?, ?, ?, ?, ?)");
            if ($stmt2) {
                $stmt2->bind_param('issss', $user_id, $first_name, $last_name, $email, $contact);
            }
        }

        if (!$stmt2) {
            handleError($mysqli, "Database error. If you added the guide approval feature, run migrate_guide_status.php once.");
        }
        $stmt2->execute();
        
        // 7. Everything worked, save changes to database permanently
        $mysqli->commit();

        // 8. Redirect to the next step: Profile Picture Selection
        echo "<script>
                alert('Registration successful! Let\'s set up your profile picture.');
                window.location.href = 'select_profile_pic.php?user_id=$user_id&role=$role';
              </script>";

    } catch (Exception $e) {
        // If anything fails during the process, undo everything
        $mysqli->rollback();
        handleError($mysqli, "Error during registration: " . $e->getMessage());
    }
}
?>