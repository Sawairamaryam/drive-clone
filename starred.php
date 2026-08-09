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

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT * FROM folders 
    WHERE user_id = ? 
    AND is_deleted = 0
    AND is_starred = 1
    ORDER BY created_at DESC
");
$stmt->execute([$userId]);
$folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT * FROM files 
    WHERE user_id = ? 
    AND is_deleted = 0
    AND is_starred = 1
    ORDER BY created_at DESC
");
$stmt->execute([$userId]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Starred - Drive Clone</title>
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
            <a class="active" href="starred.php">⭐ Starred</a>
            <a href="trash.php">🗑 Trash</a>
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
            <form method="GET" class="g-search">
                🔍
                <input type="text" placeholder="Search in Starred" disabled>
            </form>

            <div class="g-user">
                <span><?php echo htmlspecialchars(substr($user["name"], 0, 1)); ?></span>
                <a href="auth/logout.php">Logout</a>
            </div>
        </header>

        <section class="g-content-card">
            <h1>Starred</h1>

            <div class="drive-table">

                <div class="drive-table-head">
                    <div>Name</div>
                    <div>Type</div>
                    <div>Size</div>
                    <div>Created</div>
                    <div></div>
                </div>

                <?php foreach ($folders as $folder): ?>
                    <div 
                        class="drive-row drive-item"
                        data-type="folder"
                        data-open="folder.php?id=<?php echo $folder["id"]; ?>"
                        data-rename="rename.php?type=folder&id=<?php echo $folder["id"]; ?>"
                        data-delete="delete.php?type=folder&id=<?php echo $folder["id"]; ?>"
                        data-star="toggle-star.php?type=folder&id=<?php echo $folder["id"]; ?>&back=starred.php"
                        data-share="share-link.php?type=folder&id=<?php echo $folder["id"]; ?>"
                        data-info="info.php?type=folder&id=<?php echo $folder["id"]; ?>"
                        data-copy="make-copy.php?type=folder&id=<?php echo $folder["id"]; ?>"
                    >
                        <div class="drive-name">
                            <span class="row-icon folder-icon">📁</span>
                            <?php echo htmlspecialchars($folder["folder_name"]); ?>
                            <span class="star-icon">⭐</span>
                        </div>

                        <div>Folder</div>
                        <div>—</div>
                        <div><?php echo date("M d, Y", strtotime($folder["created_at"])); ?></div>
                        <div class="three-dot">⋮</div>
                    </div>
                <?php endforeach; ?>

                <?php foreach ($files as $file): ?>
                    <div 
                        class="drive-row drive-item"
                        data-type="file"
                        data-preview="preview.php?id=<?php echo $file["id"]; ?>"
                        data-download="download.php?id=<?php echo $file["id"]; ?>"
                        data-rename="rename.php?type=file&id=<?php echo $file["id"]; ?>"
                        data-delete="delete.php?type=file&id=<?php echo $file["id"]; ?>"
                        data-star="toggle-star.php?type=file&id=<?php echo $file["id"]; ?>&back=starred.php"
                        data-share="share-link.php?type=file&id=<?php echo $file["id"]; ?>"
                        data-info="info.php?type=file&id=<?php echo $file["id"]; ?>"
                        data-copy="make-copy.php?type=file&id=<?php echo $file["id"]; ?>"
                    >
                        <div class="drive-name">
                            <span class="row-icon file-icon">📄</span>
                            <?php echo htmlspecialchars($file["original_name"]); ?>
                            <span class="star-icon">⭐</span>
                        </div>

                        <div>File</div>
                        <div><?php echo round($file["file_size"] / 1024, 2); ?> KB</div>
                        <div><?php echo date("M d, Y", strtotime($file["created_at"])); ?></div>
                        <div class="three-dot">⋮</div>
                    </div>
                <?php endforeach; ?>

                <?php if (!$folders && !$files): ?>
                    <p class="empty-message">No starred items yet.</p>
                <?php endif; ?>

            </div>
        </section>

    </main>
</div>

<?php include 'drive-menu.php'; ?>

</body>
</html>