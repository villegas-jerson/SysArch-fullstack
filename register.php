<?php
session_start();
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$firstName  = trim($_POST["firstName"]  ?? "");
$lastName   = trim($_POST["lastName"]   ?? "");
$middleName = trim($_POST["middleName"] ?? "");
$yearLevel  = trim($_POST["yearLevel"]  ?? "");
$email      = trim($_POST["email"]      ?? "");
$course     = trim($_POST["course"]     ?? "");
$address    = trim($_POST["address"]    ?? "");
$password   = $_POST["password"]        ?? "";
$verify     = $_POST["verifyPassword"]  ?? "";

// Validation
if (!$firstName || !$lastName || !$password || !$course || !$yearLevel) {
    header("Location: index.php?reg_error=Please fill in all required fields.");
    exit;
}
if ($password !== $verify) {
    header("Location: index.php?reg_error=Passwords do not match.");
    exit;
}
if (strlen($password) < 6) {
    header("Location: index.php?reg_error=Password must be at least 6 characters.");
    exit;
}
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: index.php?reg_error=Invalid email address.");
    exit;
}

// Check duplicate email
if ($email) {
    $stmt = $pdo->prepare("SELECT IdNumber FROM students WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header("Location: index.php?reg_error=Email already registered.");
        exit;
    }
}

// Generate unique ID
$year = date("Y");
do {
    $stmt  = $pdo->query("SELECT COUNT(*) as cnt FROM students");
    $count = $stmt->fetch()["cnt"] + 1;
    $idNumber = $year . "-" . str_pad($count, 5, "0", STR_PAD_LEFT);
    $check = $pdo->prepare("SELECT IdNumber FROM students WHERE IdNumber = ?");
    $check->execute([$idNumber]);
} while ($check->fetch());

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("
        INSERT INTO students 
            (IdNumber, firstName, lastName, middleName, yearLevel, Course, email, Address, password, remainingCredits)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 30)
    ");
    $stmt->execute([$idNumber, $firstName, $lastName, $middleName, $yearLevel, $course, $email, $address, $hashedPassword]);

    header("Location: index.php?reg_success=" . urlencode($idNumber));
    exit;

} catch (PDOException $e) {
    header("Location: index.php?reg_error=" . urlencode("Registration failed: " . $e->getMessage()));
    exit;
}
?>