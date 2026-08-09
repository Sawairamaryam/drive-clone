<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

$userId = $_SESSION["user_id"];

if (!isset($_GET["type"]) || !isset($_GET["id"])) {
    header("Location: dashboard.php");
    exit;
}

$type = $_GET["type"];
$id = intval($_GET["id"]);

if ($type !== "file" && $type !== "folder") {
    header("Location: dashboard.php");
    exit;
}

$message = "";
$item = null;
$backLink = "dashboard.php";

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

    if ($item["parent_id"]) {
        $backLink = "folder.php?id=" . $item["parent_id"];
    }

    $currentName = $item["folder_name"];
}

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

    if ($item["folder_id"]) {
        $backLink = "folder.php?id=" . $item["folder_id"];
    }

    $currentName = $item["original_name"];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newName = trim($_POST["new_name"]);

    if (empty($newName)) {
        $message = "Name cannot be empty.";
    } else {
        if ($type === "folder") {
            $stmt = $pdo->prepare("
                UPDATE folders 
                SET folder_name = ? 
                WHERE id = ? 
                AND user_id = ?
            ");
            $stmt->execute([$newName, $id, $userId]);
        }

        if ($type === "file") {
            $stmt = $pdo->prepare("
                UPDATE files 
                SET original_name = ? 
                WHERE id = ? 
                AND user_id = ?
            ");
            $stmt->execute([$newName, $id, $userId]);
        }

        header("Location: " . $backLink);
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Rename - Drive Clone</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">

<div class="auth-card">
    <h1>Rename <?php echo ucfirst($type); ?></h1>
    <p>Update the name of your <?php echo htmlspecialchars($type); ?>.</p>

    <?php if (!empty($message)): ?>
        <p class="error-message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>New Name</label>
            <input 
                type="text" 
                name="new_name" 
                value="<?php echo htmlspecialchars($currentName); ?>" 
                required
            >
        </div>

        <button type="submit" class="btn">Rename</button>
    </form>

    <p class="auth-link">
        <a href="<?php echo htmlspecialchars($backLink); ?>">Back to Drive</a>
    </p>
</div>

</body>
</html>