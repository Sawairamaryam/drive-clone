<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

function createFolder($pdo, $userId, $parentId, $folderName) {
    $folderName = trim($folderName);

    if ($folderName === "") {
        $folderName = "Uploaded Folder";
    }

    if ($parentId === null) {
        $stmt = $pdo->prepare("
            INSERT INTO folders (user_id, parent_id, folder_name)
            VALUES (?, NULL, ?)
        ");
        $stmt->execute([$userId, $folderName]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO folders (user_id, parent_id, folder_name)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, $parentId, $folderName]);
    }

    return $pdo->lastInsertId();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $userId = $_SESSION["user_id"];

    $currentFolderId = null;

    if (isset($_POST["folder_id"]) && $_POST["folder_id"] !== "") {
        $currentFolderId = intval($_POST["folder_id"]);
    }

    $rootFolderName = "Uploaded Folder";

    if (isset($_POST["root_folder_name"]) && trim($_POST["root_folder_name"]) !== "") {
        $rootFolderName = trim($_POST["root_folder_name"]);
    }

    if (!isset($_FILES["folder_files"])) {
        if ($currentFolderId) {
            header("Location: folder.php?id=" . $currentFolderId);
            exit;
        }

        header("Location: dashboard.php");
        exit;
    }

    /*
        Main uploaded folder create hoga.
        Example: agar selected folder ka name "drive" hai,
        to Drive ke andar "drive" naam ka folder banega.
    */
    $mainFolderId = createFolder($pdo, $userId, $currentFolderId, $rootFolderName);

    $folderCache = [];
    $folderCache[$rootFolderName] = $mainFolderId;

    $totalFiles = count($_FILES["folder_files"]["name"]);

    for ($i = 0; $i < $totalFiles; $i++) {
        if ($_FILES["folder_files"]["error"][$i] !== 0) {
            continue;
        }

        $relativePath = "";

        if (isset($_FILES["folder_files"]["full_path"][$i])) {
            $relativePath = $_FILES["folder_files"]["full_path"][$i];
        } else {
            $relativePath = $_FILES["folder_files"]["name"][$i];
        }

        $relativePath = str_replace("\\", "/", $relativePath);
        $relativePath = trim($relativePath, "/");

        $pathParts = explode("/", $relativePath);
        $originalFileName = array_pop($pathParts);

        if (empty($originalFileName)) {
            continue;
        }

        /*
            Default: file main uploaded folder ke andar jayegi.
        */
        $parentId = $mainFolderId;

        /*
            Agar selected folder ke andar subfolders hain,
            to woh bhi database me create honge.
            Example:
            drive/images/photo.jpg
        */
        if (count($pathParts) > 1) {
            array_shift($pathParts);

            $cachePath = $rootFolderName;

            foreach ($pathParts as $folderName) {
                $folderName = trim($folderName);

                if ($folderName === "") {
                    continue;
                }

                $cachePath .= "/" . $folderName;

                if (isset($folderCache[$cachePath])) {
                    $parentId = $folderCache[$cachePath];
                } else {
                    $newFolderId = createFolder($pdo, $userId, $parentId, $folderName);
                    $folderCache[$cachePath] = $newFolderId;
                    $parentId = $newFolderId;
                }
            }
        }

        $fileType = $_FILES["folder_files"]["type"][$i];
        $fileSize = $_FILES["folder_files"]["size"][$i];
        $tmpName = $_FILES["folder_files"]["tmp_name"][$i];

        $extension = pathinfo($originalFileName, PATHINFO_EXTENSION);
        $storedName = uniqid("drive_", true);

        if (!empty($extension)) {
            $storedName .= "." . $extension;
        }

        $uploadPath = "uploads/user_files/" . $storedName;

        if (move_uploaded_file($tmpName, $uploadPath)) {
            $stmt = $pdo->prepare("
                INSERT INTO files 
                (user_id, folder_id, original_name, stored_name, file_type, file_size)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $userId,
                $parentId,
                $originalFileName,
                $storedName,
                $fileType,
                $fileSize
            ]);
        }
    }

    if ($currentFolderId) {
        header("Location: folder.php?id=" . $currentFolderId);
        exit;
    }

    header("Location: dashboard.php");
    exit;
}

header("Location: dashboard.php");
exit;