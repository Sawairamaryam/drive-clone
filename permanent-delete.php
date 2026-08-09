<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

if (!isset($_GET["type"]) || !isset($_GET["id"])) {
    header("Location: trash.php");
    exit;
}

$userId = $_SESSION["user_id"];
$type = $_GET["type"];
$id = intval($_GET["id"]);

if ($type === "file") {
    $stmt = $pdo->prepare("
        SELECT * FROM files 
        WHERE id = ? 
        AND user_id = ? 
        AND is_deleted = 1
    ");
    $stmt->execute([$id, $userId]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($file) {
        $filePath = "uploads/user_files/" . $file["stored_name"];

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $stmt = $pdo->prepare("
            DELETE FROM files 
            WHERE id = ? 
            AND user_id = ?
        ");
        $stmt->execute([$id, $userId]);
    }
}

if ($type === "folder") {
    $stmt = $pdo->prepare("
        DELETE FROM folders 
        WHERE id = ? 
        AND user_id = ? 
        AND is_deleted = 1
    ");
    $stmt->execute([$id, $userId]);
}

header("Location: trash.php");
exit;