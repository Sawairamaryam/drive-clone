<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $folderName = trim($_POST["folder_name"]);
    $userId = $_SESSION["user_id"];
    $parentId = null;

    if (isset($_POST["parent_id"]) && $_POST["parent_id"] !== "") {
        $parentId = intval($_POST["parent_id"]);
    }

    if (!empty($folderName)) {
        $stmt = $pdo->prepare("
            INSERT INTO folders (user_id, parent_id, folder_name)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([$userId, $parentId, $folderName]);
    }

    if ($parentId) {
        header("Location: folder.php?id=" . $parentId);
        exit;
    }
}

header("Location: dashboard.php");
exit;