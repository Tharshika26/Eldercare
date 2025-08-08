<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $name = trim($_POST['name']);
    $age = trim($_POST['age']);
    $address = trim($_POST['address']);
    $phoneno = trim($_POST['phoneno']);
    $gender = $_POST['gender'];
    
    // Validation
    if (empty($username) || empty($password) || empty($name) || empty($age) || empty($address) || empty($phoneno) || empty($gender)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!is_numeric($age) || $age < 1 || $age > 120) {
        $error = 'Please enter a valid age between 1 and 120.';
    } elseif (!preg_match('/^[0-9]{10}$/', $phoneno)) {
        $error = 'Phone number must contain exactly 10 digits.';
    } else {
        // Check if username already exists in caretaker table
        if (isset($db_connected) && $db_connected) {
            if (checkCaretakerExists($username)) {
                $error = 'Username already exists. Please choose a different one.';
            } else {
                // Save caretaker data to database
                if (createCaretaker($username, $password, $name, $age, $address, $phoneno, $gender)) {
                    // Redirect to login page with success message
                    header('Location: login.php?message=registration_success&username=' . urlencode($username));
                    exit();
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        } else {
            $error = 'Database connection failed. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Elderly Care Management System</title>
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
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
        }
        
        .register-form {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .register-form h2 {
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
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color:#2c3e50;
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
            background-color:#2c3e50;
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
            background-color:#2c3e50;
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
            color: #2c3e50;
            text-decoration: none;
        }
        
        .back-link a:hover {
            text-decoration: underline;
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
        <div class="register-form">
            <h2>Register</h2>
            
            <?php if (isset($db_connected) && !$db_connected): ?>
                <div class="db-status">
                    ⚠️ Database connection failed. Using demo mode with hard-coded data.
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="name">Full Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="age">Age:</label>
                    <input type="number" id="age" name="age" min="1" max="120" value="<?php echo isset($_POST['age']) ? htmlspecialchars($_POST['age']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="gender">Gender:</label>
                    <select id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="address">Address:</label>
                    <textarea id="address" name="address" required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="phoneno">Phone Number:</label>
                    <input type="tel" id="phoneno" name="phoneno" value="<?php echo isset($_POST['phoneno']) ? htmlspecialchars($_POST['phoneno']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn">Register</button>
            </form>
            
            
            
            <div class="back-link">
                <a href="index.html">← Back to Home</a> | 
                <a href="login.php">Already have an account? Login</a>
            </div>
        </div>
    </div>
</body>
</html> 