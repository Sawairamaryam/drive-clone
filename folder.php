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

$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}

if (!isset($_GET["id"])) {
    header("Location: dashboard.php");
    exit;
}

$folderId = intval($_GET["id"]);

$stmt = $pdo->prepare("
    SELECT * FROM folders 
    WHERE id = ? 
    AND user_id = ? 
    AND is_deleted = 0
");
$stmt->execute([$folderId, $userId]);
$currentFolder = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentFolder) {
    header("Location: dashboard.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!empty($search)) {
    $stmt = $pdo->prepare("
        SELECT * FROM folders 
        WHERE user_id = ? 
        AND parent_id = ? 
        AND is_deleted = 0
        AND folder_name LIKE ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId, $folderId, "%" . $search . "%"]);
} else {
    $stmt = $pdo->prepare("
        SELECT * FROM folders 
        WHERE user_id = ? 
        AND parent_id = ? 
        AND is_deleted = 0
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId, $folderId]);
}

$folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($search)) {
    $stmt = $pdo->prepare("
        SELECT * FROM files 
        WHERE user_id = ? 
        AND folder_id = ? 
        AND is_deleted = 0
        AND original_name LIKE ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId, $folderId, "%" . $search . "%"]);
} else {
    $stmt = $pdo->prepare("
        SELECT * FROM files 
        WHERE user_id = ? 
        AND folder_id = ? 
        AND is_deleted = 0
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId, $folderId]);
}

$files = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($currentFolder["folder_name"]); ?> - Drive</title>
    <link rel="stylesheet" href="assets/css/style.css?v=23">
</head>
<body>

