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
        
        .elders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
        }
        
        .elder-card {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .elder-card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 2px solid #e74c3c;
            padding-bottom: 10px;
        }
        
        .elder-info {
            margin-bottom: 15px;
        }
        
        .elder-info label {
            font-weight: bold;
            color: #2c3e50;
            display: block;
            margin-bottom: 5px;
        }
        
        .elder-info p {
            color: #7f8c8d;
            margin-bottom: 10px;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 3px;
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
            <div class="caretaker-info">
                <h2>My Information</h2>
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
                        <div class="info-value"><?php echo count($assigned_elders); ?> elders</div>
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
                <div class="elders-grid">
                    <?php foreach ($assigned_elders as $elder): ?>
                        <div class="elder-card">
                            <h3><?php echo htmlspecialchars($elder['full_name'] ?? $elder['elder_name'] ?? 'Unknown Elder'); ?></h3>
                            
                            <?php if (empty($elder['full_name']) && !empty($elder['elder_name'] ?? '')): ?>
                            <div class="elder-info" style="background-color: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0;">
                                <label style="color: #856404;">⚠️ Note:</label>
                                <p style="color: #856404; margin: 5px 0 0 0;">Elder "<?php echo htmlspecialchars($elder['elder_name']); ?>" is allocated to you, but detailed elder information is not available in the system. This may be because:</p>
                                <ul style="color: #856404; margin: 5px 0 0 20px;">
                                    <li>The elder record hasn't been created yet</li>
                                    <li>There's a mismatch between allocation and elder records</li>
                                </ul>
                            </div>
                            <?php endif; ?>
                            
                            <div class="elder-info">
                                <label>Elder Name (from allocation):</label>
                                <p><?php echo htmlspecialchars($elder['elder_name'] ?? 'N/A'); ?></p>
                            </div>
                            
                            <?php if (!empty($elder['username'] ?? '')): ?>
                            <div class="elder-info">
                                <label>Username:</label>
                                <p><?php echo htmlspecialchars($elder['username']); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($elder['age'] ?? '')): ?>
                            <div class="elder-info">
                                <label>Age:</label>
                                <p><?php echo htmlspecialchars($elder['age']); ?> years old</p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($elder['gender'] ?? '')): ?>
                            <div class="elder-info">
                                <label>Gender:</label>
                                <p><?php echo htmlspecialchars($elder['gender']); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($elder['address'] ?? '')): ?>
                            <div class="elder-info">
                                <label>Address:</label>
                                <p><?php echo htmlspecialchars($elder['address']); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($elder['phone_number'] ?? '')): ?>
                            <div class="elder-info">
                                <label>Phone Number:</label>
                                <p><?php echo htmlspecialchars($elder['phone_number']); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($elder['status'] ?? '')): ?>
                            <div class="elder-info">
                                <label>Elder Status:</label>
                                <p>
                                    <span class="status-<?php echo strtolower($elder['status']); ?>">
                                        <?php echo htmlspecialchars($elder['status']); ?>
                                    </span>
                                </p>
                            </div>
                            <?php endif; ?>
                            
                            <div class="medical-info" style="background-color: #e8f5e8; border-left: 4px solid #28a745;">
                                <h4 style="color: #155724;">📋 Your Allocation Details</h4>
                                <div class="elder-info">
                                    <label>Allocation ID:</label>
                                    <p><strong>#<?php echo htmlspecialchars($elder['allocation_id'] ?? 'N/A'); ?></strong></p>
                                </div>
                                <div class="elder-info">
                                    <label>Elder Name:</label>
                                    <p><strong><?php echo htmlspecialchars($elder['elder_name'] ?? 'N/A'); ?></strong></p>
                                </div>
                                <div class="elder-info">
                                    <label>Allocation Status:</label>
                                    <p>
                                        <span class="status-<?php echo strtolower($elder['allocation_status'] ?? 'unknown'); ?>">
                                            <?php echo htmlspecialchars($elder['allocation_status'] ?? 'N/A'); ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="elder-info">
                                    <label>Allocated On:</label>
                                    <p><strong><?php echo htmlspecialchars($elder['allocation_date'] ?? 'N/A'); ?></strong> at <strong><?php echo htmlspecialchars($elder['allocation_time'] ?? 'N/A'); ?></strong></p>
                                </div>
                                <div class="elder-info">
                                    <label>Assigned Caretaker:</label>
                                    <p><?php echo htmlspecialchars($elder['assigned_caretaker'] ?? 'N/A'); ?> (You)</p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 