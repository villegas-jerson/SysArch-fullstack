<?php
session_start();
require_once "db.php";

// ----------------------
// 1. Ensure PDO throws exceptions
// ----------------------
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// ----------------------
// 2. Always return JSON
// ----------------------
header("Content-Type: application/json");

// ----------------------
// 3. Check if student is logged in
// ----------------------
if (!isset($_SESSION["user"]) || $_SESSION["user"]["role"] !== "student") {
    echo json_encode(["success" => false, "message" => "Unauthorized."]);
    exit;
}

$idNumber = $_SESSION["user"]["idNumber"];

// ----------------------
// 4. Handle remove photo (JSON payload)
// ----------------------
$contentType = $_SERVER["CONTENT_TYPE"] ?? "";
if (strpos($contentType, "application/json") !== false) {
    $data = json_decode(file_get_contents("php://input"), true);
    if (($data["action"] ?? "") === "removePhoto") {
        try {
            $stmt = $pdo->prepare("UPDATE students SET photo = NULL WHERE IdNumber = ?");
            $stmt->execute([$idNumber]);
            $_SESSION["user"]["photo"] = "";
            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "DB error: " . $e->getMessage()]);
        }
        exit;
    }
}

// ----------------------
// 5. Check for uploaded file
// ----------------------
if (!isset($_FILES["photo"])) {
    echo json_encode(["success" => false, "message" => "No file uploaded."]);
    exit;
}

$file = $_FILES["photo"];

// ----------------------
// 6. Check upload errors
// ----------------------
if ($file["error"] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "message" => "Upload error code: " . $file["error"]]);
    exit;
}

// ----------------------
// 7. Validate file size (max 2MB)
// ----------------------
$maxSize = 2 * 1024 * 1024;
if ($file["size"] > $maxSize) {
    echo json_encode(["success" => false, "message" => "Image must be under 2MB."]);
    exit;
}

// ----------------------
// 8. Validate MIME type safely
// ----------------------
$finfo = finfo_open(FILEINFO_MIME_TYPE);
if ($finfo === false) {
    echo json_encode(["success" => false, "message" => "Failed to open fileinfo."]);
    exit;
}

$mimeType = finfo_file($finfo, $file["tmp_name"]);
finfo_close($finfo); // only one call, safe now

if ($mimeType === false) {
    echo json_encode(["success" => false, "message" => "Failed to detect MIME type."]);
    exit;
}

$allowedTypes = ["image/jpeg", "image/png", "image/gif", "image/webp"];
if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode([
        "success" => false,
        "message" => "Only JPG, PNG, GIF, WEBP allowed. Detected: $mimeType"
    ]);
    exit;
}

// ----------------------
// 9. Convert image to Base64
// ----------------------
$imageData = file_get_contents($file["tmp_name"]);
$base64Photo = "data:" . $mimeType . ";base64," . base64_encode($imageData);

// ----------------------
// 10. Update database with try/catch
// ----------------------
try {
    $stmt = $pdo->prepare("UPDATE students SET photo = ? WHERE IdNumber = ?");
    $stmt->execute([$base64Photo, $idNumber]);

    $affectedRows = $stmt->rowCount(); // debug
    if ($affectedRows === 0) {
        echo json_encode(["success" => false, "message" => "No rows updated. Check IdNumber."]);
        exit;
    }

    $_SESSION["user"]["photo"] = $base64Photo;

    echo json_encode([
        "success" => true,
        "photo" => $base64Photo
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "DB error: " . $e->getMessage()
    ]);
}