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
        UPDATE files 
        SET is_deleted = 0, deleted_at = NULL
        WHERE id = ? 
        AND user_id = ?
    ");
    $stmt->execute([$id, $userId]);
}

if ($type === "folder") {
    $stmt = $pdo->prepare("
        UPDATE folders 
        SET is_deleted = 0, deleted_at = NULL
        WHERE id = ? 
        AND user_id = ?
    ");
    $stmt->execute([$id, $userId]);
}

header("Location: trash.php");
exit;