<div class="google-drive-layout">

    <aside class="g-sidebar">
        <div class="g-logo">
            <span class="drive-logo"></span>
            <h2>Drive</h2>
        </div>

        <div class="new-dropdown-wrap">
            <button type="button" class="g-new-btn" onclick="toggleNewMenu()">+ New</button>

            <div class="new-dropdown" id="newDropdown">
                <button type="button" onclick="showCreateFolder()">📁 New folder</button>
                <button type="button" onclick="showUploadFile()">📄 File upload</button>
                <button type="button" onclick="showUploadMultiple()">📚 Multiple files upload</button>
                <button type="button" onclick="openFolderUploadFolder()">📂 Folder upload</button>
            </div>
        </div>

        <nav class="g-nav">
            <a href="dashboard.php">🏠 Home</a>
            <a class="active" href="dashboard.php">📁 My Drive</a>
            <a href="recent.php">🕘 Recent</a>
            <a href="starred.php">⭐ Starred</a>
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

                <input type="hidden" name="id" value="<?php echo $folderId; ?>">

                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search in this folder"
                    value="<?php echo htmlspecialchars($search); ?>"
                >

                <?php if (!empty($search)): ?>
                    <a class="clear-drive-search" href="folder.php?id=<?php echo $folderId; ?>">Clear</a>
                <?php endif; ?>
            </form>

            <div class="g-user">
                <span><?php echo htmlspecialchars(substr($user["name"], 0, 1)); ?></span>
                <a href="auth/logout.php">Logout</a>
            </div>
        </header>

        <section class="g-content-card">

            <?php if (!empty($search)): ?>
                <h1>Search results for "<?php echo htmlspecialchars($search); ?>"</h1>
            <?php else: ?>
                <h1><?php echo htmlspecialchars($currentFolder["folder_name"]); ?></h1>
            <?php endif; ?>

            <p class="breadcrumb">
                <a href="dashboard.php">My Drive</a> /
                <?php echo htmlspecialchars($currentFolder["folder_name"]); ?>
            </p>

            <div class="g-actions-row">

                <form method="POST" action="create-folder.php" class="g-inline-form drive-action-form" id="createFolderForm">
                    <input type="hidden" name="parent_id" value="<?php echo $folderId; ?>">
                    <input type="text" name="folder_name" placeholder="New folder name" required>
                    <button type="submit">Create Folder</button>
                </form>

                <form method="POST" action="upload-file.php" enctype="multipart/form-data" class="g-inline-form drive-action-form" id="uploadFileForm">
                    <input type="hidden" name="folder_id" value="<?php echo $folderId; ?>">
                    <input type="file" name="file" required>
                    <button type="submit">Upload File</button>
                </form>

                <form method="POST" action="upload-multiple.php" enctype="multipart/form-data" class="g-inline-form drive-action-form" id="uploadMultipleForm">
                    <input type="hidden" name="folder_id" value="<?php echo $folderId; ?>">
                    <input type="file" name="files[]" multiple required>
                    <button type="submit">Upload Files</button>
                </form>

                <form method="POST" action="upload-folder.php" enctype="multipart/form-data" id="uploadFolderFormFolder">
                    <input type="hidden" name="folder_id" value="<?php echo $folderId; ?>">
                    <input type="hidden" name="root_folder_name" id="rootFolderNameFolder">

                    <input 
                        type="file" 
                        name="folder_files[]" 
                        id="folderUploadInputFolder" 
                        webkitdirectory 
                        directory 
                        multiple 
                        style="display:none;"
                    >
                </form>

            </div>

            <div class="upload-progress-box" id="uploadProgressBoxFolder">
                <div class="upload-progress-text" id="uploadProgressTextFolder">
                    Uploading... 0%
                </div>

                <div class="upload-progress-line">
                    <div class="upload-progress-fill" id="uploadProgressFillFolder"></div>
                </div>
            </div>

            <div class="selected-toolbar" id="selectedToolbar">
                <button type="button" onclick="clearSelection()">✕</button>
                <span id="selectedText">1 selected</span>

                <button type="button" id="toolbarOpen">Open</button>
                <button type="button" id="toolbarPreview">Preview</button>
                <button type="button" id="toolbarDownload">Download</button>
                <button type="button" id="toolbarRename">Rename</button>
                <button type="button" id="toolbarStar">Star / Unstar</button>
                <button type="button" id="toolbarShare">Share</button>
                <button type="button" id="toolbarInfo">File information</button>
                <button type="button" id="toolbarDelete">Remove</button>
            </div>

            <div class="drive-table">

                <div class="drive-table-head">
                    <div>Name</div>
                    <div>Type</div>
                    <div>Size</div>
                    <div>Modified</div>
                    <div></div>
                </div>

                <?php foreach ($folders as $folder): ?>
                    <div 
                        class="drive-row drive-item"
                        data-type="folder"
                        data-open="folder.php?id=<?php echo $folder["id"]; ?>"
                        data-rename="rename.php?type=folder&id=<?php echo $folder["id"]; ?>"
                        data-delete="delete.php?type=folder&id=<?php echo $folder["id"]; ?>"
                        data-star="toggle-star.php?type=folder&id=<?php echo $folder["id"]; ?>&back=folder.php?id=<?php echo $folderId; ?>"
                        data-share="share-link.php?type=folder&id=<?php echo $folder["id"]; ?>"
                        data-info="info.php?type=folder&id=<?php echo $folder["id"]; ?>"
                        data-copy="make-copy.php?type=folder&id=<?php echo $folder["id"]; ?>"
                        data-move="move.php?type=folder&id=<?php echo $folder["id"]; ?>"
                    >
                        <div class="drive-name">
                            <span class="row-icon folder-icon">📁</span>

                            <?php echo htmlspecialchars($folder["folder_name"]); ?>

                            <?php if ($folder["is_starred"]): ?>
                                <span class="star-icon">⭐</span>
                            <?php endif; ?>
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
                        data-star="toggle-star.php?type=file&id=<?php echo $file["id"]; ?>&back=folder.php?id=<?php echo $folderId; ?>"
                        data-share="share-link.php?type=file&id=<?php echo $file["id"]; ?>"
                        data-info="info.php?type=file&id=<?php echo $file["id"]; ?>"
                        data-copy="make-copy.php?type=file&id=<?php echo $file["id"]; ?>"
                        data-move="move.php?type=file&id=<?php echo $file["id"]; ?>"
                    >
                        <div class="drive-name">
                            <span class="row-icon file-icon">📄</span>

                            <?php echo htmlspecialchars($file["original_name"]); ?>

                            <?php if ($file["is_starred"]): ?>
                                <span class="star-icon">⭐</span>
                            <?php endif; ?>
                        </div>

                        <div>File</div>
                        <div><?php echo round($file["file_size"] / 1024, 2); ?> KB</div>
                        <div><?php echo date("M d, Y", strtotime($file["created_at"])); ?></div>
                        <div class="three-dot">⋮</div>
                    </div>
                <?php endforeach; ?>

                <?php if (!$folders && !$files): ?>
                    <?php if (!empty($search)): ?>
                        <p class="empty-message">No result found for "<?php echo htmlspecialchars($search); ?>".</p>
                    <?php else: ?>
                        <p class="empty-message">No files or folders inside this folder.</p>
                    <?php endif; ?>
                <?php endif; ?>

            </div>

        </section>

    </main>
</div>

