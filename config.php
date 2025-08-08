<?php
// Database configuration
$db_host = 'localhost';
$db_name = 'elderease';
$db_user = 'root';  // Default XAMPP username
$db_pass = '';      // Default XAMPP password (empty)

// Create database connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db_connected = true;
} catch(PDOException $e) {
    $db_connected = false;
    // Fallback to hard-coded data if database connection fails
    error_log("Database connection failed: " . $e->getMessage());
}

// Database helper functions
function getEldersFromDB() {
    global $pdo, $db_connected;
    if (!$db_connected) return [];
    
    try {
        $stmt = $pdo->query("SELECT * FROM elders ORDER BY full_name");
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error fetching elders: " . $e->getMessage());
        return [];
    }
}

function getCaretakersFromDB() {
    global $pdo, $db_connected;
    if (!$db_connected) return [];
    
    try {
        $stmt = $pdo->query("SELECT * FROM caretaker ORDER BY full_name");
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error fetching caretakers: " . $e->getMessage());
        return [];
    }
}

function getUsersFromDB() {
    global $pdo, $db_connected;
    if (!$db_connected) return [];
    
    try {
        $stmt = $pdo->query("SELECT * FROM users ORDER BY username");
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error fetching users: " . $e->getMessage());
        return [];
    }
}

function getElderByIdFromDB($id) {
    global $pdo, $db_connected;
    if (!$db_connected) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM elders WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Error fetching elder by ID: " . $e->getMessage());
        return null;
    }
}

function getCaretakerByIdFromDB($id) {
    global $pdo, $db_connected;
    if (!$db_connected) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM caretaker WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Error fetching caretaker by ID: " . $e->getMessage());
        return null;
    }
}

