<div class="context-menu" id="contextMenu">
    <button type="button" id="menuOpen">Open</button>
    <button type="button" id="menuPreview">Preview</button>
    <button type="button" id="menuDownload">Download</button>
    <button type="button" id="menuRename">Rename</button>
    <button type="button" id="menuStar">Star / Unstar</button>
    <hr>
    <button type="button" disabled>Share</button>
    <button type="button" disabled>Make a copy</button>
    <button type="button" disabled>File information</button>
    <hr>
    <button type="button" id="menuDelete" class="danger">Remove</button>
</div>

<script>
const contextMenu = document.getElementById("contextMenu");
let selectedItem = null;

const menuOpen = document.getElementById("menuOpen");
const menuPreview = document.getElementById("menuPreview");
const menuDownload = document.getElementById("menuDownload");
const menuRename = document.getElementById("menuRename");
const menuStar = document.getElementById("menuStar");
const menuDelete = document.getElementById("menuDelete");

function selectItem(item) {
    document.querySelectorAll(".drive-item").forEach(function(row) {
        row.classList.remove("selected");
    });

    item.classList.add("selected");
    selectedItem = item;

    if (item.dataset.type === "folder") {
        menuOpen.style.display = "block";
        menuPreview.style.display = "none";
        menuDownload.style.display = "none";
    } else {
        menuOpen.style.display = "none";
        menuPreview.style.display = "block";
        menuDownload.style.display = "block";
    }
}

document.querySelectorAll(".drive-item").forEach(function(item) {
    item.addEventListener("click", function(e) {
        e.stopPropagation();
        selectItem(item);
    });

    item.addEventListener("dblclick", function() {
        if (item.dataset.type === "folder" && item.dataset.open) {
            window.location.href = item.dataset.open;
        }

        if (item.dataset.type === "file" && item.dataset.preview) {
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

menuOpen.onclick = function() {
    if (selectedItem && selectedItem.dataset.open) {
        window.location.href = selectedItem.dataset.open;
    }
};

menuPreview.onclick = function() {
    if (selectedItem && selectedItem.dataset.preview) {
        window.location.href = selectedItem.dataset.preview;
    }
};

menuDownload.onclick = function() {
    if (selectedItem && selectedItem.dataset.download) {
        window.location.href = selectedItem.dataset.download;
    }
};

menuRename.onclick = function() {
    if (selectedItem && selectedItem.dataset.rename) {
        window.location.href = selectedItem.dataset.rename;
    }
};

menuStar.onclick = function() {
    if (selectedItem && selectedItem.dataset.star) {
        window.location.href = selectedItem.dataset.star;
    }
};

menuDelete.onclick = function() {
    if (selectedItem && selectedItem.dataset.delete) {
        if (confirm("Move this item to Trash?")) {
            window.location.href = selectedItem.dataset.delete;
        }
    }
};

document.addEventListener("click", function() {
    contextMenu.style.display = "none";
});
</script>