<div class="context-menu" id="contextMenu">
    <button type="button" id="menuOpen">Open</button>
    <button type="button" id="menuPreview">Preview</button>
    <button type="button" id="menuDownload">Download</button>
    <button type="button" id="menuRename">Rename</button>
    <button type="button" id="menuStar">Star / Unstar</button>

    <hr>

    <button type="button" id="menuShare">Share</button>
    <button type="button" id="menuMove">Move to</button>
    <button type="button" id="menuCopy">Make a copy</button>
    <button type="button" id="menuInfo">File information</button>

    <hr>

    <button type="button" id="menuDelete" class="danger">Remove</button>
</div>

<script>
const contextMenu = document.getElementById("contextMenu");
const selectedToolbar = document.getElementById("selectedToolbar");
const selectedText = document.getElementById("selectedText");

let selectedItem = null;

const menuOpen = document.getElementById("menuOpen");
const menuPreview = document.getElementById("menuPreview");
const menuDownload = document.getElementById("menuDownload");
const menuRename = document.getElementById("menuRename");
const menuStar = document.getElementById("menuStar");
const menuShare = document.getElementById("menuShare");
const menuMove = document.getElementById("menuMove");
const menuCopy = document.getElementById("menuCopy");
const menuInfo = document.getElementById("menuInfo");
const menuDelete = document.getElementById("menuDelete");

const toolbarOpen = document.getElementById("toolbarOpen");
const toolbarPreview = document.getElementById("toolbarPreview");
const toolbarDownload = document.getElementById("toolbarDownload");
const toolbarRename = document.getElementById("toolbarRename");
const toolbarStar = document.getElementById("toolbarStar");
const toolbarShare = document.getElementById("toolbarShare");
const toolbarInfo = document.getElementById("toolbarInfo");
const toolbarDelete = document.getElementById("toolbarDelete");

function selectItem(item) {
    document.querySelectorAll(".drive-item").forEach(function(row) {
        row.classList.remove("selected");
    });

    item.classList.add("selected");
    selectedItem = item;

    selectedToolbar.style.display = "flex";
    selectedText.innerText = "1 selected";

    updateActionVisibility(item);
}

function updateActionVisibility(item) {
    if (item.dataset.type === "folder") {
        menuOpen.style.display = "block";
        menuPreview.style.display = "none";
        menuDownload.style.display = "none";

        toolbarOpen.style.display = "inline-block";
        toolbarPreview.style.display = "none";
        toolbarDownload.style.display = "none";
    } else {
        menuOpen.style.display = "none";
        menuPreview.style.display = "block";
        menuDownload.style.display = "block";

        toolbarOpen.style.display = "none";
        toolbarPreview.style.display = "inline-block";
        toolbarDownload.style.display = "inline-block";
    }
}

function clearSelection() {
    document.querySelectorAll(".drive-item").forEach(function(row) {
        row.classList.remove("selected");
    });

    selectedItem = null;
    selectedToolbar.style.display = "none";
    contextMenu.style.display = "none";
}

document.querySelectorAll(".drive-item").forEach(function(item) {
    item.addEventListener("click", function(e) {
        e.stopPropagation();
        selectItem(item);
    });

    item.addEventListener("dblclick", function() {
        if (item.dataset.type === "folder") {
            window.location.href = item.dataset.open;
        }

        if (item.dataset.type === "file") {
            window.location.href = item.dataset.preview;
        }
    });

    item.addEventListener("contextmenu", function(e) {
        e.preventDefault();
        selectItem(item);

        contextMenu.style.display = "block";
        contextMenu.style.left = e.pageX + "px";
        contextMenu.style.top = e.pageY + "px";
    });
});

menuOpen.onclick = toolbarOpen.onclick = function() {
    if (selectedItem && selectedItem.dataset.open) {
        window.location.href = selectedItem.dataset.open;
    }
};

menuPreview.onclick = toolbarPreview.onclick = function() {
    if (selectedItem && selectedItem.dataset.preview) {
        window.location.href = selectedItem.dataset.preview;
    }
};

menuDownload.onclick = toolbarDownload.onclick = function() {
    if (selectedItem && selectedItem.dataset.download) {
        window.location.href = selectedItem.dataset.download;
    }
};

menuRename.onclick = toolbarRename.onclick = function() {
    if (selectedItem && selectedItem.dataset.rename) {
        window.location.href = selectedItem.dataset.rename;
    }
};

menuStar.onclick = toolbarStar.onclick = function() {
    if (selectedItem && selectedItem.dataset.star) {
        window.location.href = selectedItem.dataset.star;
    }
};

