<?php
$host     = "127.0.0.1";   // or "localhost"
$port     = "3306";         // default MySQL port
$dbname   = "sit-inmonitoring";
$username = "root";
$password = "";             // XAMPP default has NO password

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(json_encode(["success" => false, "message" => "Database connection failed: " . $e->getMessage()]));
}