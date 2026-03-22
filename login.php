<?php
session_start();
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$idNumber = trim($_POST["idNumber"] ?? "");
$password = $_POST["password"]      ?? "";

if (!$idNumber || !$password) {
    header("Location: index.php?error=empty");
    exit;
}

// Check admin table first
$stmt = $pdo->prepare("SELECT * FROM admin WHERE adminID = ?");
$stmt->execute([$idNumber]);
$admin = $stmt->fetch();

if ($admin && $admin["password"] === $password) {
    $_SESSION["user"] = [
        "idNumber"  => $admin["adminID"],
        "firstName" => $admin["Name"],
        "lastName"  => "",
        "role"      => "admin"
    ];
    header("Location: admin_profile.php");
    exit;
}

// Check students table
$stmt = $pdo->prepare("SELECT * FROM students WHERE IdNumber = ?");
$stmt->execute([$idNumber]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user["password"])) {
    $_SESSION["user"] = [
        "idNumber"   => $user["IdNumber"],
        "firstName"  => $user["firstName"],
        "lastName"   => $user["lastName"],
        "middleName" => $user["middleName"],
        "yearLevel"  => $user["yearLevel"],
        "email"      => $user["email"],
        "course"     => $user["Course"],
        "address"    => $user["Address"],
        "photo"      => $user["photo"] ?? "",
        "role"       => "student"
    ];
    header("Location: student_profile.php");
    exit;
}

// Failed
header("Location: index.php?error=invalid");
exit;