menuShare.onclick = toolbarShare.onclick = function() {
    if (selectedItem && selectedItem.dataset.share) {
        window.location.href = selectedItem.dataset.share;
    }
};

menuMove.onclick = function() {
    if (selectedItem && selectedItem.dataset.move) {
        window.location.href = selectedItem.dataset.move;
    }
};

menuCopy.onclick = function() {
    if (selectedItem && selectedItem.dataset.copy) {
        window.location.href = selectedItem.dataset.copy;
    }
};

menuInfo.onclick = toolbarInfo.onclick = function() {
    if (selectedItem && selectedItem.dataset.info) {
        window.location.href = selectedItem.dataset.info;
    }
};

menuDelete.onclick = toolbarDelete.onclick = function() {
    if (selectedItem && selectedItem.dataset.delete) {
        if (confirm("Move this item to Trash?")) {
            window.location.href = selectedItem.dataset.delete;
        }
    }
};

function toggleNewMenu() {
    const dropdown = document.getElementById("newDropdown");

    if (dropdown.style.display === "block") {
        dropdown.style.display = "none";
    } else {
        dropdown.style.display = "block";
    }
}

function hideAllDriveForms() {
    document.getElementById("createFolderForm").style.display = "none";
    document.getElementById("uploadFileForm").style.display = "none";
    document.getElementById("uploadMultipleForm").style.display = "none";
}

function showCreateFolder() {
    hideAllDriveForms();
    document.getElementById("createFolderForm").style.display = "flex";
    document.getElementById("newDropdown").style.display = "none";
}

function showUploadFile() {
    hideAllDriveForms();
    document.getElementById("uploadFileForm").style.display = "flex";
    document.getElementById("newDropdown").style.display = "none";
}

function showUploadMultiple() {
    hideAllDriveForms();
    document.getElementById("uploadMultipleForm").style.display = "flex";
    document.getElementById("newDropdown").style.display = "none";
}

function openFolderUploadFolder() {
    const input = document.getElementById("folderUploadInputFolder");
    const dropdown = document.getElementById("newDropdown");

    if (dropdown) {
        dropdown.style.display = "none";
    }

    if (input) {
        input.click();
    }
}

const folderInputFolder = document.getElementById("folderUploadInputFolder");
const rootFolderNameFolder = document.getElementById("rootFolderNameFolder");
const uploadFolderFormFolder = document.getElementById("uploadFolderFormFolder");

const progressBoxFolder = document.getElementById("uploadProgressBoxFolder");
const progressTextFolder = document.getElementById("uploadProgressTextFolder");
const progressFillFolder = document.getElementById("uploadProgressFillFolder");

if (folderInputFolder && rootFolderNameFolder && uploadFolderFormFolder) {
    folderInputFolder.addEventListener("change", function() {
        if (this.files.length > 0) {
            const firstFile = this.files[0];

            if (firstFile.webkitRelativePath) {
                rootFolderNameFolder.value = firstFile.webkitRelativePath.split("/")[0];
            } else {
                rootFolderNameFolder.value = "Uploaded Folder";
            }

            const formData = new FormData(uploadFolderFormFolder);
            const xhr = new XMLHttpRequest();

            progressBoxFolder.style.display = "block";
            progressTextFolder.innerText = "Uploading... 0%";
            progressFillFolder.style.width = "0%";

            xhr.upload.addEventListener("progress", function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);

                    progressTextFolder.innerText = "Uploading... " + percent + "%";
                    progressFillFolder.style.width = percent + "%";
                }
            });

            xhr.onload = function() {
                if (xhr.status === 200) {
                    progressTextFolder.innerText = "Upload complete. Loading folder...";
                    progressFillFolder.style.width = "100%";

                    setTimeout(function() {
                        window.location.href = "folder.php?id=<?php echo $folderId; ?>";
                    }, 700);
                } else {
                    progressTextFolder.innerText = "Upload failed. Please try again.";
                }
            };

            xhr.onerror = function() {
                progressTextFolder.innerText = "Upload failed. Check your connection or file size.";
            };

            xhr.open("POST", "upload-folder.php", true);
            xhr.send(formData);
        }
    });
}

document.addEventListener("click", function(e) {
    contextMenu.style.display = "none";

    const dropdown = document.getElementById("newDropdown");
    const newButton = document.querySelector(".g-new-btn");

    if (dropdown && newButton && !dropdown.contains(e.target) && !newButton.contains(e.target)) {
        dropdown.style.display = "none";
    }
});
</script>

</body>
</html>