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

// Handle elder operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_elder'])) {
        // Add new elder
        $username = trim($_POST['username']);
        $full_name = trim($_POST['full_name']);
        $age = intval($_POST['age']);
        $gender = $_POST['gender'];
        $address = trim($_POST['address']);
        $phone_number = trim($_POST['phone_number']);
        $status = trim($_POST['status']);
        
        if (addElderLocal($username, $full_name, $age, $gender, $address, $phone_number, $status)) {
            $message = 'Elder added successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to add elder. Username might already exist.';
            $messageType = 'error';
        }
    } elseif (isset($_POST['update_elder'])) {
        // Update elder
        $id = intval($_POST['id']);
        $username = $_POST['username'];
        $full_name = trim($_POST['full_name']);
        $age = intval($_POST['age']);
        $gender = $_POST['gender'];
        $address = trim($_POST['address']);
        $phone_number = trim($_POST['phone_number']);
        $status = trim($_POST['status']);
        
        if (updateElderLocal($id, $username, $full_name, $age, $gender, $address, $phone_number, $status)) {
            $message = 'Elder updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update elder.';
            $messageType = 'error';
        }
    } elseif (isset($_POST['delete_elder'])) {
        // Delete elder
        $id = intval($_POST['id']);
        
        if (deleteElderLocal($id)) {
            $message = 'Elder deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to delete elder.';
            $messageType = 'error';
        }
    }
}

// Get all elders from database
$elders = getEldersFromDB();

// Debug: Check what we got
error_log("Database connected: " . ($db_connected ? 'yes' : 'no'));
error_log("Elders count: " . count($elders));
if (empty($elders)) {
    error_log("No elders found in database");
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.html');
    exit();
}