function getEldersByCaretakerFromDB($caretakerId) {
    global $pdo, $db_connected;
    if (!$db_connected) return [];
    
    try {
        // Get caretaker information first
        $caretaker_name = null;
        $caretaker_username = null;
        
        if (is_numeric($caretakerId)) {
            // If caretakerId is numeric (ID), get caretaker details by ID
            $stmt = $pdo->prepare("SELECT full_name, username FROM caretaker WHERE id = ?");
            $stmt->execute([$caretakerId]);
            $caretaker_data = $stmt->fetch();
            if ($caretaker_data) {
                $caretaker_name = $caretaker_data['full_name'];
                $caretaker_username = $caretaker_data['username'];
            }
        } else {
            // If caretakerId is username, get caretaker details by username
            $stmt = $pdo->prepare("SELECT full_name, username FROM caretaker WHERE username = ?");
            $stmt->execute([$caretakerId]);
            $caretaker_data = $stmt->fetch();
            if ($caretaker_data) {
                $caretaker_name = $caretaker_data['full_name'];
                $caretaker_username = $caretaker_data['username'];
            } else {
                // Fallback: treat caretakerId as the name itself
                $caretaker_name = $caretakerId;
            }
        }
        
        error_log("Searching allocations for caretaker: Name='$caretaker_name', Username='$caretaker_username', ID='$caretakerId'");
        
        // Try multiple approaches to find allocations
        $allocated_elders = [];
        
        // Approach 1: Search by caretaker_name (full_name)
        if ($caretaker_name) {
            $stmt = $pdo->prepare("
                SELECT 
                    COALESCE(e.id, 0) as id,
                    COALESCE(e.username, a.elder_name) as username,
                    COALESCE(e.full_name, a.elder_name) as full_name,
                    COALESCE(e.age, 0) as age,
                    COALESCE(e.gender, 'N/A') as gender,
                    COALESCE(e.address, 'N/A') as address,
                    COALESCE(e.phone_number, 'N/A') as phone_number,
                    COALESCE(e.status, a.elder_status) as status,
                    a.elder_status as allocation_status, 
                    a.date as allocation_date, 
                    a.time as allocation_time,
                    a.id as allocation_id, 
                    a.caretaker_name as assigned_caretaker, 
                    a.elder_name as elder_name
                FROM allocate_the_caretaker a 
                LEFT JOIN elders e ON (a.elder_name = e.username OR a.elder_name = e.full_name)
                WHERE a.caretaker_name = ? 
                ORDER BY a.date DESC, a.time DESC
            ");
            $stmt->execute([$caretaker_name]);
            $allocated_elders = $stmt->fetchAll();
            error_log("Found " . count($allocated_elders) . " allocations by full name: $caretaker_name");
        }
        
        // Approach 2: If no results, try searching by username
        if (empty($allocated_elders) && $caretaker_username) {
            $stmt = $pdo->prepare("
                SELECT 
                    COALESCE(e.id, 0) as id,
                    COALESCE(e.username, a.elder_name) as username,
                    COALESCE(e.full_name, a.elder_name) as full_name,
                    COALESCE(e.age, 0) as age,
                    COALESCE(e.gender, 'N/A') as gender,
                    COALESCE(e.address, 'N/A') as address,
                    COALESCE(e.phone_number, 'N/A') as phone_number,
                    COALESCE(e.status, a.elder_status) as status,
                    a.elder_status as allocation_status, 
                    a.date as allocation_date, 
                    a.time as allocation_time,
                    a.id as allocation_id, 
                    a.caretaker_name as assigned_caretaker, 
                    a.elder_name as elder_name
                FROM allocate_the_caretaker a 
                LEFT JOIN elders e ON (a.elder_name = e.username OR a.elder_name = e.full_name)
                WHERE a.caretaker_name = ? 
                ORDER BY a.date DESC, a.time DESC
            ");
            $stmt->execute([$caretaker_username]);
            $allocated_elders = $stmt->fetchAll();
            error_log("Found " . count($allocated_elders) . " allocations by username: $caretaker_username");
        }
        
        // Approach 3: If still no results, search by ID as string
        if (empty($allocated_elders)) {
            $stmt = $pdo->prepare("
                SELECT 
                    COALESCE(e.id, 0) as id,
                    COALESCE(e.username, a.elder_name) as username,
                    COALESCE(e.full_name, a.elder_name) as full_name,
                    COALESCE(e.age, 0) as age,
                    COALESCE(e.gender, 'N/A') as gender,
                    COALESCE(e.address, 'N/A') as address,
                    COALESCE(e.phone_number, 'N/A') as phone_number,
                    COALESCE(e.status, a.elder_status) as status,
                    a.elder_status as allocation_status, 
                    a.date as allocation_date, 
                    a.time as allocation_time,
                    a.id as allocation_id, 
                    a.caretaker_name as assigned_caretaker, 
                    a.elder_name as elder_name
                FROM allocate_the_caretaker a 
                LEFT JOIN elders e ON (a.elder_name = e.username OR a.elder_name = e.full_name)
                WHERE a.caretaker_name = ? 
                ORDER BY a.date DESC, a.time DESC
            ");
            $stmt->execute([$caretakerId]);
            $allocated_elders = $stmt->fetchAll();
            error_log("Found " . count($allocated_elders) . " allocations by caretaker ID: $caretakerId");
        }
        
        // Log the results for debugging
        foreach ($allocated_elders as $elder) {
            error_log("Allocation found: Elder=" . ($elder['full_name'] ?? 'N/A') . ", Status=" . ($elder['allocation_status'] ?? 'N/A') . ", Date=" . ($elder['allocation_date'] ?? 'N/A'));
        }
        
        return $allocated_elders;
        
    } catch(PDOException $e) {
        error_log("Error fetching elders by caretaker: " . $e->getMessage());
        return [];
    }
}

function getUnassignedEldersFromDB() {
    global $pdo, $db_connected;
    if (!$db_connected) return [];
    
    try {
        $stmt = $pdo->query("SELECT * FROM elders WHERE caretaker_id IS NULL OR caretaker_id = 0 ORDER BY full_name");
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error fetching unassigned elders: " . $e->getMessage());
        return [];
    }
}

function getAvailableCaretakersFromDB() {
    global $pdo, $db_connected;
    if (!$db_connected) return [];
    
    try {
        // Get caretakers with less than 3 assigned elders
        $stmt = $pdo->query("
            SELECT c.*, COUNT(e.id) as assigned_count 
            FROM caretaker c 
            LEFT JOIN elders e ON c.id = e.caretaker_id 
            GROUP BY c.id 
            HAVING assigned_count < 3 
            ORDER BY c.full_name
        ");
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error fetching available caretakers: " . $e->getMessage());
        return [];
    }
}

function updateElderCaretaker($elderId, $caretakerId) {
    global $pdo, $db_connected;
    if (!$db_connected) return false;
    
    try {
        $stmt = $pdo->prepare("UPDATE elders SET caretaker_id = ? WHERE id = ?");
        return $stmt->execute([$caretakerId, $elderId]);
    } catch(PDOException $e) {
        error_log("Error updating elder caretaker: " . $e->getMessage());
        return false;
    }
}

function createCaretaker($username, $password, $full_name, $age, $address, $phone_number, $gender) {
    global $pdo, $db_connected;
    if (!$db_connected) return false;
    
    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO caretaker (username, password, full_name, age, address, phone_number, gender) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$username, $hashedPassword, $full_name, $age, $address, $phone_number, $gender]);
    } catch(PDOException $e) {
        error_log("Error creating caretaker: " . $e->getMessage());
        return false;
    }
}

function checkCaretakerExists($username) {
    global $pdo, $db_connected;
    if (!$db_connected) return false;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM caretaker WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetchColumn() > 0;
    } catch(PDOException $e) {
        error_log("Error checking caretaker existence: " . $e->getMessage());
        return false;
    }
}

