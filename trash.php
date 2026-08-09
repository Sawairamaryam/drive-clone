<?php
session_start();
require 'config/db.php';
require 'storage-helper.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

$userId = $_SESSION["user_id"];
$storageInfo = getStorageInfo($pdo, $userId);

$stmt = $pdo->prepare("
    SELECT * FROM folders 
    WHERE user_id = ? 
    AND is_deleted = 1
    ORDER BY deleted_at DESC
");
$stmt->execute([$userId]);
$folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT * FROM files 
    WHERE user_id = ? 
    AND is_deleted = 1
    ORDER BY deleted_at DESC
");
$stmt->execute([$userId]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Trash - Drive Clone</title>
    <link rel="stylesheet" href="assets/css/style.css?v=14">
</head>
<body>

<div class="google-drive-layout">

    <aside class="g-sidebar">
        <div class="g-logo">
            <span class="drive-logo"></span>
            <h2>Drive</h2>
        </div>

        <div class="new-dropdown-wrap">
            <button type="button" class="g-new-btn">+ New</button>
        </div>

        <nav class="g-nav">
            <a href="dashboard.php">🏠 Home</a>
            <a href="dashboard.php">📁 My Drive</a>
            <a href="recent.php">🕘 Recent</a>
            <a href="starred.php">⭐ Starred</a>
            <a class="active" href="trash.php">🗑 Trash</a>
            <a href="#">☁ Storage</a>
        </nav>

        <div class="g-storage">
            <div class="storage-line">
                <span style="width: <?php echo $storageInfo["percentage"]; ?>%;"></span>
            </div>

            <p>
                <?php echo $storageInfo["used_formatted"]; ?> of 
                <?php echo $storageInfo["limit_formatted"]; ?> used
            </p>
        </div>
    </aside>

    <main class="g-main">

        <header class="g-topbar">
            <div class="g-search">
                🔍
                <input type="text" placeholder="Search in Trash" disabled>
            </div>

            <div class="g-user">
                <a href="auth/logout.php">Logout</a>
            </div>
        </header>

        <section class="g-content-card">

            <h1>Trash</h1>
            <p class="trash-note">Items in Trash can be restored or permanently deleted.</p>

            <div class="drive-table">

                <div class="drive-table-head">
                    <div>Name</div>
                    <div>Type</div>
                    <div>Size</div>
                    <div>Deleted At</div>
                    <div>Actions</div>
                </div>

                <?php foreach ($folders as $folder): ?>
                    <div class="drive-row">
                        <div class="drive-name">
                            <span class="row-icon folder-icon">📁</span>
                            <?php echo htmlspecialchars($folder["folder_name"]); ?>
                        </div>

                        <div>Folder</div>
                        <div>—</div>

                        <div>
                            <?php echo $folder["deleted_at"] ? date("M d, Y", strtotime($folder["deleted_at"])) : "—"; ?>
                        </div>

                        <div class="trash-actions">
                            <a href="restore.php?type=folder&id=<?php echo $folder["id"]; ?>">Restore</a>

                            <a 
                                class="danger-link" 
                                href="permanent-delete.php?type=folder&id=<?php echo $folder["id"]; ?>"
                                onclick="return confirm('Delete this folder forever?');"
                            >
                                Delete Forever
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php foreach ($files as $file): ?>
                    <div class="drive-row">
                        <div class="drive-name">
                            <span class="row-icon file-icon">📄</span>
                            <?php echo htmlspecialchars($file["original_name"]); ?>
                        </div>

                        <div>File</div>
                        <div><?php echo round($file["file_size"] / 1024, 2); ?> KB</div>

                        <div>
                            <?php echo $file["deleted_at"] ? date("M d, Y", strtotime($file["deleted_at"])) : "—"; ?>
                        </div>

                        <div class="trash-actions">
                            <a href="restore.php?type=file&id=<?php echo $file["id"]; ?>">Restore</a>

                            <a 
                                class="danger-link" 
                                href="permanent-delete.php?type=file&id=<?php echo $file["id"]; ?>"
                                onclick="return confirm('Delete this file forever?');"
                            >
                                Delete Forever
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (!$folders && !$files): ?>
                    <p class="empty-message">Trash is empty.</p>
                <?php endif; ?>

            </div>

        </section>

    </main>
</div>

</body>
</html>