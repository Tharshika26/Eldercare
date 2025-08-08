<?php
session_start();
require_once 'config.php';

// Use cookie-based authentication with role check
requireAuth('caretaker');

// Get caretaker information from session
$caretaker_username = $_SESSION['username'];
$caretaker_data = $_SESSION['caretaker_data'] ?? null;

// Try to get caretaker ID from session or caretaker data
if (isset($_SESSION['caretaker_id'])) {
    $caretaker_id = $_SESSION['caretaker_id'];
} elseif ($caretaker_data && isset($caretaker_data['id'])) {
    $caretaker_id = $caretaker_data['id'];
} else {
    // Fallback: try to get caretaker by username
    $caretaker_id = $caretaker_username;
}

// Get caretaker details
if (is_numeric($caretaker_id)) {
    // If caretaker_id is numeric, use getCaretakerById
    $caretaker = getCaretakerById($caretaker_id);
} else {
    // If caretaker_id is username, use caretaker_data from session or fetch by username
    $caretaker = $caretaker_data ?: getCaretakerByUsername($caretaker_username);
}

// Get assigned elders
$assigned_elders = getEldersByCaretaker($caretaker_id);

// Handle caretaker profile update
$update_message = '';
$update_error = '';

if (isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // Comprehensive validation
    $validation_errors = [];
    
    // Validate full name
    if (empty($full_name)) {
        $validation_errors[] = 'Full name is required.';
    } elseif (strlen($full_name) < 2) {
        $validation_errors[] = 'Full name must be at least 2 characters long.';
    } elseif (strlen($full_name) > 100) {
        $validation_errors[] = 'Full name must not exceed 100 characters.';
    } elseif (!preg_match('/^[a-zA-Z\s\.\-\']+$/', $full_name)) {
        $validation_errors[] = 'Full name can only contain letters, spaces, dots, hyphens, and apostrophes.';
    }
    
    // Validate age
    if (empty($age)) {
        $validation_errors[] = 'Age is required.';
    } elseif (!is_numeric($age)) {
        $validation_errors[] = 'Age must be a valid number.';
    } elseif ($age < 18 || $age > 100) {
        $validation_errors[] = 'Age must be between 18 and 100 years.';
    }
    
    // Validate gender
    if (empty($gender)) {
        $validation_errors[] = 'Gender is required.';
    } elseif (!in_array($gender, ['Male', 'Female', 'Other'])) {
        $validation_errors[] = 'Please select a valid gender option.';
    }
    
    // Validate phone number
    if (empty($phone_number)) {
        $validation_errors[] = 'Phone number is required.';
    } elseif (!preg_match('/^[0-9]{10}$/', $phone_number)) {
        $validation_errors[] = 'Phone number must contain exactly 10 digits.';
    }
    
    // Validate address (optional but if provided, check length)
    if (!empty($address) && strlen($address) > 500) {
        $validation_errors[] = 'Address must not exceed 500 characters.';
    }
    
    if (!empty($validation_errors)) {
        $update_error = implode(' ', $validation_errors);
    } else {
        try {
            // Update caretaker information in database
            $stmt = $pdo->prepare("UPDATE caretaker SET full_name = ?, age = ?, gender = ?, phone_number = ?, address = ? WHERE username = ?");
            $result = $stmt->execute([$full_name, $age, $gender, $phone_number, $address, $caretaker_username]);
            
            if ($result) {
                $update_message = 'Profile updated successfully!';
                // Refresh caretaker data
                $caretaker = getCaretakerByUsername($caretaker_username);
                // Update session data
                $_SESSION['caretaker_data'] = $caretaker;
            } else {
                $update_error = 'Failed to update profile. Please try again.';
            }
        } catch (PDOException $e) {
            $update_error = 'Database error: ' . $e->getMessage();
        }
    }
}

// System is working correctly - debug logging removed

