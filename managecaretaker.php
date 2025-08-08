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
$messageType = '';

// Handle caretaker operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_caretaker'])) {
        // Add new caretaker
        $username = trim($_POST['username']);
        $full_name = trim($_POST['full_name']);
        $age = intval($_POST['age']);
        $gender = $_POST['gender'];
        $address = trim($_POST['address']);
        $phone_number = trim($_POST['phone_number']);
        $password = $_POST['password'];
        
        if (addCaretakerLocal($username, $full_name, $age, $gender, $address, $phone_number, $password)) {
            $message = 'Caretaker added successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to add caretaker. Username might already exist.';
            $messageType = 'error';
        }
    } elseif (isset($_POST['update_caretaker'])) {
        // Update caretaker
        $id = intval($_POST['id']);
        $username = $_POST['username'];
        $full_name = trim($_POST['full_name']);
        $age = intval($_POST['age']);
        $gender = $_POST['gender'];
        $address = trim($_POST['address']);
        $phone_number = trim($_POST['phone_number']);
        $password = !empty($_POST['password']) ? $_POST['password'] : null;
        
        if (updateCaretakerLocal($id, $username, $full_name, $age, $gender, $address, $phone_number, $password)) {
            $message = 'Caretaker updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update caretaker.';
            $messageType = 'error';
        }
    } elseif (isset($_POST['delete_caretaker'])) {
        // Delete caretaker
        $id = intval($_POST['id']);
        
        if (deleteCaretakerLocal($id)) {
            $message = 'Caretaker deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to delete caretaker.';
            $messageType = 'error';
        }
    }
}

// Get all caretakers from database
$caretakers = getCaretakersFromDB();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.html');
    exit();
}