function createUser($username, $password, $name, $age, $address, $phoneno, $gender) {
    global $pdo, $db_connected;
    if (!$db_connected) return false;
    
    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, name, age, address, phoneno, gender, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        return $stmt->execute([$username, $hashedPassword, $name, $age, $address, $phoneno, $gender]);
    } catch(PDOException $e) {
        error_log("Error creating user: " . $e->getMessage());
        return false;
    }
}

function authenticateAdmin($username, $password) {
    global $pdo, $db_connected;
    if (!$db_connected) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && $admin['password'] === $password) {
            return $admin;
        }
        return null;
    } catch(PDOException $e) {
        error_log("Error authenticating admin: " . $e->getMessage());
        return null;
    }
}

function authenticateCaretaker($username, $password) {
    global $pdo, $db_connected;
    if (!$db_connected) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM caretaker WHERE username = ?");
        $stmt->execute([$username]);
        $caretaker = $stmt->fetch();
        
        if ($caretaker && password_verify($password, $caretaker['password'])) {
            return $caretaker;
        }
        return null;
    } catch(PDOException $e) {
        error_log("Error authenticating caretaker: " . $e->getMessage());
        return null;
    }
}

// Cookie-based authentication functions
function validateAuthCookie() {
    if (!isset($_COOKIE['eldercare_user_id']) || !isset($_COOKIE['eldercare_role']) || !isset($_COOKIE['eldercare_auth_token'])) {
        return false;
    }
    
    $user_id = $_COOKIE['eldercare_user_id'];
    $role = $_COOKIE['eldercare_role'];
    $username = $_COOKIE['eldercare_username'] ?? '';
    $auth_token = $_COOKIE['eldercare_auth_token'];
    
    // Basic validation - in production, you'd want more sophisticated token validation
    if (empty($user_id) || empty($role) || empty($auth_token)) {
        return false;
    }
    
    return [
        'user_id' => $user_id,
        'username' => $username,
        'role' => $role,
        'caretaker_id' => $_COOKIE['eldercare_caretaker_id'] ?? null
    ];
}

