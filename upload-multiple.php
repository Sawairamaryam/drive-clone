<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $userId = $_SESSION["user_id"];
    $folderId = null;

    if (isset($_POST["folder_id"]) && $_POST["folder_id"] !== "") {
        $folderId = intval($_POST["folder_id"]);
    }

    if (isset($_FILES["files"])) {
        $totalFiles = count($_FILES["files"]["name"]);

        for ($i = 0; $i < $totalFiles; $i++) {
            if ($_FILES["files"]["error"][$i] === 0) {
                $originalName = $_FILES["files"]["name"][$i];
                $fileType = $_FILES["files"]["type"][$i];
                $fileSize = $_FILES["files"]["size"][$i];
                $tmpName = $_FILES["files"]["tmp_name"][$i];

                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                $storedName = uniqid("drive_", true) . "." . $extension;

                $uploadPath = "uploads/user_files/" . $storedName;

                if (move_uploaded_file($tmpName, $uploadPath)) {
                    $stmt = $pdo->prepare("
                        INSERT INTO files 
                        (user_id, folder_id, original_name, stored_name, file_type, file_size)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $userId,
                        $folderId,
                        $originalName,
                        $storedName,
                        $fileType,
                        $fileSize
                    ]);
                }
            }
        }
    }

    if ($folderId) {
        header("Location: folder.php?id=" . $folderId);
        exit;
    }
}

header("Location: dashboard.php");
exit;