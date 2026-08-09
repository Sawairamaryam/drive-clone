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

$fileId = null;
$folderId = null;
$itemName = "";
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

    $fileId = $id;
    $itemName = $file["original_name"];

    if (!empty($file["folder_id"])) {
        $backLink = "folder.php?id=" . $file["folder_id"];
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

    $folderId = $id;
    $itemName = $folder["folder_name"];

    if (!empty($folder["parent_id"])) {
        $backLink = "folder.php?id=" . $folder["parent_id"];
    }
}

$stmt = $pdo->prepare("
    SELECT * FROM shared_links
    WHERE user_id = ?
    AND (
        (file_id IS NOT NULL AND file_id = ?)
        OR
        (folder_id IS NOT NULL AND folder_id = ?)
    )
    LIMIT 1
");
$stmt->execute([$userId, $fileId, $folderId]);
$existingLink = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existingLink) {
    $token = $existingLink["share_token"];
} else {
    $token = bin2hex(random_bytes(24));

    $stmt = $pdo->prepare("
        INSERT INTO shared_links (user_id, file_id, folder_id, share_token)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $fileId, $folderId, $token]);
}

$shareUrl = "http://localhost/drive_clone/public-share.php?token=" . $token;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Share Link - Drive Clone</title>
    <link rel="stylesheet" href="assets/css/style.css?v=8">
</head>
<body class="auth-page">

<div class="auth-card share-card">
    <h1>Share Link</h1>
    <p>Public link generated for:</p>

    <h3><?php echo htmlspecialchars($itemName); ?></h3>

    <div class="share-link-box">
        <input 
            type="text" 
            value="<?php echo htmlspecialchars($shareUrl); ?>" 
            id="shareLinkInput" 
            readonly
        >

        <button type="button" onclick="copyShareLink()">Copy</button>
    </div>

    <p class="auth-link">
        <a href="<?php echo htmlspecialchars($backLink); ?>">Back to Drive</a>
    </p>
</div>

<script>
function copyShareLink() {
    const input = document.getElementById("shareLinkInput");
    input.select();
    input.setSelectionRange(0, 99999);
    document.execCommand("copy");
    alert("Share link copied!");
}
</script>

</body>
</html>