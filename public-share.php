<?php
require 'config/db.php';

if (!isset($_GET["token"])) {
    die("Invalid share link.");
}

$token = $_GET["token"];

$stmt = $pdo->prepare("
    SELECT * FROM shared_links 
    WHERE share_token = ?
");
$stmt->execute([$token]);
$share = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$share) {
    die("Share link not found.");
}

$file = null;
$folder = null;
$folderFiles = [];
$folderFolders = [];

if (!empty($share["file_id"])) {
    $stmt = $pdo->prepare("
        SELECT * FROM files 
        WHERE id = ? 
        AND is_deleted = 0
    ");
    $stmt->execute([$share["file_id"]]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!empty($share["folder_id"])) {
    $stmt = $pdo->prepare("
        SELECT * FROM folders 
        WHERE id = ? 
        AND is_deleted = 0
    ");
    $stmt->execute([$share["folder_id"]]);
    $folder = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($folder) {
        $stmt = $pdo->prepare("
            SELECT * FROM folders
            WHERE parent_id = ?
            AND is_deleted = 0
            ORDER BY created_at DESC
        ");
        $stmt->execute([$folder["id"]]);
        $folderFolders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
            SELECT * FROM files
            WHERE folder_id = ?
            AND is_deleted = 0
            ORDER BY created_at DESC
        ");
        $stmt->execute([$folder["id"]]);
        $folderFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Shared Item - Drive Clone</title>
    <link rel="stylesheet" href="assets/css/style.css?v=8">
</head>
<body>

<div class="public-share-page">
    <div class="public-share-card">

        <?php if ($file): ?>
            <h1>Shared File</h1>

            <div class="shared-file-box">
                <div class="shared-icon">📄</div>
                <h2><?php echo htmlspecialchars($file["original_name"]); ?></h2>
                <p><?php echo round($file["file_size"] / 1024, 2); ?> KB</p>

                <a class="download-btn" href="public-download.php?token=<?php echo htmlspecialchars($token); ?>">
                    Download File
                </a>
            </div>
        <?php endif; ?>

        <?php if ($folder): ?>
            <h1>Shared Folder</h1>
            <h2>📁 <?php echo htmlspecialchars($folder["folder_name"]); ?></h2>

            <div class="drive-table public-table">
                <div class="drive-table-head">
                    <div>Name</div>
                    <div>Type</div>
                    <div>Size</div>
                    <div>Created</div>
                    <div></div>
                </div>

                <?php foreach ($folderFolders as $subFolder): ?>
                    <div class="drive-row">
                        <div class="drive-name">
                            <span class="row-icon">📁</span>
                            <?php echo htmlspecialchars($subFolder["folder_name"]); ?>
                        </div>

                        <div>Folder</div>
                        <div>—</div>
                        <div><?php echo date("M d, Y", strtotime($subFolder["created_at"])); ?></div>
                        <div></div>
                    </div>
                <?php endforeach; ?>

                <?php foreach ($folderFiles as $folderFile): ?>
                    <div class="drive-row">
                        <div class="drive-name">
                            <span class="row-icon">📄</span>
                            <?php echo htmlspecialchars($folderFile["original_name"]); ?>
                        </div>

                        <div>File</div>
                        <div><?php echo round($folderFile["file_size"] / 1024, 2); ?> KB</div>
                        <div><?php echo date("M d, Y", strtotime($folderFile["created_at"])); ?></div>
                        <div>
                            <a href="public-file-download.php?id=<?php echo $folderFile["id"]; ?>&token=<?php echo htmlspecialchars($token); ?>">
                                Download
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (!$folderFolders && !$folderFiles): ?>
                    <p class="empty-message">This shared folder is empty.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!$file && !$folder): ?>
            <h2>This shared item is not available.</h2>
        <?php endif; ?>

    </div>
</div>

</body>
</html>