// Helper functions for caretaker management
function addCaretakerLocal($username, $full_name, $age, $gender, $address, $phone_number, $password) {
    global $pdo, $db_connected;
    if (!$db_connected) {
        error_log("Database not connected in addCaretakerLocal");
        return false;
    }
    
    try {
        // Check if username already exists
        $checkStmt = $pdo->prepare("SELECT id FROM caretaker WHERE username = ?");
        $checkStmt->execute([$username]);
        if ($checkStmt->fetch()) {
            error_log("Username already exists: " . $username);
            return false;
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO caretaker (username, full_name, age, gender, address, phone_number, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([$username, $full_name, $age, $gender, $address, $phone_number, $hashedPassword]);
        error_log("Add caretaker result: " . ($result ? 'success' : 'failed'));
        return $result;
    } catch(PDOException $e) {
        error_log("Error adding caretaker: " . $e->getMessage());
        return false;
    }
}

function updateCaretakerLocal($id, $username, $full_name, $age, $gender, $address, $phone_number, $password = null) {
    global $pdo, $db_connected;
    if (!$db_connected) {
        error_log("Database not connected in updateCaretakerLocal");
        return false;
    }
    
    try {
        // Check if username exists for other records
        $checkStmt = $pdo->prepare("SELECT id FROM caretaker WHERE username = ? AND id != ?");
        $checkStmt->execute([$username, $id]);
        if ($checkStmt->fetch()) {
            error_log("Username already exists for another caretaker: " . $username);
            return false;
        }
        
        if ($password) {
            // Update with new password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE caretaker SET username = ?, full_name = ?, age = ?, gender = ?, address = ?, phone_number = ?, password = ? WHERE id = ?");
            $result = $stmt->execute([$username, $full_name, $age, $gender, $address, $phone_number, $hashedPassword, $id]);
        } else {
            // Update without changing password
            $stmt = $pdo->prepare("UPDATE caretaker SET username = ?, full_name = ?, age = ?, gender = ?, address = ?, phone_number = ? WHERE id = ?");
            $result = $stmt->execute([$username, $full_name, $age, $gender, $address, $phone_number, $id]);
        }
        error_log("Update caretaker result: " . ($result ? 'success' : 'failed'));
        return $result;
    } catch(PDOException $e) {
        error_log("Error updating caretaker: " . $e->getMessage());
        return false;
    }
}

function deleteCaretakerLocal($id) {
    global $pdo, $db_connected;
    if (!$db_connected) {
        error_log("Database not connected in deleteCaretakerLocal");
        return false;
    }
    
    try {
        // First check if caretaker has assigned elders (if that relationship exists)
        // For now, we'll just delete the caretaker
        $stmt = $pdo->prepare("DELETE FROM caretaker WHERE id = ?");
        $result = $stmt->execute([$id]);
        error_log("Delete caretaker result: " . ($result ? 'success' : 'failed'));
        return $result;
    } catch(PDOException $e) {
        error_log("Error deleting caretaker: " . $e->getMessage());
        return false;
    }
}

function getCaretakerWorkload() {
    global $pdo, $db_connected;
    if (!$db_connected) return [];
    
    try {
        $stmt = $pdo->query("
            SELECT c.*, COUNT(e.id) as assigned_count 
            FROM caretaker c 
            LEFT JOIN elders e ON c.id = e.caretaker_id 
            GROUP BY c.id 
            ORDER BY c.full_name
        ");
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error fetching caretaker workload: " . $e->getMessage());
        return [];
    }
}

$caretakerWorkload = getCaretakerWorkload();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Caretakers - Elderly Care Management System</title>
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
        
        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .nav-links a:hover {
            background-color: rgba(255,255,255,0.1);
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
        
        .main-content {
            flex: 1;
            padding: 30px;
            background-color: #f5f5f5;
        }
        
        .section {
            background-color: white;
            padding: 30px;
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
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
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
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
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
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group textarea {
            resize: vertical;
            height: 80px;
        }
        
        .btn {
            background-color: #3498db;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
        
        .btn-success {
            background-color: #27ae60;
        }
        
        .btn-success:hover {
            background-color: #229954;
        }
        
        .btn-danger {
            background-color: #e74c3c;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .caretakers-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .caretakers-table th,
        .caretakers-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .caretakers-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .caretakers-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .actions {
            display: flex;
            gap: 10px;
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
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        
        .close:hover {
            color: #000;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stats-card {
            background-color: #3498db;
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stats-number {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .workload-low {
            color: #27ae60;
            font-weight: bold;
        }
        
        .workload-medium {
            color: #f39c12;
            font-weight: bold;
        }
        
        .workload-high {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .password-note {
            font-size: 12px;
            color: #666;
            font-style: italic;
            margin-top: 5px;
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
                <li><a href="admin_dashboard.php" id="dashboard-link">Dashboard</a></li>
                <li><a href="admin_dashboard.php#elders-section" id="elders-link">All Elders</a></li>
                <li><a href="manageelders.php" id="manage-elders-link">Manage Elders</a></li>
                <li><a href="admin_dashboard.php#caretakers-section" id="caretakers-link">All Caretakers</a></li>
                <li><a href="managecaretaker.php" class="active" id="manage-caretakers-link">Manage Caretakers</a></li>
                <li><a href="admin_dashboard.php#allocate-section" id="allocate-link">Allocate Caretaker to Elders</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <?php if (isset($db_connected) && !$db_connected): ?>
                <div class="db-status">
                    ⚠️ Database connection failed. Caretaker management is not available.
                </div>
            <?php else: ?>
                
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <!-- Statistics -->
                <div class="section">
                    <h2>Caretaker Statistics</h2>
                    <div class="stats-grid">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo count($caretakers); ?></div>
                            <div>Total Caretakers</div>
                        </div>
                        <div class="stats-card" style="background-color: #27ae60;">
                            <div class="stats-number">
                                <?php echo count(array_filter($caretakers, function($c) { return $c['gender'] === 'Female'; })); ?>
                            </div>
                            <div>Female Caretakers</div>
                        </div>
                        <div class="stats-card" style="background-color: #3498db;">
                            <div class="stats-number">
                                <?php echo count(array_filter($caretakers, function($c) { return $c['gender'] === 'Male'; })); ?>
                            </div>
                            <div>Male Caretakers</div>
                        </div>
                        <div class="stats-card" style="background-color: #f39c12;">
                            <div class="stats-number">
                                <?php 
                                $ages = array_column($caretakers, 'age');
                                echo !empty($ages) ? round(array_sum($ages) / count($ages)) : 0;
                                ?>
                            </div>
                            <div>Average Age</div>
                        </div>
                    </div>
                </div>
                      
                
                <!-- Caretakers List -->
                <div class="section">
                    <h2>All Caretakers (<?php echo count($caretakers); ?>)</h2>
                    <?php if (empty($caretakers)): ?>
                        <p>No caretakers found in the database.</p>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="caretakers-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Age</th>
                                        <th>Gender</th>
                                        <th>Phone</th>
                                        <th>Address</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($caretakers as $caretaker): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($caretaker['id']); ?></td>
                                            <td><?php echo htmlspecialchars($caretaker['username']); ?></td>
                                            <td><?php echo htmlspecialchars($caretaker['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($caretaker['age']); ?></td>
                                            <td><?php echo htmlspecialchars($caretaker['gender']); ?></td>
                                            <td><?php echo htmlspecialchars($caretaker['phone_number']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($caretaker['address'], 0, 50)) . (strlen($caretaker['address']) > 50 ? '...' : ''); ?></td>
                                            <td>
                                                <div class="actions">
                                                    <button onclick="deleteCaretaker(<?php echo $caretaker['id']; ?>)" class="btn btn-small btn-danger">Delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDeleteModal()">&times;</span>
            <h2>Confirm Delete</h2>
            <p>Are you sure you want to delete this caretaker? This action cannot be undone.</p>
            <form method="POST" id="deleteForm">
                <input type="hidden" id="delete_id" name="id">
                <div style="margin-top: 20px;">
                    <button type="submit" name="delete_caretaker" class="btn btn-danger">Yes, Delete</button>
                    <button type="button" onclick="closeDeleteModal()" class="btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>
        </div>
    </div>
    
    <script>
        const caretakers = <?php echo json_encode($caretakers); ?>;
        
        function editCaretaker(id) {
            const caretaker = caretakers.find(c => c.id == id);
            if (caretaker) {
                document.getElementById('edit_id').value = caretaker.id;
                document.getElementById('edit_username').value = caretaker.username;
                document.getElementById('edit_full_name').value = caretaker.full_name;
                document.getElementById('edit_age').value = caretaker.age;
                document.getElementById('edit_gender').value = caretaker.gender;
                document.getElementById('edit_phone_number').value = caretaker.phone_number;
                document.getElementById('edit_address').value = caretaker.address;
                document.getElementById('edit_password').value = ''; // Clear password field
                document.getElementById('editModal').style.display = 'block';
            }
        }
        
        function deleteCaretaker(id) {
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
            if (event.target === deleteModal) {
                deleteModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
