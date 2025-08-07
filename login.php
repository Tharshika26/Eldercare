<?php
session_start();
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Try database authentication first
        if (isset($db_connected) && $db_connected) {
            // Debug: Show what we're trying to authenticate
            error_log("Attempting login for username: " . $username);
            
            // First, try admin authentication
            $admin = authenticateAdmin($username, $password);
            if ($admin) {
                $_SESSION['user_id'] = 'admin';
                $_SESSION['username'] = $admin['username'];
                $_SESSION['role'] = 'admin';
                $_SESSION['name'] = 'Administrator';
                
                // Set secure cookies for persistent authentication
                $cookie_options = [
                    'expires' => time() + (30 * 24 * 60 * 60), // 30 days
                    'path' => '/',
                    'secure' => false, // Set to true in production with HTTPS
                    'httponly' => true,
                    'samesite' => 'Strict'
                ];
                
                setcookie('eldercare_user_id', 'admin', $cookie_options);
                setcookie('eldercare_username', $admin['username'], $cookie_options);
                setcookie('eldercare_role', 'admin', $cookie_options);
                setcookie('eldercare_auth_token', hash('sha256', 'admin_' . $admin['username'] . '_' . time()), $cookie_options);
                
                header('Location: admin_dashboard.php');
                exit();
            }
            
            // If not admin, try caretaker authentication
            $caretaker = authenticateCaretaker($username, $password);
            if ($caretaker) {
                $_SESSION['user_id'] = $caretaker['username'];
                $_SESSION['username'] = $caretaker['username'];
                $_SESSION['role'] = 'caretaker';
                $_SESSION['name'] = $caretaker['full_name'];
                $_SESSION['caretaker_id'] = $caretaker['id'] ?? $caretaker['username']; // Use id if available, fallback to username
                $_SESSION['caretaker_data'] = $caretaker;
                
                // Set secure cookies for persistent authentication
                $cookie_options = [
                    'expires' => time() + (30 * 24 * 60 * 60), // 30 days
                    'path' => '/',
                    'secure' => false, // Set to true in production with HTTPS
                    'httponly' => true,
                    'samesite' => 'Strict'
                ];
                
                $caretaker_id = $caretaker['id'] ?? $caretaker['username'];
                setcookie('eldercare_user_id', $caretaker_id, $cookie_options);
                setcookie('eldercare_username', $caretaker['username'], $cookie_options);
                setcookie('eldercare_role', 'caretaker', $cookie_options);
                setcookie('eldercare_caretaker_id', $caretaker_id, $cookie_options);
                setcookie('eldercare_auth_token', hash('sha256', 'caretaker_' . $caretaker['username'] . '_' . time()), $cookie_options);
                
                header('Location: caretaker_dashboard.php');
                exit();
            }
            
            // Debug: Log authentication failure
            error_log("Authentication failed for username: " . $username);
            $error = 'Invalid username or password. Please check your credentials and try again.';
        } else {
            $error = 'Database connection failed. Please check your database connection.';
        }
    }
}

// Handle messages from URL parameters
$message = '';
$messageType = 'error';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'logged_out':
            $message = 'You have been successfully logged out.';
            $messageType = 'success';
            break;
        case 'unauthorized':
            $message = 'You are not authorized to access that page.';
            $messageType = 'error';
            break;
    }
}
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'unauthorized':
            $message = 'You are not authorized to access that page.';
            $messageType = 'error';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Elderly Care Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            line-height: 1.6;
        }
        
        .container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
        }
        
        .login-form {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .login-form h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #2c3e50;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background-color:#2c3e50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color:rgb(4, 53, 86);
        }
        
        .error {
            background-color: #e74c3c;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success {
            background-color: #27ae60;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color:#2c3e50;
            text-decoration: none;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .demo-credentials {
            background-color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .demo-credentials h4 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .demo-credentials ul {
            list-style: none;
            padding-left: 0;
        }
        
        .demo-credentials li {
            margin-bottom: 5px;
        }
        
        .db-status {
            background-color: #f39c12;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-form">
            <h2>Login</h2>
            
            <?php if (isset($db_connected) && !$db_connected): ?>
                <div class="db-status">
                    ⚠️ Database connection failed. Using demo mode with hard-coded data.
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($message)): ?>
                <div class="<?php echo $messageType === 'success' ? 'success' : 'error'; ?>"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn">Login</button>
            </form>
            
            
                  
                <p style="margin-top: 10px; font-size: 12px; color: #7f8c8d;">
                    Don't have a caretaker account? <a href="register.php" style="color:#2c3e50">Register here</a>
                </p>
            
            
            <div class="back-link">
                <a href="index.html">← Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html> 