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

$back = $_GET["back"] ?? "dashboard.php";

if ($type === "file") {
    $stmt = $pdo->prepare("
        UPDATE files 
        SET is_starred = IF(is_starred = 1, 0, 1)
        WHERE id = ? 
        AND user_id = ?
    ");
    $stmt->execute([$id, $userId]);
}

if ($type === "folder") {
    $stmt = $pdo->prepare("
        UPDATE folders 
        SET is_starred = IF(is_starred = 1, 0, 1)
        WHERE id = ? 
        AND user_id = ?
    ");
    $stmt->execute([$id, $userId]);
}

header("Location: " . $back);
exit;