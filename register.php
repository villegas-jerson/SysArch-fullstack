<?php
session_start();
require_once "db.php";

header("Content-Type: application/json");

// Only accept POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
    exit;
}

// Read JSON body (sent by script.js via fetch)
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid data received."]);
    exit;
}

$firstName  = trim($data["firstName"]  ?? "");
$lastName   = trim($data["lastName"]   ?? "");
$middleName = trim($data["middleName"] ?? "");
$yearLevel  = trim($data["yearLevel"]  ?? "");
$email      = trim($data["email"]      ?? "");
$course     = trim($data["course"]     ?? "");
$address    = trim($data["address"]    ?? "");
$password   = $data["password"]        ?? "";

// Validation
if (!$firstName || !$lastName || !$password || !$course || !$yearLevel) {
    echo json_encode(["success" => false, "message" => "Please fill in all required fields."]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(["success" => false, "message" => "Password must be at least 6 characters."]);
    exit;
}

if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email address."]);
    exit;
}

// Check duplicate email
if ($email) {
    $stmt = $pdo->prepare("SELECT IdNumber FROM students WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Email already registered."]);
        exit;
    }
}

// Generate unique ID Number: YYYY-XXXXX
$year = date("Y");
do {
    $stmt  = $pdo->query("SELECT COUNT(*) as cnt FROM students");
    $count = $stmt->fetch()["cnt"] + 1;
    $idNumber = $year . "-" . str_pad($count, 5, "0", STR_PAD_LEFT);

    // Check if this ID already exists
    $check = $pdo->prepare("SELECT IdNumber FROM students WHERE IdNumber = ?");
    $check->execute([$idNumber]);
} while ($check->fetch()); // retry if collision

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert into DB
try {
    $stmt = $pdo->prepare("
        INSERT INTO students 
            (IdNumber, firstName, lastName, middleName, yearLevel, Course, email, Address, password, remainingCredits)
        VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, ?, 30)
    ");
    $stmt->execute([
        $idNumber,
        $firstName,
        $lastName,
        $middleName,
        $yearLevel,
        $course,
        $email,
        $address,
        $hashedPassword
    ]);

    echo json_encode([
        "success"  => true,
        "idNumber" => $idNumber,
        "message"  => "Registration successful!"
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Registration failed: " . $e->getMessage()
    ]);
}
?>