// Helper functions for elder management
function addElderLocal($username, $full_name, $age, $gender, $address, $phone_number, $status) {
    global $pdo, $db_connected;
    if (!$db_connected) {
        error_log("Database not connected in addElderLocal");
        return false;
    }
    
    try {
        // Check if username already exists
        $checkStmt = $pdo->prepare("SELECT id FROM elders WHERE username = ?");
        $checkStmt->execute([$username]);
        if ($checkStmt->fetch()) {
            error_log("Username already exists: " . $username);
            return false;
        }
        
        $stmt = $pdo->prepare("INSERT INTO elders (username, full_name, age, gender, address, phone_number, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([$username, $full_name, $age, $gender, $address, $phone_number, $status]);
        error_log("Add elder result: " . ($result ? 'success' : 'failed'));
        return $result;
    } catch(PDOException $e) {
        error_log("Error adding elder: " . $e->getMessage());
        return false;
    }
}

function updateElderLocal($id, $username, $full_name, $age, $gender, $address, $phone_number, $status) {
    global $pdo, $db_connected;
    if (!$db_connected) {
        error_log("Database not connected in updateElderLocal");
        return false;
    }
    
    try {
        // Check if username exists for other records
        $checkStmt = $pdo->prepare("SELECT id FROM elders WHERE username = ? AND id != ?");
        $checkStmt->execute([$username, $id]);
        if ($checkStmt->fetch()) {
            error_log("Username already exists for another elder: " . $username);
            return false;
        }
        
        $stmt = $pdo->prepare("UPDATE elders SET username = ?, full_name = ?, age = ?, gender = ?, address = ?, phone_number = ?, status = ? WHERE id = ?");
        $result = $stmt->execute([$username, $full_name, $age, $gender, $address, $phone_number, $status, $id]);
        error_log("Update elder result: " . ($result ? 'success' : 'failed'));
        return $result;
    } catch(PDOException $e) {
        error_log("Error updating elder: " . $e->getMessage());
        return false;
    }
}

function deleteElderLocal($id) {
    global $pdo, $db_connected;
    if (!$db_connected) {
        error_log("Database not connected in deleteElderLocal");
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM elders WHERE id = ?");
        $result = $stmt->execute([$id]);
        error_log("Delete elder result: " . ($result ? 'success' : 'failed'));
        return $result;
    } catch(PDOException $e) {
        error_log("Error deleting elder: " . $e->getMessage());
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Elders - Elderly Care Management System</title>
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
        
        .status-active {
            color: #27ae60;
            font-weight: bold;
        }
        
        .status-inactive {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .status-pending {
            color: #f39c12;
            font-weight: bold;
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
                <li><a href="manageelders.php" class="active" id="manage-elders-link">Manage Elders</a></li>
                <li><a href="admin_dashboard.php#caretakers-section" id="caretakers-link">All Caretakers</a></li>
                <li><a href="managecaretaker.php" id="manage-caretakers-link">Manage Caretakers</a></li>
                <li><a href="admin_dashboard.php#allocate-section" id="allocate-link">Allocate Caretaker to Elders</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <?php if (isset($db_connected) && !$db_connected): ?>
                <div class="db-status">
                    ⚠️ Database connection failed. Elder management is not available.
                </div>
            <?php else: ?>
                
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <!-- Statistics -->
                <div class="section">
                    <h2>Elder Statistics</h2>
                    <div class="stats-grid">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo count($elders); ?></div>
                            <div>Total Elders</div>
                        </div>
                        <div class="stats-card" style="background-color: #27ae60;">
                            <div class="stats-number">
                                <?php echo count(array_filter($elders, function($e) { return strtolower($e['status']) === 'active'; })); ?>
                            </div>
                            <div>Active Elders</div>
                        </div>
                        <div class="stats-card" style="background-color: #f39c12;">
                            <div class="stats-number">
                                <?php echo count(array_filter($elders, function($e) { return strtolower($e['status']) === 'pending'; })); ?>
                            </div>
                            <div>Pending Elders</div>
                        </div>
                    </div>
                </div>
                
                <!-- Add New Elder -->
                <div class="section">
                    <h2>Add New Elder</h2>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="username">Username:</label>
                                <input type="text" id="username" name="username" required>
                            </div>
                            <div class="form-group">
                                <label for="full_name">Full Name:</label>
                                <input type="text" id="full_name" name="full_name" required>
                            </div>
                            <div class="form-group">
                                <label for="age">Age:</label>
                                <input type="number" id="age" name="age" min="1" max="120" required>
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender:</label>
                                <select id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="phone_number">Phone Number:</label>
                                <input type="tel" id="phone_number" name="phone_number" required>
                            </div>
                            <div class="form-group">
                                <label for="status">Status:</label>
                                <input type="text" name="status" id="status" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="address">Address:</label>
                            <textarea id="address" name="address" required></textarea>
                        </div>
                        <button type="submit" name="add_elder" class="btn btn-success">Add Elder</button>
                    </form>
                </div>
                
                <!-- Elders List -->
                <div class="section">
                    <h2>All Elders (<?php echo count($elders); ?>)</h2>
                    
                    
                    
                    <?php if (!$db_connected): ?>
                        <div style="background: #ffebee; color: #c62828; padding: 15px; border-radius: 5px; margin: 10px 0;">
                            <strong>Database Connection Error:</strong> Cannot connect to database. Please check your database configuration.
                        </div>
                    <?php elseif (empty($elders)): ?>
                        <div style="background: #fff3e0; color: #ef6c00; padding: 15px; border-radius: 5px; margin: 10px 0;">
                            <strong>No Data Found:</strong> No elders found in the database. You can add elders using the form above.
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="elders-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Age</th>
                                        <th>Gender</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($elders as $elder): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($elder['id']); ?></td>
                                            <td><?php echo htmlspecialchars($elder['username']); ?></td>
                                            <td><?php echo htmlspecialchars($elder['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($elder['age']); ?></td>
                                            <td><?php echo htmlspecialchars($elder['gender']); ?></td>
                                            <td><?php echo htmlspecialchars($elder['phone_number']); ?></td>
                                            <td>
                                                <span class="status-<?php echo strtolower($elder['status']); ?>">
                                                    <?php echo htmlspecialchars($elder['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="actions">
                                                    <button onclick="editElder(<?php echo $elder['id']; ?>)" class="btn btn-small">Edit</button>
                                                    <button onclick="deleteElder(<?php echo $elder['id']; ?>)" class="btn btn-small btn-danger">Delete</button>
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
    
    <!-- Edit Elder Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Edit Elder</h2>
            <form method="POST" id="editForm">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_username">Username:</label>
                    <input type="text" id="edit_username" name="username" required>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_full_name">Full Name:</label>
                        <input type="text" id="edit_full_name" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_age">Age:</label>
                        <input type="number" id="edit_age" name="age" min="1" max="120" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_gender">Gender:</label>
                        <select id="edit_gender" name="gender" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_phone_number">Phone Number:</label>
                        <input type="tel" id="edit_phone_number" name="phone_number" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_status">Status:</label>
                        <input type="text" id="edit_status" name="status" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit_address">Address:</label>
                    <textarea id="edit_address" name="address" required></textarea>
                </div>
                <button type="submit" name="update_elder" class="btn btn-success">Update Elder</button>
            </form>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDeleteModal()">&times;</span>
            <h2>Confirm Delete</h2>
            <p>Are you sure you want to delete this elder? This action cannot be undone.</p>
            <form method="POST" id="deleteForm">
                <input type="hidden" id="delete_id" name="id">
                <div style="margin-top: 20px;">
                    <button type="submit" name="delete_elder" class="btn btn-danger">Yes, Delete</button>
                    <button type="button" onclick="closeDeleteModal()" class="btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>
        </div>
    </div>
    
    <script>
        const elders = <?php echo json_encode($elders); ?>;
        
        function editElder(id) {
            const elder = elders.find(e => e.id == id);
            if (elder) {
                document.getElementById('edit_id').value = elder.id;
                document.getElementById('edit_username').value = elder.username;
                document.getElementById('edit_full_name').value = elder.full_name;
                document.getElementById('edit_age').value = elder.age;
                document.getElementById('edit_gender').value = elder.gender;
                document.getElementById('edit_phone_number').value = elder.phone_number;
                document.getElementById('edit_address').value = elder.address;
                document.getElementById('edit_status').value = elder.status;
                document.getElementById('editModal').style.display = 'block';
            }
        }
        
        function deleteElder(id) {
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