function authenticateFromCookie() {
    $cookie_data = validateAuthCookie();
    if (!$cookie_data) {
        return false;
    }
    
    // Restore session data from cookies
    $_SESSION['user_id'] = $cookie_data['user_id'];
    $_SESSION['username'] = $cookie_data['username'];
    $_SESSION['role'] = $cookie_data['role'];
    
    if ($cookie_data['role'] === 'admin') {
        $_SESSION['name'] = 'Administrator';
    } elseif ($cookie_data['role'] === 'caretaker') {
        $_SESSION['caretaker_id'] = $cookie_data['caretaker_id'];
        // Get caretaker data from database
        $caretaker = getCaretakerByUsername($cookie_data['username']);
        if ($caretaker) {
            $_SESSION['name'] = $caretaker['full_name'];
            $_SESSION['caretaker_data'] = $caretaker;
        }
    }
    
    return true;
}

function clearAuthCookies() {
    $cookie_options = [
        'expires' => time() - 3600, // Expire in the past
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Strict'
    ];
    
    setcookie('eldercare_user_id', '', $cookie_options);
    setcookie('eldercare_username', '', $cookie_options);
    setcookie('eldercare_role', '', $cookie_options);
    setcookie('eldercare_caretaker_id', '', $cookie_options);
    setcookie('eldercare_auth_token', '', $cookie_options);
}

function requireAuth($required_role = null) {
    // Check session first
    $authenticated = isset($_SESSION['user_id']) && isset($_SESSION['role']);
    
    // If not authenticated via session, try cookie authentication
    if (!$authenticated) {
        $authenticated = authenticateFromCookie();
    }
    
    if (!$authenticated) {
        header('Location: login.php');
        exit();
    }
    
    // Check role if specified
    if ($required_role && $_SESSION['role'] !== $required_role) {
        header('Location: login.php?error=unauthorized');
        exit();
    }
    
    return true;
}

function authenticateUser($username, $password) {
    global $pdo, $db_connected;
    if (!$db_connected) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return null;
    } catch(PDOException $e) {
        error_log("Error authenticating user: " . $e->getMessage());
        return null;
    }
}

// Load data from database
$elders = getEldersFromDB();
$caretakers = getCaretakersFromDB();
$users = getUsersFromDB();

// Helper functions (updated to use database when available)
function getElderById($id) {
    return getElderByIdFromDB($id);
}

function getCaretakerById($username) {
    return getCaretakerByIdFromDB($username);
}

function getCaretakerByUsername($username) {
    global $pdo, $db_connected;
    if (!$db_connected) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM caretaker WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Error fetching caretaker by username: " . $e->getMessage());
        return null;
    }
}

function getEldersByCaretaker($caretakerId) {
    return getEldersByCaretakerFromDB($caretakerId);
}

function getUnassignedElders() {
    return getUnassignedEldersFromDB();
}

function getAvailableCaretakers() {
    return getAvailableCaretakersFromDB();
}

// Additional functions for allocation
function getElderByUsername($username) {
    global $pdo, $db_connected;
    if (!$db_connected) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM elders WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Error fetching elder by username: " . $e->getMessage());
        return null;
    }
}

function saveAllocation($caretaker_name, $elder_name, $elder_status, $date, $time) {
    global $pdo, $db_connected;
    if (!$db_connected) return false;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO allocate_the_caretaker (caretaker_name, elder_name, elder_status, date, time) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$caretaker_name, $elder_name, $elder_status, $date, $time]);
    } catch(PDOException $e) {
        error_log("Error saving allocation: " . $e->getMessage());
        return false;
    }
}

function getAllocations() {
    global $pdo, $db_connected;
    if (!$db_connected) return [];
    
    try {
        $stmt = $pdo->query("SELECT * FROM allocate_the_caretaker ORDER BY date DESC, time DESC");
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error fetching allocations: " . $e->getMessage());
        return [];
    }
}

function getAllocationById($id) {
    global $pdo, $db_connected;
    if (!$db_connected) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM allocate_the_caretaker WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Error fetching allocation by ID: " . $e->getMessage());
        return null;
    }
}

