<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Not logged in'); window.location.href='login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Get current folder path
$current_folder = isset($_GET['folder']) ? $_GET['folder'] : '';
$base_path = "files/$user_id" . ($current_folder ? "/$current_folder" : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Files</title>
<link rel="stylesheet" href="styles.css">
<style>
.more-options {
    float: right;
    cursor: pointer;
    font-size: 20px;
    padding: 0 10px;
    user-select: none;
}
.file-options {
    display: none;
    position: absolute;
    background: white;
    border: 1px solid #ddd;
    border-radius: 6px;
    box-shadow: 0px 2px 10px rgba(0,0,0,0.1);
    z-index: 10;
    padding: 5px 0;
    min-width: 120px;
}
.file-options a {
    display: block;
    padding: 8px 15px;
    text-decoration: none;
    color: #333;
    font-size: 14px;
}
.file-options a:hover {
    background: #f0f0f0;
}
.file-item {
    position: relative;
    margin-bottom: 10px;
}
</style>
</head>
<body>
<div class="wrapper">
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>GDRIVE</h2>
        <ul>
            <li><a href="dashboard.php">🏠 Dashboard</a></li>
            <li><a href="my_files.php">📁 My Files</a></li>
            <li><a href="recent.php">🕓 Recent Files</a></li>
            <li><a href="trash.php">🗑️ Trash</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="topbar">
            <div class="profile">
                <img src="">
            </div>
        </div>

        <div class="content-area">
            <h3>Current Folder: <?= $current_folder ?: 'Root' ?></h3>
            <?php if ($current_folder): ?>
                <p><a href="my_files.php?folder=<?= urlencode(dirname($current_folder)) ?>">⬅ Back</a></p>
            <?php endif; ?>

            <!-- Upload File -->
            <form class="upload-form" action="upload.php<?= $current_folder ? '?folder=' . urlencode($current_folder) : '' ?>" method="post" enctype="multipart/form-data" style="margin-bottom:15px;">
                <input type="file" name="file" required>
                <button type="submit">Upload File</button>
            </form>

            <!-- Create Folder -->
            <form class="folder-form" action="upload.php<?= $current_folder ? '?folder=' . urlencode($current_folder) : '' ?>" method="post" style="margin-bottom:20px;">
                <input type="text" name="new_folder" placeholder="New Folder Name" required>
                <button type="submit">Create Folder</button>
            </form>

            <!-- Subfolders -->
            <h4>Folders:</h4>
            <?php
            if (is_dir($base_path)) {
                $folders = array_filter(glob($base_path . '/*'), 'is_dir');
                if (!empty($folders)) {
                    foreach ($folders as $folder_path) {
                        $folder_name = basename($folder_path);
                        $full_path = $current_folder ? $current_folder . '/' . $folder_name : $folder_name;
                        echo "<div class='file-item'>📁 <a href='my_files.php?folder=" . urlencode($full_path) . "'>$folder_name</a>
                            <div class='more-options' onclick=\"toggleMenu('menu-folder-$folder_name')\">⋮</div>
                            <div class='file-options' id='menu-folder-$folder_name'>
                                <a href='rename_folder.php?path=" . urlencode($full_path) . "'>Rename</a>
                                <a href='delete_folder.php?path=" . urlencode($full_path) . "' onclick=\"return confirm('Delete this folder and its contents?')\">Delete</a>
                            </div>
                        </div>";
                    }
                } else {
                    echo "<p>No folders found</p>";
                }
            }
            ?>

            <!-- Files -->
            <h4>Files:</h4>
            <?php
            if (is_dir($base_path)) {
                $files = array_filter(glob($base_path . '/*'), 'is_file');
                if (!empty($files)) {
                    foreach ($files as $file_path) {
                        $file_name = basename($file_path);
                        echo "<div class='file-item'>📄 <a href='$file_path' target='_blank'>$file_name</a>
                            <div class='more-options' onclick=\"toggleMenu('menu-file-$file_name')\">⋮</div>
                            <div class='file-options' id='menu-file-$file_name'>
                                <a href='rename_file.php?path=" . urlencode(($current_folder ? $current_folder . '/' : '') . $file_name) . "'>Rename</a>
                                <a href='delete_file.php?path=" . urlencode(($current_folder ? $current_folder . '/' : '') . $file_name) . "' onclick=\"return confirm('Delete this file?')\">Delete</a>
                            </div>
                        </div>";
                    }
                } else {
                    echo "<p>No files found</p>";
                }
            }
            ?>
        </div>
    </div>
</div>

<script>
function toggleMenu(id) {
    document.querySelectorAll('.file-options').forEach(menu => {
        if (menu.id !== id) menu.style.display = 'none';
    });
    const el = document.getElementById(id);
    el.style.display = (el.style.display === 'block') ? 'none' : 'block';
}
window.onclick = function(event) {
    if (!event.target.matches('.more-options')) {
        document.querySelectorAll('.file-options').forEach(menu => menu.style.display = 'none');
    }
}
</script>
</body>
</html>
