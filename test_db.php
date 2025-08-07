<?php
require_once 'config.php';

echo "<h1>Database Connection Test</h1>";

echo "<h2>Connection Status</h2>";
echo "Database connected: " . ($db_connected ? 'YES' : 'NO') . "<br>";

if ($db_connected) {
    echo "<h2>PDO Object</h2>";
    var_dump($pdo);
    
    echo "<h2>Elders Table Test</h2>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM elders");
        $count = $stmt->fetchColumn();
        echo "Number of elders in database: " . $count . "<br>";
        
        if ($count > 0) {
            echo "<h3>Sample Elder Data:</h3>";
            $stmt = $pdo->query("SELECT * FROM elders LIMIT 5");
            $elders = $stmt->fetchAll();
            echo "<pre>";
            print_r($elders);
            echo "</pre>";
        }
    } catch(PDOException $e) {
        echo "Error querying elders table: " . $e->getMessage() . "<br>";
    }
    
    echo "<h2>Caretakers Table Test</h2>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM caretaker");
        $count = $stmt->fetchColumn();
        echo "Number of caretakers in database: " . $count . "<br>";
        
        if ($count > 0) {
            echo "<h3>Sample Caretaker Data:</h3>";
            $stmt = $pdo->query("SELECT * FROM caretaker LIMIT 5");
            $caretakers = $stmt->fetchAll();
            echo "<pre>";
            print_r($caretakers);
            echo "</pre>";
        }
    } catch(PDOException $e) {
        echo "Error querying caretaker table: " . $e->getMessage() . "<br>";
    }
    
    echo "<h2>Function Test</h2>";
    $elders = getEldersFromDB();
    echo "getEldersFromDB() returned " . count($elders) . " elders<br>";
    if (!empty($elders)) {
        echo "<pre>";
        print_r($elders);
        echo "</pre>";
    }
    
} else {
    echo "<p style='color: red;'>Database connection failed. Check your database configuration in config.php</p>";
}
?>
