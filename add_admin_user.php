<?php
require_once 'dbconnect.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // Validation
    if (empty($username) || strlen($username) < 3) {
        $error = 'Username must be at least 3 characters';
    } elseif (empty($password) || strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if username already exists
        $check = $mysqli->query("SELECT id FROM users WHERE username = '" . $mysqli->real_escape_string($username) . "'");
        if ($check && $check->num_rows > 0) {
            $error = 'Username already exists';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert new admin user
            $stmt = $mysqli->prepare("INSERT INTO users (username, password, role, status, created_at) VALUES (?, ?, 'admin', 'Active', NOW())");
            $stmt->bind_param('ss', $username, $hashed_password);
            
            if ($stmt->execute()) {
                $new_user_id = $mysqli->insert_id;
                $message = "✓ Admin user '<strong>$username</strong>' created successfully! (User ID: $new_user_id)";
                // Clear form
                $username = '';
                $password = '';
                $confirm_password = '';
            } else {
                $error = 'Error creating user: ' . $mysqli->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Admin User</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        input:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.3);
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background-color: #45a049;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background-color: #e7f3ff;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            font-size: 13px;
            color: #004085;
            border: 1px solid #b8daff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>➕ Add New Admin User</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit">Create Admin User</button>
        </form>
        
        <div class="info">
            <strong>ℹ️ Info:</strong><br>
            • Username: minimum 3 characters<br>
            • Password: minimum 6 characters<br>
            • Role: automatically set to "admin"<br>
            • Status: automatically set to "Active"
        </div>
    </div>
</body>
</html>