function updateAllocation($id, $caretaker_name, $elder_name, $elder_status, $date, $time) {
    global $pdo, $db_connected;
    if (!$db_connected) return false;
    
    try {
        $stmt = $pdo->prepare("UPDATE allocate_the_caretaker SET caretaker_name = ?, elder_name = ?, elder_status = ?, date = ?, time = ? WHERE id = ?");
        return $stmt->execute([$caretaker_name, $elder_name, $elder_status, $date, $time, $id]);
    } catch(PDOException $e) {
        error_log("Error updating allocation: " . $e->getMessage());
        return false;
    }
}

function deleteAllocation($id) {
    global $pdo, $db_connected;
    if (!$db_connected) return false;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM allocate_the_caretaker WHERE id = ?");
        return $stmt->execute([$id]);
    } catch(PDOException $e) {
        error_log("Error deleting allocation: " . $e->getMessage());
        return false;
    }
}

// Elder management functions
function addElder($username, $full_name, $age, $gender, $address, $phone_number, $status) {
    global $pdo, $db_connected;
    if (!$db_connected) return false;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO elders (username, full_name, age, gender, address, phone_number, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$username, $full_name, $age, $gender, $address, $phone_number, $status]);
    } catch(PDOException $e) {
        error_log("Error adding elder: " . $e->getMessage());
        return false;
    }
}

function updateElder($id, $username, $full_name, $age, $gender, $address, $phone_number, $status) {
    global $pdo, $db_connected;
    if (!$db_connected) return false;
    
    try {
        $stmt = $pdo->prepare("UPDATE elders SET username = ?, full_name = ?, age = ?, gender = ?, address = ?, phone_number = ?, status = ? WHERE id = ?");
        return $stmt->execute([$username, $full_name, $age, $gender, $address, $phone_number, $status, $id]);
    } catch(PDOException $e) {
        error_log("Error updating elder: " . $e->getMessage());
        return false;
    }
}

function deleteElder($id) {
    global $pdo, $db_connected;
    if (!$db_connected) return false;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM elders WHERE id = ?");
        return $stmt->execute([$id]);
    } catch(PDOException $e) {
        error_log("Error deleting elder: " . $e->getMessage());
        return false;
    }
}

// Caretaker management functions
function addCaretaker($username, $full_name, $age, $gender, $address, $phone_number, $password) {
    global $pdo, $db_connected;
    if (!$db_connected) return false;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO caretaker (username, full_name, age, gender, address, phone_number, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$username, $full_name, $age, $gender, $address, $phone_number, $password]);
    } catch(PDOException $e) {
        error_log("Error adding caretaker: " . $e->getMessage());
        return false;
    }
}

function updateCaretaker($id, $username, $full_name, $age, $gender, $address, $phone_number, $password = null) {
    global $pdo, $db_connected;
    if (!$db_connected) return false;
    
    try {
        if ($password !== null) {
            // Update with password
            $stmt = $pdo->prepare("UPDATE caretaker SET username = ?, full_name = ?, age = ?, gender = ?, address = ?, phone_number = ?, password = ? WHERE id = ?");
            return $stmt->execute([$username, $full_name, $age, $gender, $address, $phone_number, $password, $id]);
        } else {
            // Update without password
            $stmt = $pdo->prepare("UPDATE caretaker SET username = ?, full_name = ?, age = ?, gender = ?, address = ?, phone_number = ? WHERE id = ?");
            return $stmt->execute([$username, $full_name, $age, $gender, $address, $phone_number, $id]);
        }
    } catch(PDOException $e) {
        error_log("Error updating caretaker: " . $e->getMessage());
        return false;
    }
}

function deleteCaretaker($id) {
    global $pdo, $db_connected;
    if (!$db_connected) return false;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM caretaker WHERE id = ?");
        return $stmt->execute([$id]);
    } catch(PDOException $e) {
        error_log("Error deleting caretaker: " . $e->getMessage());
        return false;
    }
}
?>