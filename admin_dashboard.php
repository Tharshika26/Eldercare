<?php
session_start();
require_once 'config.php';

// Use cookie-based authentication with role check
requireAuth('admin');

// Handle logout
if (isset($_GET['logout'])) {
    clearAuthCookies();
    session_destroy();
    header('Location: login.php?message=logged_out');
    exit();
}

$message = '';

// Handle caretaker allocation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['allocate'])) {
    $elder_id = $_POST['elder_id'];
    $caretaker_id = $_POST['caretaker_id'];
    $allocation_date = $_POST['allocation_date'];
    $allocation_time = $_POST['allocation_time'];
    
    // Try to save allocation in database
    if (isset($db_connected) && $db_connected) {
        // Get elder and caretaker names for the allocation record
        $elder = getElderByUsername($elder_id);
        $caretaker = getCaretakerByUsername($caretaker_id);
        
        if ($elder && $caretaker) {
            if (saveAllocation($caretaker['full_name'], $elder['full_name'], $elder['status'], $allocation_date, $allocation_time)) {
                $message = 'Caretaker allocated successfully on ' . date('M d, Y', strtotime($allocation_date)) . ' at ' . date('g:i A', strtotime($allocation_time)) . '!';
            } else {
                $message = 'Failed to allocate caretaker. Please try again.';
            }
        } else {
            $message = 'Failed to find elder or caretaker details.';
        }
    } else {
        $message = 'Database connection failed. Cannot save allocation.';
    }
}

// Handle allocation edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_allocation'])) {
    $allocation_id = $_POST['allocation_id'];
    $caretaker_name = $_POST['edit_caretaker_name'];
    $elder_name = $_POST['edit_elder_name'];
    $elder_status = $_POST['edit_elder_status'];
    $allocation_date = $_POST['edit_allocation_date'];
    $allocation_time = $_POST['edit_allocation_time'];
    
    if (updateAllocation($allocation_id, $caretaker_name, $elder_name, $elder_status, $allocation_date, $allocation_time)) {
        $message = 'Allocation updated successfully!';
    } else {
        $message = 'Failed to update allocation. Please try again.';
    }
}

// Handle allocation delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_allocation'])) {
    $allocation_id = $_POST['allocation_id'];
    
    if (deleteAllocation($allocation_id)) {
        $message = 'Allocation deleted successfully!';
    } else {
        $message = 'Failed to delete allocation. Please try again.';
    }
}

