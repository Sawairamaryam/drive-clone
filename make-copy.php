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

    if (!$file) {
        header("Location: dashboard.php");
        exit;
    }

    if (!empty($file["folder_id"])) {
        $backLink = "folder.php?id=" . $file["folder_id"];
    }

    $oldPath = "uploads/user_files/" . $file["stored_name"];

    if (!file_exists($oldPath)) {
        die("Original file missing from server.");
    }

    $extension = pathinfo($file["stored_name"], PATHINFO_EXTENSION);
    $newStoredName = uniqid("drive_copy_", true) . "." . $extension;
    $newPath = "uploads/user_files/" . $newStoredName;

    if (copy($oldPath, $newPath)) {
        $newOriginalName = "Copy of " . $file["original_name"];

        $stmt = $pdo->prepare("
            INSERT INTO files 
            (user_id, folder_id, original_name, stored_name, file_type, file_size)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $file["folder_id"],
            $newOriginalName,
            $newStoredName,
            $file["file_type"],
            $file["file_size"]
        ]);
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

    if (!$folder) {
        header("Location: dashboard.php");
        exit;
    }

    if (!empty($folder["parent_id"])) {
        $backLink = "folder.php?id=" . $folder["parent_id"];
    }

    $newFolderName = "Copy of " . $folder["folder_name"];

    $stmt = $pdo->prepare("
        INSERT INTO folders 
        (user_id, parent_id, folder_name)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([
        $userId,
        $folder["parent_id"],
        $newFolderName
    ]);
}

header("Location: " . $backLink);
exit;