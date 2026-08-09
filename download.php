<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

if (!isset($_GET["id"])) {
    header("Location: dashboard.php");
    exit;
}

$userId = $_SESSION["user_id"];
$fileId = intval($_GET["id"]);

$stmt = $pdo->prepare("
    SELECT * FROM files 
    WHERE id = ? 
    AND user_id = ? 
    AND is_deleted = 0
");
$stmt->execute([$fileId, $userId]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    die("File not found.");
}

$filePath = "uploads/user_files/" . $file["stored_name"];

if (!file_exists($filePath)) {
    die("File missing from server.");
}

header("Content-Description: File Transfer");
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"" . basename($file["original_name"]) . "\"");
header("Content-Length: " . filesize($filePath));

readfile($filePath);
exit;