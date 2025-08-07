<?php
session_start();
require_once 'config.php';

echo "<h1>Debug Allocations</h1>";

// Check database connection
echo "<h2>Database Connection:</h2>";
echo $db_connected ? "✅ Connected" : "❌ Not Connected";

if ($db_connected) {
    echo "<h2>All Allocations in Database:</h2>";
    try {
        $stmt = $pdo->prepare("SELECT * FROM allocate_the_caretaker ORDER BY id");
        $stmt->execute();
        $all_allocations = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Caretaker Name</th><th>Elder Name</th><th>Elder Status</th><th>Date</th><th>Time</th></tr>";
        
        foreach ($all_allocations as $alloc) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($alloc['id']) . "</td>";
            echo "<td>" . htmlspecialchars($alloc['caretaker_name']) . "</td>";
            echo "<td>" . htmlspecialchars($alloc['elder_name']) . "</td>";
            echo "<td>" . htmlspecialchars($alloc['elder_status']) . "</td>";
            echo "<td>" . htmlspecialchars($alloc['date']) . "</td>";
            echo "<td>" . htmlspecialchars($alloc['time']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
    
    echo "<h2>All Caretakers in Database:</h2>";
    try {
        $stmt = $pdo->prepare("SELECT * FROM caretaker ORDER BY id");
        $stmt->execute();
        $all_caretakers = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Age</th><th>Gender</th></tr>";
        
        foreach ($all_caretakers as $caretaker) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($caretaker['id']) . "</td>";
            echo "<td>" . htmlspecialchars($caretaker['username']) . "</td>";
            echo "<td>" . htmlspecialchars($caretaker['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($caretaker['age']) . "</td>";
            echo "<td>" . htmlspecialchars($caretaker['gender']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
    
    echo "<h2>Session Data:</h2>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    echo "<h2>Cookie Data:</h2>";
    echo "<pre>";
    print_r($_COOKIE);
    echo "</pre>";
}
?>