// Handle elder management operations
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_elder'])) {
    $username = $_POST['username'];
    $full_name = $_POST['full_name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $address = $_POST['address'];
    $phone_number = $_POST['phone_number'];
    $status = $_POST['status'];
    
    if (addElder($username, $full_name, $age, $gender, $address, $phone_number, $status)) {
        $message = 'Elder added successfully!';
        // Refresh elders data
        $elders = getEldersFromDB();
    } else {
        $message = 'Failed to add elder. Please try again.';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_elder'])) {
    $elder_id = $_POST['elder_id'];
    $username = $_POST['edit_username'];
    $full_name = $_POST['edit_full_name'];
    $age = $_POST['edit_age'];
    $gender = $_POST['edit_gender'];
    $address = $_POST['edit_address'];
    $phone_number = $_POST['edit_phone_number'];
    $status = $_POST['edit_status'];
    
    if (updateElder($elder_id, $username, $full_name, $age, $gender, $address, $phone_number, $status)) {
        $message = 'Elder updated successfully!';
        // Refresh elders data
        $elders = getEldersFromDB();
    } else {
        $message = 'Failed to update elder. Please try again.';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_elder'])) {
    $elder_id = $_POST['elder_id'];
    
    if (deleteElder($elder_id)) {
        $message = 'Elder deleted successfully!';
        // Refresh elders data
        $elders = getEldersFromDB();
    } else {
        $message = 'Failed to delete elder. Please try again.';
    }
}

// Handle caretaker management operations
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_caretaker'])) {
    $username = $_POST['username'];
    $full_name = $_POST['full_name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $address = $_POST['address'];
    $phone_number = $_POST['phone_number'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    if (addCaretaker($username, $full_name, $age, $gender, $address, $phone_number, $password)) {
        $message = 'Caretaker added successfully!';
        // Refresh caretakers data
        $caretakers = getCaretakersFromDB();
    } else {
        $message = 'Failed to add caretaker. Please try again.';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_caretaker'])) {
    $caretaker_id = $_POST['caretaker_id'];
    $username = $_POST['edit_username'];
    $full_name = $_POST['edit_full_name'];
    $age = $_POST['edit_age'];
    $gender = $_POST['edit_gender'];
    $address = $_POST['edit_address'];
    $phone_number = $_POST['edit_phone_number'];
    
    // Handle password update (optional)
    $password = null;
    if (!empty($_POST['edit_password'])) {
        $password = password_hash($_POST['edit_password'], PASSWORD_DEFAULT);
    }
    
    if (updateCaretaker($caretaker_id, $username, $full_name, $age, $gender, $address, $phone_number, $password)) {
        $message = 'Caretaker updated successfully!';
        // Refresh caretakers data
        $caretakers = getCaretakersFromDB();
    } else {
        $message = 'Failed to update caretaker. Please try again.';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_caretaker'])) {
    $caretaker_id = $_POST['caretaker_id'];
    
    if (deleteCaretaker($caretaker_id)) {
        $message = 'Caretaker deleted successfully!';
        // Refresh caretakers data
        $caretakers = getCaretakersFromDB();
    } else {
        $message = 'Failed to delete caretaker. Please try again.';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.html');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Elderly Care Management System</title>
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
        
        .main-container {
            display: flex;
            min-height: calc(100vh - 80px);
        }
        
        .sidebar {
            width: 250px;
            background-color: #34495e;
            padding: 20px 0;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        
        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar li {
            margin-bottom: 5px;
        }
        
        .sidebar a {
            display: block;
            color: #ecf0f1;
            text-decoration: none;
            padding: 15px 20px;
            transition: background-color 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar a:hover, .sidebar a.active {
            background-color: #2c3e50;
            border-left-color: #3498db;
        }
        
        .sidebar a i {
            margin-right: 10px;
            width: 20px;
        }
        
        .main-content {
            flex: 1;
            padding: 30px;
            background-color: #f5f5f5;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .content-section {
            display: none;
        }
        
        .content-section.active {
            display: block;
        }
        
        .stats-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stats-card h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .stats-number {
            font-size: 2em;
            font-weight: bold;
            color: #3498db;
        }
        
        .section {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .elders-list, .caretakers-list {
            display: grid;
            gap: 15px;
        }
        
        .elder-item, .caretaker-item {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }
        
        .elder-item h4, .caretaker-item h4 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .elder-item p, .caretaker-item p {
            color: #666;
            font-size: 14px;
            margin-bottom: 3px;
        }
        
        .assigned-elders {
            margin-top: 10px;
            padding: 10px;
            background-color: #e8f4fd;
            border-radius: 4px;
            border-left: 3px solid #3498db;
        }
        
        .assigned-elders ul {
            margin: 5px 0 0 20px;
            padding: 0;
        }
        
        .assigned-elders li {
            color: #2c3e50;
            font-size: 13px;
            margin-bottom: 2px;
        }
        
        .allocation-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: bold;
        }
        
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn {
            background-color: #27ae60;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn:hover {
            background-color: #229954;
        }
        
        .message {
            background-color: #27ae60;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
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
        
        /* Action buttons */
        .actions {
            display: flex;
            gap: 5px;
            justify-content: center;
        }
        
        .btn-edit, .btn-delete {
            padding: 5px 8px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
        }
        
        .btn-edit {
            background-color: #3498db;
            color: white;
        }
        
        .btn-edit:hover {
            background-color: #2980b9;
        }
        
        .btn-delete {
            background-color: #e74c3c;
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #c0392b;
        }
        
        /* Status badges */
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        /* Modal styles */
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
            padding:0;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .modal-header {
            background-color: #2c3e50;
            color: white;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 18px;
        }
        
        .close {
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            color: white;
        }
        
        .close:hover {
            opacity: 0.7;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal form {
            padding: 20px;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .btn-cancel {
            background-color: #95a5a6;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-cancel:hover {
            background-color: #7f8c8d;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .empty-state p {
            margin: 10px 0;
        }
        
        .elders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .elders-table th,
        .elders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .elders-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .elders-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-edit {
            background-color: #3498db;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: background-color 0.3s;
        }
        
        .btn-edit:hover {
            background-color: #2980b9;
        }
        
        .btn-delete {
            background-color: #e74c3c;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: background-color 0.3s;
        }
        
        .btn-delete:hover {
            background-color: #c0392b;
        }
        
        /* Colored Stats Cards */
        .stats-card.blue {
            background-color: #3498db;
            color: white;
        }
        
        .stats-card.green {
            background-color: #27ae60;
            color: white;
        }
        
        .stats-card.orange {
            background-color: #f39c12;
            color: white;
        }
        
        .stats-card.red {
            background-color: #e74c3c;
            color: white;
        }
        
        /* Black numbers for colored stats cards */
        .stats-card.blue .stats-number,
        .stats-card.green .stats-number,
        .stats-card.orange .stats-number,
        .stats-card.red .stats-number {
            color: white;
        }
        
        /* Single Row Stats Grid for Allocation Management */
        .allocation-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        
        /* Responsive design for smaller screens */
        @media (max-width: 1200px) {
            .allocation-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .allocation-stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Modern Modal Styles */
        .modern-modal {
            max-width: 600px;
            width: 90%;
            padding-top:-100px;
            margin-top:0;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .modern-modal .modal-header {
            background-color: #f8f9fa;
            padding: 20px 30px;
            border-bottom: 1px solid #e9ecef;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modern-modal .close {
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
        
        .modern-modal .close:hover {
            color: #495057;
            background-color: #e9ecef;
            border-radius: 50%;
        }
        
        .modal-form-grid {
            padding: 30px;
            display: grid;
            gap: 20px;
        }
        
        .form-field {
            display: flex;
            flex-direction: column;
        }
        
        .form-field label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-field input {
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            background-color: #fff;
        }
        
        .form-field input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .modern-modal .modal-actions {
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
            display: inline-block;
        }
        
        .btn-update:hover {
            background-color: #219a52;
        }
        
        .modern-modal .btn-cancel {
            background-color: #95a5a6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: inline-block;
        }
        
        .modern-modal .btn-cancel:hover {
            background-color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <h1>Admin Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <a href="?logout=1" class="logout-btn">Logout</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="main-container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <ul>
                <li><a href="#" onclick="showSection('dashboard')" class="active" id="dashboard-link">Dashboard</a></li>
                <li><a href="#" onclick="showSection('elders')" id="elders-link">All Elders</a></li>
                <li><a href="manageelders.php" id="manage-elders-link">Manage Elders</a></li>
                <li><a href="#" onclick="showSection('caretakers')" id="caretakers-link">All Caretakers</a></li>
                <li><a href="managecaretaker.php" id="manage-caretakers-link">Manage Caretakers</a></li>
                <li><a href="#" onclick="showSection('allocate')" id="allocate-link">Allocate Caretaker to Elders</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <?php if (isset($db_connected) && !$db_connected): ?>
                <div class="db-status">
                    ⚠️ Database connection failed. Using demo mode with hard-coded data.
                </div>
            <?php endif; ?>
            
            <?php if (!empty($message)): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <!-- Dashboard Section -->
            <div id="dashboard-section" class="content-section active">
                <h2>Dashboard Overview</h2>
                <div class="dashboard-grid">
                    <div class="stats-card">
                        <h3>Total Elders</h3>
                        <div class="stats-number"><?php echo count($elders); ?></div>
                    </div>
                    <div class="stats-card">
                        <h3>Total Caretakers</h3>
                        <div class="stats-number"><?php echo count($caretakers); ?></div>
                    </div>
                    <div class="stats-card">
                        <h3>Unassigned Elders</h3>
                        <div class="stats-number"><?php echo count(getUnassignedElders()); ?></div>
                    </div>
                    <div class="stats-card">
                        <h3>Available Caretakers</h3>
                        <div class="stats-number"><?php echo count(getAvailableCaretakers()); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Elders Section -->
            <div id="elders-section" class="content-section">
                <div class="section">
                    <h2>All Elders</h2>
                    <div class="elders-list">
                        <?php foreach ($elders as $elder): ?>
                            <div class="elder-item">
                                <h4><?php echo htmlspecialchars($elder['full_name'] ?? $elder['name'] ?? 'Unknown'); ?></h4>
                                <p><strong>Age:</strong> <?php echo htmlspecialchars($elder['age']); ?> | <strong>Gender:</strong> <?php echo htmlspecialchars($elder['gender']); ?></p>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($elder['address']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($elder['phone_number']); ?></p>
                                <p><strong>Status:</strong> <?php echo htmlspecialchars($elder['status']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Caretakers Section -->
            <div id="caretakers-section" class="content-section">
                <div class="section">
                    <h2>All Caretakers</h2>
                    <div class="caretakers-list">
                        <?php foreach ($caretakers as $caretaker): ?>
                            <div class="caretaker-item">
                                <h4><?php echo htmlspecialchars($caretaker['full_name'] ?? $caretaker['name'] ?? 'Unknown'); ?></h4>
                                <p><strong>Age:</strong> <?php echo htmlspecialchars($caretaker['age']); ?> | <strong>Gender:</strong> <?php echo htmlspecialchars($caretaker['gender']); ?></p>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($caretaker['address']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($caretaker['phone_number']); ?></p>
                                <p><strong>Username:</strong> <?php echo htmlspecialchars($caretaker['username']); ?></p>
                                <?php if (isset($caretaker['id'])): ?>
                                    <?php 
                                    $assignedElders = getEldersByCaretaker($caretaker['id']);
                                    $elderCount = count($assignedElders);
                                    ?>
                                    <p><strong>Assigned Elders:</strong> <?php echo $elderCount; ?>/3</p>

                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Allocation Section -->
            <div id="allocate-section" class="content-section">
                <div class="section">
                    <h2>Allocate Caretaker to Elders</h2>
                    <div class="allocation-form">
                        <form method="POST">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="elder_id">Select Elder:</label>
                                    <select name="elder_id" id="elder_id" required>
                                        <option value="">Choose an elder...</option>
                                        <?php foreach ($elders as $elder): ?>
                                            <option value="<?php echo $elder['username']; ?>">
                                                <?php echo htmlspecialchars($elder['full_name'] ?? $elder['name'] ?? 'Unknown'); ?> (Age: <?php echo htmlspecialchars($elder['age']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="caretaker_id">Select Caretaker:</label>
                                    <select name="caretaker_id" id="caretaker_id" required>
                                        <option value="">Choose a caretaker...</option>
                                        <?php foreach ($caretakers as $caretaker): ?>
                                            <option value="<?php echo $caretaker['username']; ?>">
                                                <?php echo htmlspecialchars($caretaker['full_name'] ?? $caretaker['name'] ?? 'Unknown'); ?> (<?php echo htmlspecialchars($caretaker['gender']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="allocation_date">Allocation Date:</label>
                                    <input type="date" name="allocation_date" id="allocation_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="allocation_time">Allocation Time:</label>
                                    <input type="time" name="allocation_time" id="allocation_time" value="<?php echo date('H:i'); ?>" required>
                                </div>
                                <div class="form-group">
                                    <button type="submit" name="allocate" class="btn">Allocate</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Allocation Management Table -->
                    <div class="section" style="margin-top: 30px;">
                        <h2>Allocation Management</h2>
                        <div class="allocation-stats-grid" style="margin: 20px 0;">
                            <?php 
                            $allocations = getAllocations();
                            $totalAllocations = count($allocations);
                            $todayAllocations = count(array_filter($allocations, function($a) { return $a['date'] == date('Y-m-d'); }));
                            $thisWeekAllocations = count(array_filter($allocations, function($a) { 
                                return strtotime($a['date']) >= strtotime('-7 days'); 
                            }));
                            $activeAllocations = count(array_filter($allocations, function($a) { 
                                return strtolower($a['elder_status']) == 'active'; 
                            }));
                            ?>
                            <div class="stats-card blue">
                                <h3 style="color: white ">Total Allocations</h3>
                                <div class="stats-number"><?php echo $totalAllocations; ?></div>
                            </div>
                            <div class="stats-card green">
                                <h3 style="color: white ">Today's Allocations</h3>
                                <div class="stats-number"><?php echo $todayAllocations; ?></div>
                            </div>
                            <div class="stats-card blue">
                                <h3 style="color: white ">This Week</h3>
                                <div class="stats-number"><?php echo $thisWeekAllocations; ?></div>
                            </div>
                            <div class="stats-card orange">
                                <h3 style="color: white ">Active Allocations</h3>
                                <div class="stats-number"><?php echo $activeAllocations; ?></div>
                            </div>
                        </div>
                        
                        <?php if (!empty($allocations)): ?>
                            <div class="table-container" style="overflow-x: auto;">
                                <table class="elders-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Caretaker</th>
                                            <th>Elder</th>
                                            <th>Elder Status</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allocations as $allocation): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($allocation['id']); ?></td>
                                                <td><?php echo htmlspecialchars($allocation['caretaker_name']); ?></td>
                                                <td><?php echo htmlspecialchars($allocation['elder_name']); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo strtolower($allocation['elder_status']); ?>">
                                                        <?php echo htmlspecialchars($allocation['elder_status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($allocation['date'])); ?></td>
                                                <td><?php echo date('g:i A', strtotime($allocation['time'])); ?></td>
                                                <td class="actions">
                                                    <button class="btn-edit" onclick="editAllocation(<?php echo $allocation['id']; ?>)" title="Edit Allocation">Edit</button>
                                                
                                                    <button class="btn-delete" onclick="deleteAllocation(<?php echo $allocation['id']; ?>)" title="Delete Allocation">Delete</button>
                                                    
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No allocations recorded yet.</p>
                                <p>Use the form above to create your first allocation.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Allocation Modal -->
    <div id="editAllocationModal" class="modal">
        <div class="modal-content modern-modal">
            <div class="modal-header">
                <h2 style="color: #2c3e50; margin: 0; font-weight: 600;">Edit Allocation</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" id="editAllocationForm">
                <input type="hidden" name="allocation_id" id="edit_allocation_id">
                
                <div class="modal-form-grid">
                    <div class="form-field">
                        <label for="edit_caretaker_name">Caretaker Name:</label>
                        <input type="text" name="edit_caretaker_name" id="edit_caretaker_name" required>
                    </div>
                    
                    <div class="form-field">
                        <label for="edit_elder_name">Elder Name:</label>
                        <input type="text" name="edit_elder_name" id="edit_elder_name" required>
                    </div>
                    
                    <div class="form-field">
                        <label for="edit_elder_status">Elder Status:</label>
                        <input type="text" name="edit_elder_status" id="edit_elder_status" required>
                    </div>
                    
                    <div class="form-field">
                        <label for="edit_allocation_date">Allocation Date:</label>
                        <input type="date" name="edit_allocation_date" id="edit_allocation_date" required>
                    </div>
                    
                    <div class="form-field">
                        <label for="edit_allocation_time">Allocation Time:</label>
                        <input type="time" name="edit_allocation_time" id="edit_allocation_time" required>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="edit_allocation" class="btn-update">Update Allocation</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Allocation Modal -->
    <div id="deleteAllocationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Allocation</h3>
                <span class="close" onclick="closeDeleteModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this allocation?</p>
                <p><strong>This action cannot be undone.</strong></p>
            </div>
            <form method="POST" id="deleteAllocationForm">
                <input type="hidden" name="allocation_id" id="delete_allocation_id">
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" name="delete_allocation" class="btn-delete">Delete Allocation</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showSection(sectionName) {
            // Hide all sections
            const sections = document.querySelectorAll('.content-section');
            sections.forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active class from all sidebar links
            const links = document.querySelectorAll('.sidebar a');
            links.forEach(link => {
                link.classList.remove('active');
            });
            
            // Show selected section
            const targetSection = document.getElementById(sectionName + '-section');
            if (targetSection) {
                targetSection.classList.add('active');
            }
            
            // Add active class to clicked link
            const targetLink = document.getElementById(sectionName + '-link');
            if (targetLink) {
                targetLink.classList.add('active');
            }
        }
        
        // Allocation management functions
        const allocations = <?php echo json_encode($allocations); ?>;
        
        function editAllocation(id) {
            const allocation = allocations.find(a => a.id == id);
            if (allocation) {
                document.getElementById('edit_allocation_id').value = allocation.id;
                document.getElementById('edit_caretaker_name').value = allocation.caretaker_name;
                document.getElementById('edit_elder_name').value = allocation.elder_name;
                document.getElementById('edit_elder_status').value = allocation.elder_status;
                document.getElementById('edit_allocation_date').value = allocation.date;
                document.getElementById('edit_allocation_time').value = allocation.time;
                document.getElementById('editAllocationModal').style.display = 'block';
            }
        }
        
        function deleteAllocation(id) {
            document.getElementById('delete_allocation_id').value = id;
            document.getElementById('deleteAllocationModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editAllocationModal').style.display = 'none';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteAllocationModal').style.display = 'none';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editAllocationModal');
            const deleteModal = document.getElementById('deleteAllocationModal');
            if (event.target == editModal) {
                editModal.style.display = 'none';
            }
            if (event.target == deleteModal) {
                deleteModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>