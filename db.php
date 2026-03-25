<?php
// 1. Separate the host and the port
$host     = "crossover.proxy.rlwy.net"; 
$port     = "11938"; 
$dbname   = "sit-inmonitoring";
$username = "root";
// 2. Make sure you use the actual Railway password (usually a long random string)
// If you are using the public proxy, 'root' with no password usually won't work.
$password = "YOUR_RAILWAY_PASSWORD_HERE"; 

try {
    // 3. Format the DSN correctly with host and port separated by semicolons
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    
    $pdo = new PDO($dsn, $username, $password);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // This will return a clean JSON error for your script.js to read
    die(json_encode(["success" => false, "message" => "Database connection failed: " . $e->getMessage()]));
}