<?php
require 'config/db.php';

if (!isset($_GET["token"])) {
    die("Invalid download link.");
}

$token = $_GET["token"];

$stmt = $pdo->prepare("
    SELECT s.*, f.*
    FROM shared_links s
    JOIN files f ON s.file_id = f.id
    WHERE s.share_token = ?
    AND f.is_deleted = 0
");
$stmt->execute([$token]);
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