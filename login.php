<?php
session_start();
require_once "db.php";

$idNumber = trim($_POST["idNumber"] ?? "");
$password = $_POST["password"] ?? "";

if (empty($idNumber) || empty($password)) {
    header("Location: index.php?error=empty");
    exit;
}

// ================= ADMIN LOGIN =================
$stmt = $pdo->prepare("SELECT * FROM admin WHERE adminID = ?");
$stmt->execute([$idNumber]);
$admin = $stmt->fetch();

if ($admin && ($password === $admin["password"] || password_verify($password, $admin["password"]))) {
    $_SESSION["user"] = [
        "idNumber"  => $admin["adminID"],
        "firstName" => $admin["Name"] ?? "Admin",
        "role"      => "admin"
    ];
    header("Location: admin_profile.php");
    exit;
}

// ================= STUDENT LOGIN =================
// 1. Fetch the student including the NEW 'Id' column
$stmt = $pdo->prepare("SELECT * FROM students WHERE IdNumber = ?");
$stmt->execute([$idNumber]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user["password"])) {
    $_SESSION["user"] = [
        "idNumber"   => $user["IdNumber"],
        "firstName"  => $user["firstName"],
        "lastName"   => $user["lastName"],
        "middleName" => $user["middleName"] ?? "",
        "yearLevel"  => $user["yearLevel"],
        "email"      => $user["email"],
        "course"     => $user["Course"],
        "address"    => $user["Address"] ?? "",
        "photo"      => $user["photo"] ?? "",
        "role"       => "student"
    ];
    header("Location: student_profile.php");
    exit;
}

// ================= FAILED LOGIN =================
header("Location: index.php?error=invalid");
exit;