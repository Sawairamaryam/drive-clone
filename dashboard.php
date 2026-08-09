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

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!empty($search)) {
    $stmt = $pdo->prepare("
        SELECT * FROM folders 
        WHERE user_id = ? 
        AND parent_id IS NULL 
        AND is_deleted = 0
        AND folder_name LIKE ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId, "%" . $search . "%"]);
} else {
    $stmt = $pdo->prepare("
        SELECT * FROM folders 
        WHERE user_id = ? 
        AND parent_id IS NULL 
        AND is_deleted = 0
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
}

$folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($search)) {
    $stmt = $pdo->prepare("
        SELECT * FROM files 
        WHERE user_id = ? 
        AND folder_id IS NULL 
        AND is_deleted = 0
        AND original_name LIKE ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId, "%" . $search . "%"]);
} else {
    $stmt = $pdo->prepare("
        SELECT * FROM files 
        WHERE user_id = ? 
        AND folder_id IS NULL 
        AND is_deleted = 0
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
}

$files = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Drive</title>
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
                <button type="button" onclick="openFolderUploadDashboard()">📂 Folder upload</button>
            </div>
        </div>

        <nav class="g-nav">
            <a class="active" href="dashboard.php">🏠 Home</a>
            <a href="dashboard.php">📁 My Drive</a>
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

                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search in Drive"
                    value="<?php echo htmlspecialchars($search); ?>"
                >

                <?php if (!empty($search)): ?>
                    <a class="clear-drive-search" href="dashboard.php">Clear</a>
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
                <h1>Welcome to Drive</h1>
            <?php endif; ?>

            <div class="g-actions-row">

                <form method="POST" action="create-folder.php" class="g-inline-form drive-action-form" id="createFolderForm">
                    <input type="text" name="folder_name" placeholder="New folder name" required>
                    <button type="submit">Create Folder</button>
                </form>

                <form method="POST" action="upload-file.php" enctype="multipart/form-data" class="g-inline-form drive-action-form" id="uploadFileForm">
                    <input type="file" name="file" required>
                    <button type="submit">Upload File</button>
                </form>

                <form method="POST" action="upload-multiple.php" enctype="multipart/form-data" class="g-inline-form drive-action-form" id="uploadMultipleForm">
                    <input type="file" name="files[]" multiple required>
                    <button type="submit">Upload Files</button>
                </form>

                <form method="POST" action="upload-folder.php" enctype="multipart/form-data" id="uploadFolderFormDashboard">
                    <input type="hidden" name="root_folder_name" id="rootFolderNameDashboard">

                    <input 
                        type="file" 
                        name="folder_files[]" 
                        id="folderUploadInputDashboard" 
                        webkitdirectory 
                        directory 
                        multiple 
                        style="display:none;"
                    >
                </form>

            </div>

            <div class="upload-progress-box" id="uploadProgressBoxDashboard">
                <div class="upload-progress-text" id="uploadProgressTextDashboard">
                    Uploading... 0%
                </div>

                <div class="upload-progress-line">
                    <div class="upload-progress-fill" id="uploadProgressFillDashboard"></div>
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
                        data-star="toggle-star.php?type=folder&id=<?php echo $folder["id"]; ?>&back=dashboard.php"
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
                        data-star="toggle-star.php?type=file&id=<?php echo $file["id"]; ?>&back=dashboard.php"
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
                        <p class="empty-message">No files or folders yet.</p>
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

function openFolderUploadDashboard() {
    const input = document.getElementById("folderUploadInputDashboard");
    const dropdown = document.getElementById("newDropdown");

    if (dropdown) {
        dropdown.style.display = "none";
    }

    if (input) {
        input.click();
    }
}

const folderInputDashboard = document.getElementById("folderUploadInputDashboard");
const rootFolderNameDashboard = document.getElementById("rootFolderNameDashboard");
const uploadFolderFormDashboard = document.getElementById("uploadFolderFormDashboard");

const progressBoxDashboard = document.getElementById("uploadProgressBoxDashboard");
const progressTextDashboard = document.getElementById("uploadProgressTextDashboard");
const progressFillDashboard = document.getElementById("uploadProgressFillDashboard");

if (folderInputDashboard && rootFolderNameDashboard && uploadFolderFormDashboard) {
    folderInputDashboard.addEventListener("change", function() {
        if (this.files.length > 0) {
            const firstFile = this.files[0];

            if (firstFile.webkitRelativePath) {
                rootFolderNameDashboard.value = firstFile.webkitRelativePath.split("/")[0];
            } else {
                rootFolderNameDashboard.value = "Uploaded Folder";
            }

            const formData = new FormData(uploadFolderFormDashboard);
            const xhr = new XMLHttpRequest();

            progressBoxDashboard.style.display = "block";
            progressTextDashboard.innerText = "Uploading... 0%";
            progressFillDashboard.style.width = "0%";

            xhr.upload.addEventListener("progress", function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);

                    progressTextDashboard.innerText = "Uploading... " + percent + "%";
                    progressFillDashboard.style.width = percent + "%";
                }
            });

            xhr.onload = function() {
                if (xhr.status === 200) {
                    progressTextDashboard.innerText = "Upload complete. Loading Drive...";
                    progressFillDashboard.style.width = "100%";

                    setTimeout(function() {
                        window.location.href = "dashboard.php";
                    }, 700);
                } else {
                    progressTextDashboard.innerText = "Upload failed. Please try again.";
                }
            };

            xhr.onerror = function() {
                progressTextDashboard.innerText = "Upload failed. Check your connection or file size.";
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