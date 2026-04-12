<?php
session_start();
require_once "db.php";

header("Content-Type: application/json");

// Check Session
if (!isset($_SESSION["user"]) || $_SESSION["user"]["role"] !== "student") {
    echo json_encode(["success" => false, "message" => "Unauthorized: Please log in again."]);
    exit;
}

$idNumber = $_SESSION["user"]["idNumber"] ?? null;
if (!$idNumber) {
    echo json_encode(["success" => false, "message" => "Session Error: ID Number missing."]);
    exit;
}

// Handle Remove Photo Action
$contentType = $_SERVER["CONTENT_TYPE"] ?? "";
if (strpos($contentType, "application/json") !== false) {
    $data = json_decode(file_get_contents("php://input"), true);
    if (($data["action"] ?? "") === "removePhoto") {
        try {
            $stmt = $pdo->prepare("UPDATE students SET photo = '' WHERE IdNumber = ?");
            $stmt->execute([$idNumber]);
            $_SESSION["user"]["photo"] = "";
            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "DB error: " . $e->getMessage()]);
        }
        exit;
    }
}

// 4. Handle File Upload
if (!isset($_FILES["photo"])) {
    echo json_encode(["success" => false, "message" => "No file received by the server."]);
    exit;
}

$file = $_FILES["photo"];

if ($file["error"] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "message" => "Upload error: " . $file["error"]]);
    exit;
}

// 5. Convert to Base64
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file["tmp_name"]);
finfo_close($finfo);

$imageData = file_get_contents($file["tmp_name"]);
$base64Photo = "data:" . $mimeType . ";base64," . base64_encode($imageData);

// 6. Update Database
try {
    $stmt = $pdo->prepare("UPDATE students SET photo = ? WHERE IdNumber = ?");
    $stmt->execute([$base64Photo, $_SESSION["user"]["idNumber"]]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(["success" => false, "message" => "No changes made. Is IdNumber correct?"]);
        exit;
    }

    // Update session so the change persists across pages
    $_SESSION["user"]["photo"] = $base64Photo;

    echo json_encode([
        "success" => true,
        "photo" => $base64Photo
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}