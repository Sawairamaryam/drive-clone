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

$item = null;
$itemName = "";
$itemType = "";
$itemSize = "—";
$itemCreated = "";
$itemStarred = "No";
$itemLocation = "My Drive";
$itemShared = "No";
$backLink = "dashboard.php";

if ($type === "file") {
    $stmt = $pdo->prepare("
        SELECT f.*, fo.folder_name 
        FROM files f
        LEFT JOIN folders fo ON f.folder_id = fo.id
        WHERE f.id = ?
        AND f.user_id = ?
        AND f.is_deleted = 0
    ");
    $stmt->execute([$id, $userId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        header("Location: dashboard.php");
        exit;
    }

    $itemName = $item["original_name"];
    $itemType = "File";
    $itemSize = round($item["file_size"] / 1024, 2) . " KB";
    $itemCreated = date("M d, Y h:i A", strtotime($item["created_at"]));
    $itemStarred = $item["is_starred"] ? "Yes" : "No";

    if (!empty($item["folder_id"])) {
        $itemLocation = $item["folder_name"];
        $backLink = "folder.php?id=" . $item["folder_id"];
    }
}

if ($type === "folder") {
    $stmt = $pdo->prepare("
        SELECT f.*, p.folder_name AS parent_name
        FROM folders f
        LEFT JOIN folders p ON f.parent_id = p.id
        WHERE f.id = ?
        AND f.user_id = ?
        AND f.is_deleted = 0
    ");
    $stmt->execute([$id, $userId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        header("Location: dashboard.php");
        exit;
    }

    $itemName = $item["folder_name"];
    $itemType = "Folder";
    $itemCreated = date("M d, Y h:i A", strtotime($item["created_at"]));
    $itemStarred = $item["is_starred"] ? "Yes" : "No";

    if (!empty($item["parent_id"])) {
        $itemLocation = $item["parent_name"];
        $backLink = "folder.php?id=" . $item["parent_id"];
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM files 
        WHERE folder_id = ?
        AND user_id = ?
        AND is_deleted = 0
    ");
    $stmt->execute([$id, $userId]);
    $fileCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM folders 
        WHERE parent_id = ?
        AND user_id = ?
        AND is_deleted = 0
    ");
    $stmt->execute([$id, $userId]);
    $folderCount = $stmt->fetchColumn();

    $itemSize = $fileCount . " files, " . $folderCount . " folders";
}

$stmt = $pdo->prepare("
    SELECT id FROM shared_links
    WHERE user_id = ?
    AND (
        (file_id IS NOT NULL AND file_id = ?)
        OR
        (folder_id IS NOT NULL AND folder_id = ?)
    )
    LIMIT 1
");

$fileId = $type === "file" ? $id : null;
$folderId = $type === "folder" ? $id : null;

$stmt->execute([$userId, $fileId, $folderId]);
$shared = $stmt->fetch(PDO::FETCH_ASSOC);

if ($shared) {
    $itemShared = "Yes";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Information - <?php echo htmlspecialchars($itemName); ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=10">
</head>
<body class="auth-page">

<div class="auth-card info-card">
    <h1>Item Information</h1>

    <div class="info-main-icon">
        <?php echo $type === "folder" ? "📁" : "📄"; ?>
    </div>

    <h2><?php echo htmlspecialchars($itemName); ?></h2>

    <div class="info-list">
        <div class="info-row">
            <strong>Name</strong>
            <span><?php echo htmlspecialchars($itemName); ?></span>
        </div>

        <div class="info-row">
            <strong>Type</strong>
            <span><?php echo htmlspecialchars($itemType); ?></span>
        </div>

        <div class="info-row">
            <strong>Size / Items</strong>
            <span><?php echo htmlspecialchars($itemSize); ?></span>
        </div>

        <div class="info-row">
            <strong>Location</strong>
            <span><?php echo htmlspecialchars($itemLocation); ?></span>
        </div>

        <div class="info-row">
            <strong>Starred</strong>
            <span><?php echo htmlspecialchars($itemStarred); ?></span>
        </div>

        <div class="info-row">
            <strong>Shared</strong>
            <span><?php echo htmlspecialchars($itemShared); ?></span>
        </div>

        <div class="info-row">
            <strong>Created</strong>
            <span><?php echo htmlspecialchars($itemCreated); ?></span>
        </div>
    </div>

    <div class="info-actions">
        <?php if ($type === "file"): ?>
            <a href="preview.php?id=<?php echo $id; ?>">Preview</a>
            <a href="download.php?id=<?php echo $id; ?>">Download</a>
        <?php endif; ?>

        <?php if ($type === "folder"): ?>
            <a href="folder.php?id=<?php echo $id; ?>">Open Folder</a>
        <?php endif; ?>

        <a href="rename.php?type=<?php echo htmlspecialchars($type); ?>&id=<?php echo $id; ?>">Rename</a>
        <a href="share-link.php?type=<?php echo htmlspecialchars($type); ?>&id=<?php echo $id; ?>">Share</a>
    </div>

    <p class="auth-link">
        <a href="<?php echo htmlspecialchars($backLink); ?>">Back to Drive</a>
    </p>
</div>

</body>
</html>