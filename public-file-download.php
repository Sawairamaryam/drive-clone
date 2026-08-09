<?php
require 'config/db.php';

if (!isset($_GET["token"]) || !isset($_GET["id"])) {
    die("Invalid download link.");
}

$token = $_GET["token"];
$fileId = intval($_GET["id"]);

$stmt = $pdo->prepare("
    SELECT * FROM shared_links 
    WHERE share_token = ?
    AND folder_id IS NOT NULL
");
$stmt->execute([$token]);
$share = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$share) {
    die("Invalid shared folder.");
}

$stmt = $pdo->prepare("
    SELECT * FROM files
    WHERE id = ?
    AND folder_id = ?
    AND is_deleted = 0
");
$stmt->execute([$fileId, $share["folder_id"]]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    die("File not found in shared folder.");
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