// Handle logout
if (isset($_GET['logout'])) {
    clearAuthCookies();
    session_destroy();
    header('Location: login.php?message=logged_out');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caretaker Dashboard - Elderly Care Management System</title>
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
        
        .header {
            background-color: #2c3e50;
            color: white;
            padding: 20px 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .logout-btn {
            background-color: #e74c3c;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }
        
        .logout-btn:hover {
            background-color: #c0392b;
        }
        
        .main-content {
            padding: 30px 0;
        }
        
        .caretaker-info {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .caretaker-info h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        
        .info-label {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #7f8c8d;
        }
        
        .elders-table-container {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .elders-table-container h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .elders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .elders-table th {
            background-color: #2c3e50;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #34495e;
        }
        
        .elders-table td {
            padding: 12px 8px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        .elders-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .elders-table tbody tr:hover {
            background-color: #e8f4fd;
        }
        
        .medical-info {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        
        .medical-info h4 {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .emergency-contact {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        
        .emergency-contact h4 {
            color: #155724;
            margin-bottom: 10px;
        }
        
        .special-notes {
            background-color: #e2e3e5;
            border-left: 4px solid #6c757d;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        
        .special-notes h4 {
            color: #495057;
            margin-bottom: 10px;
        }
        
        .no-elders {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            color: #7f8c8d;
        }
        
        .no-elders h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        /* Edit Button and Modal Styles */
        .edit-profile-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 15px;
            transition: background-color 0.3s;
        }
        
        .edit-profile-btn:hover {
            background-color: #2980b9;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            background-color: #f8f9fa;
            padding: 20px 30px;
            border-bottom: 1px solid #e9ecef;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            color: #2c3e50;
            margin: 0;
            font-weight: 600;
        }
        
        .close {
            background: none;
            border: none;
            font-size: 24px;
            color: #6c757d;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .close:hover {
            color: #495057;
            background-color: #e9ecef;
            border-radius: 50%;
        }
        
        .modal-form {
            padding: 30px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            background-color: #fff;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .modal-actions {
            padding: 20px 30px;
            background-color: #f8f9fa;
            border-top: 1px solid #e9ecef;
            border-radius: 0 0 12px 12px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .btn-update {
            background-color: #27ae60;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .btn-update:hover {
            background-color: #219a52;
        }
        
        .btn-cancel {
            background-color: #95a5a6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .btn-cancel:hover {
            background-color: #7f8c8d;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        /* Validation Error Styles */
        .validation-error {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 5px;
            font-weight: 500;
        }
        
        .form-group input.error,
        .form-group select.error,
        .form-group textarea.error {
            border-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }
        
        .form-group input.error:focus,
        .form-group select.error:focus,
        .form-group textarea.error:focus {
            border-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.2);
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <h1>Caretaker Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <a href="?logout=1" class="logout-btn">Logout</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="main-content">
            <!-- Success/Error Messages -->
            <?php if (!empty($update_message)): ?>
                <div class="message success"><?php echo htmlspecialchars($update_message); ?></div>
            <?php endif; ?>
            <?php if (!empty($update_error)): ?>
                <div class="message error"><?php echo htmlspecialchars($update_error); ?></div>
            <?php endif; ?>
            
            <div class="caretaker-info">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="margin: 0;">My Information</h2>
                    <button class="edit-profile-btn" onclick="openEditModal()">Edit Profile</button>
                </div>
                <?php if ($caretaker && is_array($caretaker)): ?>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Name:</div>
                        <div class="info-value"><?php echo htmlspecialchars($caretaker['full_name'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Username:</div>
                        <div class="info-value"><?php echo htmlspecialchars($caretaker['username'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Age:</div>
                        <div class="info-value"><?php echo htmlspecialchars($caretaker['age'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Gender:</div>
                        <div class="info-value"><?php echo htmlspecialchars($caretaker['gender'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Address:</div>
                        <div class="info-value"><?php echo htmlspecialchars($caretaker['address'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Phone:</div>
                        <div class="info-value"><?php echo htmlspecialchars($caretaker['phone_number'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Assigned Elders:</div>
                        <div class="info-value"><?php echo count($assigned_elders); ?>/3</div>
                    </div>
                </div>
                <?php else: ?>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Error:</div>
                        <div class="info-value">Unable to load caretaker information. Please contact administrator.</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Debug Info:</div>
                        <div class="info-value">
                            Username: <?php echo htmlspecialchars($caretaker_username); ?><br>
                            Caretaker ID: <?php echo htmlspecialchars($caretaker_id); ?><br>
                            Data Type: <?php echo gettype($caretaker); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            
            <?php if (empty($assigned_elders)): ?>
                <div class="no-elders">
                    <h3>No Elders Assigned</h3>
                    <p>You currently have no elders assigned to you. Please contact the administrator for assignments.</p>
                </div>
            <?php else: ?>
                <div class="elders-table-container">
                    <h3>My Assigned Elders</h3>
                    <table class="elders-table">
                        <thead>
                            <tr>
                                <th>Elder Name</th>
                                <th>Username</th>
                                <th>Age</th>
                                <th>Gender</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Allocation Date</th>
                                <th>Allocation Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assigned_elders as $elder): ?>
                            <tr>
                                <td>
                                    <?php 
                                    // Always show elder name from allocation table, prefer full_name if available
                                    $display_name = $elder['full_name'] ?? $elder['elder_name'] ?? 'Unknown';
                                    $has_mismatch = empty($elder['full_name']) && !empty($elder['elder_name']);
                                    ?>
                                    <strong><?php echo htmlspecialchars($display_name); ?></strong>
                                    <?php if ($has_mismatch): ?>
                                    <br><small style="color: #856404;">⚠️ Elder details not found - showing allocation name</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($elder['username'] ?? $elder['elder_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($elder['age'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($elder['gender'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($elder['phone_number'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="status-<?php echo strtolower($elder['status'] ?? 'unknown'); ?>">
                                        <?php echo htmlspecialchars($elder['status'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($elder['allocation_date'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="status-<?php echo strtolower($elder['allocation_status'] ?? 'unknown'); ?>">
                                        <?php echo htmlspecialchars($elder['allocation_status'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit My Profile</h2>
                <button class="close" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST" class="modal-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name:</label>
                        <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($caretaker['full_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="age">Age:</label>
                        <input type="number" name="age" id="age" value="<?php echo htmlspecialchars($caretaker['age'] ?? ''); ?>" min="18" max="100" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="gender">Gender:</label>
                        <select name="gender" id="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo ($caretaker['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($caretaker['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($caretaker['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="phone_number">Phone Number:</label>
                        <input type="tel" name="phone_number" id="phone_number" value="<?php echo htmlspecialchars($caretaker['phone_number'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="address">Address:</label>
                        <textarea name="address" id="address" placeholder="Enter your full address"><?php echo htmlspecialchars($caretaker['address'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="update_profile" class="btn-update">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openEditModal() {
            document.getElementById('editProfileModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editProfileModal').style.display = 'none';
            clearValidationErrors();
        }
        
        // Clear all validation error messages
        function clearValidationErrors() {
            const errorElements = document.querySelectorAll('.validation-error');
            errorElements.forEach(element => element.remove());
            
            const inputs = document.querySelectorAll('.form-group input, .form-group select, .form-group textarea');
            inputs.forEach(input => {
                input.classList.remove('error');
            });
        }
        
        // Show validation error for a specific field
        function showFieldError(fieldId, message) {
            const field = document.getElementById(fieldId);
            const formGroup = field.closest('.form-group');
            
            // Remove existing error
            const existingError = formGroup.querySelector('.validation-error');
            if (existingError) {
                existingError.remove();
            }
            
            // Add error class to field
            field.classList.add('error');
            
            // Create and add error message
            const errorElement = document.createElement('div');
            errorElement.className = 'validation-error';
            errorElement.textContent = message;
            formGroup.appendChild(errorElement);
        }
        
        // Validate full name
        function validateFullName(name) {
            if (!name || name.trim().length === 0) {
                return 'Full name is required.';
            }
            if (name.trim().length < 2) {
                return 'Full name must be at least 2 characters long.';
            }
            if (name.trim().length > 100) {
                return 'Full name must not exceed 100 characters.';
            }
            if (!/^[a-zA-Z\s\.\-\']+$/.test(name.trim())) {
                return 'Full name can only contain letters, spaces, dots, hyphens, and apostrophes.';
            }
            return null;
        }
        
        // Validate age
        function validateAge(age) {
            if (!age || age.toString().trim().length === 0) {
                return 'Age is required.';
            }
            const ageNum = parseInt(age);
            if (isNaN(ageNum)) {
                return 'Age must be a valid number.';
            }
            if (ageNum < 18 || ageNum > 100) {
                return 'Age must be between 18 and 100 years.';
            }
            return null;
        }
        
        // Validate gender
        function validateGender(gender) {
            if (!gender || gender.trim().length === 0) {
                return 'Gender is required.';
            }
            if (!['Male', 'Female', 'Other'].includes(gender)) {
                return 'Please select a valid gender option.';
            }
            return null;
        }
        
        // Validate phone number
        function validatePhoneNumber(phone) {
            if (!phone || phone.trim().length === 0) {
                return 'Phone number is required.';
            }
            if (!/^[0-9]{10}$/.test(phone.trim())) {
                return 'Phone number must contain exactly 10 digits.';
            }
            return null;
        }
        
        // Validate address
        function validateAddress(address) {
            if (address && address.trim().length > 500) {
                return 'Address must not exceed 500 characters.';
            }
            return null;
        }
        
        // Real-time validation on input
        function setupRealTimeValidation() {
            const fullNameField = document.getElementById('full_name');
            const ageField = document.getElementById('age');
            const genderField = document.getElementById('gender');
            const phoneField = document.getElementById('phone_number');
            const addressField = document.getElementById('address');
            
            // Full name validation
            fullNameField.addEventListener('blur', function() {
                const error = validateFullName(this.value);
                if (error) {
                    showFieldError('full_name', error);
                } else {
                    this.classList.remove('error');
                    const errorElement = this.closest('.form-group').querySelector('.validation-error');
                    if (errorElement) errorElement.remove();
                }
            });
            
            // Age validation
            ageField.addEventListener('blur', function() {
                const error = validateAge(this.value);
                if (error) {
                    showFieldError('age', error);
                } else {
                    this.classList.remove('error');
                    const errorElement = this.closest('.form-group').querySelector('.validation-error');
                    if (errorElement) errorElement.remove();
                }
            });
            
            // Gender validation
            genderField.addEventListener('change', function() {
                const error = validateGender(this.value);
                if (error) {
                    showFieldError('gender', error);
                } else {
                    this.classList.remove('error');
                    const errorElement = this.closest('.form-group').querySelector('.validation-error');
                    if (errorElement) errorElement.remove();
                }
            });
            
            // Phone validation
            phoneField.addEventListener('blur', function() {
                const error = validatePhoneNumber(this.value);
                if (error) {
                    showFieldError('phone_number', error);
                } else {
                    this.classList.remove('error');
                    const errorElement = this.closest('.form-group').querySelector('.validation-error');
                    if (errorElement) errorElement.remove();
                }
            });
            
            // Address validation
            addressField.addEventListener('blur', function() {
                const error = validateAddress(this.value);
                if (error) {
                    showFieldError('address', error);
                } else {
                    this.classList.remove('error');
                    const errorElement = this.closest('.form-group').querySelector('.validation-error');
                    if (errorElement) errorElement.remove();
                }
            });
        }
        
        // Form submission validation
        function validateForm() {
            clearValidationErrors();
            
            const fullName = document.getElementById('full_name').value;
            const age = document.getElementById('age').value;
            const gender = document.getElementById('gender').value;
            const phone = document.getElementById('phone_number').value;
            const address = document.getElementById('address').value;
            
            let hasErrors = false;
            
            // Validate all fields
            const fullNameError = validateFullName(fullName);
            if (fullNameError) {
                showFieldError('full_name', fullNameError);
                hasErrors = true;
            }
            
            const ageError = validateAge(age);
            if (ageError) {
                showFieldError('age', ageError);
                hasErrors = true;
            }
            
            const genderError = validateGender(gender);
            if (genderError) {
                showFieldError('gender', genderError);
                hasErrors = true;
            }
            
            const phoneError = validatePhoneNumber(phone);
            if (phoneError) {
                showFieldError('phone_number', phoneError);
                hasErrors = true;
            }
            
            const addressError = validateAddress(address);
            if (addressError) {
                showFieldError('address', addressError);
                hasErrors = true;
            }
            
            return !hasErrors;
        }
        
        // Initialize validation when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            setupRealTimeValidation();
            
            // Add form submission handler
            const form = document.querySelector('#editProfileModal form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!validateForm()) {
                        e.preventDefault();
                        return false;
                    }
                });
            }
        });
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('editProfileModal');
            if (event.target === modal) {
                closeEditModal();
            }
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeEditModal();
            }
        });
    </script>
</body>
</html> 