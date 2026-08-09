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

if ($type !== "file" && $type !== "folder") {
    header("Location: dashboard.php");
    exit;
}

$itemName = "";
$currentFolderId = null;
$backLink = "dashboard.php";
$message = "";

/* Get current item */
if ($type === "file") {
    $stmt = $pdo->prepare("
        SELECT * FROM files
        WHERE id = ?
        AND user_id = ?
        AND is_deleted = 0
    ");
    $stmt->execute([$id, $userId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        header("Location: dashboard.php");
        exit;
    }

    $itemName = $item["original_name"];
    $currentFolderId = $item["folder_id"];

    if (!empty($currentFolderId)) {
        $backLink = "folder.php?id=" . $currentFolderId;
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
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        header("Location: dashboard.php");
        exit;
    }

    $itemName = $item["folder_name"];
    $currentFolderId = $item["parent_id"];

    if (!empty($currentFolderId)) {
        $backLink = "folder.php?id=" . $currentFolderId;
    }
}

/* Fetch destination folders */
$stmt = $pdo->prepare("
    SELECT * FROM folders
    WHERE user_id = ?
    AND is_deleted = 0
    ORDER BY folder_name ASC
");
$stmt->execute([$userId]);
$folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Move item */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $destination = $_POST["destination"] ?? "";

    $newFolderId = null;

    if ($destination !== "root") {
        $newFolderId = intval($destination);
    }

    if ($type === "folder") {
        if ($newFolderId === $id) {
            $message = "You cannot move a folder inside itself.";
        } else {
            $stmt = $pdo->prepare("
                UPDATE folders
                SET parent_id = ?
                WHERE id = ?
                AND user_id = ?
            ");
            $stmt->execute([$newFolderId, $id, $userId]);

            if ($newFolderId) {
                header("Location: folder.php?id=" . $newFolderId);
                exit;
            } else {
                header("Location: dashboard.php");
                exit;
            }
        }
    }

    if ($type === "file") {
        $stmt = $pdo->prepare("
            UPDATE files
            SET folder_id = ?
            WHERE id = ?
            AND user_id = ?
        ");
        $stmt->execute([$newFolderId, $id, $userId]);

        if ($newFolderId) {
            header("Location: folder.php?id=" . $newFolderId);
            exit;
        } else {
            header("Location: dashboard.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Move - Drive Clone</title>
    <link rel="stylesheet" href="assets/css/style.css?v=16">
</head>
<body class="auth-page">

<div class="auth-card move-card">
    <h1>Move to folder</h1>

    <p>Move this <?php echo htmlspecialchars($type); ?>:</p>

    <h3>
        <?php echo $type === "folder" ? "📁" : "📄"; ?>
        <?php echo htmlspecialchars($itemName); ?>
    </h3>

    <?php if (!empty($message)): ?>
        <p class="error-message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Select destination</label>

            <select name="destination" required>
                <option value="root">My Drive</option>

                <?php foreach ($folders as $folder): ?>
                    <?php if ($type === "folder" && $folder["id"] == $id): ?>
                        <?php continue; ?>
                    <?php endif; ?>

                    <option 
                        value="<?php echo $folder["id"]; ?>"
                        <?php echo ($currentFolderId == $folder["id"]) ? "selected" : ""; ?>
                    >
                        <?php echo htmlspecialchars($folder["folder_name"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn">Move</button>
    </form>

    <p class="auth-link">
        <a href="<?php echo htmlspecialchars($backLink); ?>">Back to Drive</a>
    </p>
</div>

</body>
</html>