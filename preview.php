<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

if (!isset($_GET["id"])) {
    header("Location: dashboard.php");
    exit;
}

$userId = $_SESSION["user_id"];
$fileId = intval($_GET["id"]);

$stmt = $pdo->prepare("
    SELECT * FROM files 
    WHERE id = ? 
    AND user_id = ? 
    AND is_deleted = 0
");
$stmt->execute([$fileId, $userId]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    die("File not found.");
}

$filePath = "uploads/user_files/" . $file["stored_name"];
$extension = strtolower(pathinfo($file["original_name"], PATHINFO_EXTENSION));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Preview - <?php echo htmlspecialchars($file["original_name"]); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="preview-page">
    <div class="preview-header">
        <a href="dashboard.php">← Back to Drive</a>
        <h2><?php echo htmlspecialchars($file["original_name"]); ?></h2>
        <a class="download-btn" href="download.php?id=<?php echo $file["id"]; ?>">Download</a>
    </div>

    <div class="preview-box">
        <?php if (in_array($extension, ["jpg", "jpeg", "png", "gif", "webp"])): ?>

            <img src="<?php echo htmlspecialchars($filePath); ?>" class="preview-image">

        <?php elseif ($extension === "pdf"): ?>

            <iframe src="<?php echo htmlspecialchars($filePath); ?>" class="preview-frame"></iframe>

        <?php elseif (in_array($extension, ["txt", "php", "html", "css", "js"])): ?>

            <pre><?php echo htmlspecialchars(file_get_contents($filePath)); ?></pre>

        <?php else: ?>

            <div class="no-preview">
                <h2>Preview not available</h2>
                <p>This file type cannot be previewed in browser.</p>
                <a class="download-btn" href="download.php?id=<?php echo $file["id"]; ?>">Download File</a>
            </div>

        <?php endif; ?>
    </div>
</div>

</body>
</html>