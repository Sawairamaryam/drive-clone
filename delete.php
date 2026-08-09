<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

if (!isset($_GET["type"]) || !isset($_GET["id"])) {
    header("Location: dashboard.php");
    exit;
}

$userId = $_SESSION["user_id"];
$type = $_GET["type"];
$id = intval($_GET["id"]);

$backLink = "dashboard.php";

if ($type === "file") {
    $stmt = $pdo->prepare("
        SELECT * FROM files 
        WHERE id = ? 
        AND user_id = ? 
        AND is_deleted = 0
    ");
    $stmt->execute([$id, $userId]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($file) {
        if (!empty($file["folder_id"])) {
            $backLink = "folder.php?id=" . $file["folder_id"];
        }

        $stmt = $pdo->prepare("
            UPDATE files 
            SET is_deleted = 1, deleted_at = NOW()
            WHERE id = ? 
            AND user_id = ?
        ");
        $stmt->execute([$id, $userId]);
    }
}

if ($type === "folder") {
    $stmt = $pdo->prepare("
        SELECT * FROM folders 
        WHERE id = ? 
        AND user_id = ? 
        AND is_deleted = 0
    ");
    $stmt->execute([$id, $userId]);
    $folder = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($folder) {
        if (!empty($folder["parent_id"])) {
            $backLink = "folder.php?id=" . $folder["parent_id"];
        }

        $stmt = $pdo->prepare("
            UPDATE folders 
            SET is_deleted = 1, deleted_at = NOW()
            WHERE id = ? 
            AND user_id = ?
        ");
        $stmt->execute([$id, $userId]);
    }
}

header("Location: " . $backLink);
exit;