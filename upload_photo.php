<?php
session_start();
require_once "db.php";
header("Content-Type: application/json");

if (!isset($_SESSION["user"]) || $_SESSION["user"]["role"] !== "student") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

// Handle remove photo (JSON request)
if (isset($_SERVER["HTTP_X_REQUESTED_WITH"])) {
    $data = json_decode(file_get_contents("php://input"), true);
    if (($data["action"] ?? "") === "removePhoto") {
        $stmt = $pdo->prepare("UPDATE students SET photo=NULL WHERE IdNumber=?");
        $stmt->execute([$_SESSION["user"]["idNumber"]]);
        $_SESSION["user"]["photo"] = "";
        echo json_encode(["success" => true]);
        exit;
    }
}

// Handle photo upload
if (!isset($_FILES["photo"])) {
    echo json_encode(["success" => false, "message" => "No file uploaded"]);
    exit;
}

$file    = $_FILES["photo"];
$allowed = ["image/jpeg", "image/png", "image/gif", "image/webp"];
$maxSize = 2 * 1024 * 1024;

if (!in_array($file["type"], $allowed)) {
    echo json_encode(["success" => false, "message" => "Only image files are allowed."]);
    exit;
}
if ($file["size"] > $maxSize) {
    echo json_encode(["success" => false, "message" => "Image must be under 2MB."]);
    exit;
}

$imageData = base64_encode(file_get_contents($file["tmp_name"]));
$base64    = "data:" . $file["type"] . ";base64," . $imageData;

$stmt = $pdo->prepare("UPDATE students SET photo=? WHERE IdNumber=?");
$stmt->execute([$base64, $_SESSION["user"]["idNumber"]]);
$_SESSION["user"]["photo"] = $base64;

echo json_encode(["success" => true, "photo" => $base64]);