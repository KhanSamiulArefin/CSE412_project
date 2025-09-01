<?php
require 'db.php';
session_start();

// Set Bangladesh timezone
date_default_timezone_set('Asia/Dhaka');

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Not logged in'); window.location.href='login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$current_folder = isset($_GET['folder']) ? $_GET['folder'] : '';
$base_path = "files/$user_id" . ($current_folder ? "/$current_folder" : '');
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Back link fix (handles going from a top-level folder back to root)
$parent_folder = dirname($current_folder);
if ($parent_folder === '.' || $parent_folder === '/' || $parent_folder === '\\') {
    $parent_link = 'my_files.php';
} else {
    $parent_link = 'my_files.php?folder=' . urlencode($parent_folder);
}

function getFileTypeIcon($file_name) {
    $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $icons = [
        'pdf' => 'pdf-icon.png',
        'doc' => 'word-icon.png',
        'docx' => 'word-icon.png',
        'txt' => 'text-icon.png',
        'jpg' => 'image-icon.png',
        'jpeg' => 'image-icon.png',
        'png' => 'image-icon.png',
        'gif' => 'image-icon.png',
        'mp3' => 'audio-icon.png',
        'wav' => 'audio-icon.png',
        'mp4' => 'video-icon.png',
        'mov' => 'video-icon.png',
    ];
    return isset($icons[$extension]) ? $icons[$extension] : 'file-icon.png';
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function getBangladeshTime($timestamp) {
    $dt = new DateTime("@$timestamp");
    $dt->setTimezone(new DateTimeZone('Asia/Dhaka'));
    return $dt->format("F d Y H:i:s");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Files - GDRIVE</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Import Montserrat only for sidebar text formatting */
        @import url('https://fonts.googleapis.com/css?family=Montserrat:400,800');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
        }
        
        .wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* ====== SIDEBAR: follow your provided sidebar color & text formatting ====== */
        .sidebar {
            background-color: #e7d6ff;   /* your color */
            width: 220px;                 /* your width */
            padding: 20px;                /* your padding */
            height: 100vh;                /* full height */
            position: fixed;              /* fixed like your spec */
            top: 0;
            left: 0;
            overflow-y: auto;             /* scrollable like your spec */
            z-index: 1;
            font-family: 'Montserrat', sans-serif; /* your font on sidebar */
            color: #333;                  /* link/text base color */
        }

        .sidebar h2 {
            color: #430457;               /* your heading color */
            margin-bottom: 30px;          /* your spacing */
            /* (no text-align here to match your spec's default left alignment) */
        }

        .sidebar ul {
            list-style-type: none;        /* your list reset */
            padding: 0;                   /* your list reset */
        }

        .sidebar ul li a {
            text-decoration: none;        /* your spec */
            color: #333;                  /* your spec */
            display: block;               /* your spec */
            padding: 10px;                /* your spec */
            border-radius: 8px;           /* your spec */
            transition: background-color 0.2s ease;
        }

        .sidebar ul li a:hover {
            background-color: #d1bbfc;    /* your hover color */
        }
        /* ====== END SIDEBAR CHANGES ====== */

        /* ====== TOPBAR: follow your provided topbar color & position ====== */
        .topbar {
            background-color: #430457;    /* your color */
            padding: 15px 20px;           /* your padding */
            color: white;                 /* your text color */
            display: flex;                /* your layout */
            justify-content: flex-end;    /* your alignment */
            align-items: center;          /* your alignment */
            height: 60px;                 /* your height */
            position: fixed;              /* fixed like your spec */
            top: 0;
            left: 220px;                  /* starts after sidebar like your spec */
            right: 0;
            z-index: 100;
        }
        /* ====== END TOPBAR CHANGES ====== */

        .main-content {
            flex: 1;
            margin-left: 220px;           /* match sidebar width (your spec says 220px) */
            background: #f5f5f5;
        }

        .content-area {
            padding: 20px;
            margin-top: 60px;             /* make room for fixed topbar (your spec) */
        }
        
        .content-area h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .file-item {
            position: relative;
            display: flex;
            align-items: center;
            padding: 10px;
            margin-bottom: 5px;
            background-color: #f4f4f9;
            border-radius: 5px;
            width: 100%;
        }
        
        .file-item:hover {
            background-color: #e0e0e0;
        }
        
        .file-item img {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }
        
        .file-info {
            display: flex;
            justify-content: space-between;
            width: 100%;
        }
        
        .file-name, .folder-name {
            flex: 2;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
            text-align: left;
        }
        
        .file-time {
            flex: 2;
            text-align: center;
        }
        
        .file-type {
            flex: 2;
            text-align: center;
        }
        
        .file-size {
            flex: 3;
            text-align: right;
        }
        
        .file-options {
            position: absolute;
            right: 10px;
            display: none;
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 10;
            padding: 5px 0;
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
        
        .more-options {
            cursor: pointer;
            font-size: 20px;
            user-select: none;
        }
        
        .file-header {
            display: flex;
            padding: 10px;
            background-color: #ddd;
            font-weight: bold;
            width: 100%;
        }
        
        .file-header div {
            flex: 2;
            text-align: left;
        }
        
        .file-header .file-time {
            flex: 2;
            text-align: center;
        }
        
        .file-header .file-type {
            flex: 2;
            text-align: center;
        }
        
        .file-header .file-size {
            flex: 3;
            text-align: right;
        }
        
        .sort-container {
            position: relative;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .sort-btn {
            background-color: #8a2be2;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 4px 0 #6a1cb0, 0 5px 5px rgba(0,0,0,0.2);
            transition: all 0.1s;
            position: relative;
            top: 0;
        }
        
        .sort-btn:hover {
            background-color: #7b1fa2;
        }
        
        .sort-btn:active {
            top: 3px;
            box-shadow: 0 1px 0 #6a1cb0;
        }
        
        .sort-dropdown {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .sort-dropdown a {
            color: #333;
            padding: 10px 15px;
            text-decoration: none;
            display: block;
            font-size: 13px;
        }
        
        .sort-dropdown a:hover {
            background-color: #f1f1f1;
            color: #8a2be2;
        }
        
        .sort-dropdown.show {
            display: block;
        }
        
        .current-sort {
            font-weight: bold;
            background-color: #f1f1f1;
            color: #8a2be2 !important;
        }
        
        .upload-form, .folder-form {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .upload-form input[type="file"], 
        .folder-form input[type="text"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            flex-grow: 1;
        }
        
        .upload-form button, 
        .folder-form button {
            background-color: #8a2be2;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 15px;
            cursor: pointer;
        }
        
        .upload-form button:hover, 
        .folder-form button:hover {
            background-color: #7b1fa2;
        }
        
        .search-form {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .search-form input[type="text"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            flex-grow: 1;
        }
        
        .search-form button {
            background-color: #8a2be2;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 15px;
            cursor: pointer;
        }
        
        .search-form button:hover {
            background-color: #7b1fa2;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="sidebar">
        <h2>GDRIVE</h2>
        <ul>
            <li><a href="dashboard.php">🏠 Dashboard</a></li>
            <li><a href="my_files.php">📁 My Files</a></li>
            <li><a href="recent.php">🕓 Recent Files</a></li>
            <li><a href="trash.php">🗑️ Trash</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="topbar">
            <div class="profile">
               <img src="/gdrive/icons/profile 2.png" id="profilePic" alt="Profile" />
                <div class="dropdown" id="profileDropdown">
                    <a href="#">👤 Profile</a>
                    <a href="logout.php">🚪 Logout</a>
                </div>
            </div>
        </div>

        <div class="content-area">
            <h3>Current Folder: <?= htmlspecialchars($current_folder ?: 'Root') ?></h3>
            <?php if ($current_folder): ?>
                <p><a href="<?= $parent_link ?>">⬅ Back</a></p>
            <?php endif; ?>

            <form method="GET" action="my_files.php" class="search-form">
                <input type="text" name="search" placeholder="Search files..." 
                       value="<?= htmlspecialchars($search_query) ?>" required>
                <input type="hidden" name="folder" value="<?= htmlspecialchars($current_folder) ?>">
                <button type="submit">Search</button>
            </form>

            <form class="upload-form" action="upload.php<?= $current_folder ? '?folder=' . urlencode($current_folder) : '' ?>" 
                  method="post" enctype="multipart/form-data">
                <input type="file" name="file" required>
                <button type="submit">Upload File</button>
            </form>

            <form class="folder-form" action="upload.php<?= $current_folder ? '?folder=' . urlencode($current_folder) : '' ?>" 
                  method="post">
                <input type="text" name="new_folder" placeholder="New Folder Name" required>
                <button type="submit">Create Folder</button>
            </form>

            <!-- Sorting Dropdown -->
            <div class="sort-container">
                <button onclick="toggleSortDropdown()" class="sort-btn">Sort Files ▼</button>
                <div id="sortDropdown" class="sort-dropdown">
                    <a href="?folder=<?= urlencode($current_folder) ?>&sort=name_asc" 
                       class="<?= $sort_by == 'name_asc' ? 'current-sort' : '' ?>">Name (A-Z)</a>
                    <a href="?folder=<?= urlencode($current_folder) ?>&sort=name_desc" 
                       class="<?= $sort_by == 'name_desc' ? 'current-sort' : '' ?>">Name (Z-A)</a>
                    <a href="?folder=<?= urlencode($current_folder) ?>&sort=time_asc" 
                       class="<?= $sort_by == 'time_asc' ? 'current-sort' : '' ?>">Oldest First</a>
                    <a href="?folder=<?= urlencode($current_folder) ?>&sort=time_desc" 
                       class="<?= $sort_by == 'time_desc' ? 'current-sort' : '' ?>">Newest First</a>
                    <a href="?folder=<?= urlencode($current_folder) ?>&sort=size_asc" 
                       class="<?= $sort_by == 'size_asc' ? 'current-sort' : '' ?>">Smallest First</a>
                    <a href="?folder=<?= urlencode($current_folder) ?>&sort=size_desc" 
                       class="<?= $sort_by == 'size_desc' ? 'current-sort' : '' ?>">Largest First</a>
                </div>
            </div>

            <h4>Folders:</h4>
            <?php
            // Get folders from database
            $stmt = $pdo->prepare("SELECT * FROM files 
                                  WHERE user_id = ? 
                                  AND folder = ? 
                                  AND file_type = 'folder'
                                  AND deleted = 0
                                  ORDER BY filename ASC");
            $stmt->execute([$user_id, $current_folder]);
            $db_folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($db_folders)) {
                foreach ($db_folders as $folder) {
                    $folder_name = $folder['filename'];
                    $full_path = $current_folder ? $current_folder . '/' . $folder_name : $folder_name;
                    echo "<div class='file-item'>
                            📁 <a href='my_files.php?folder=" . urlencode($full_path) . "' class='folder-name'>" . 
                                htmlspecialchars($folder_name) . "</a>
                            <div class='more-options' onclick=\"toggleMenu('menu-folder-" . 
                                htmlspecialchars($folder_name) . "')\">⋮</div>
                            <div class='file-options' id='menu-folder-" . htmlspecialchars($folder_name) . "'>
                                <a href='rename_folder.php?path=" . urlencode($full_path) . "'>Rename</a>
                                <a href='delete_folder.php?path=" . urlencode($full_path) . "' 
                                   onclick=\"return confirm('Move this folder and its contents to trash?')\">Delete</a>
                            </div>
                          </div>";
                }
            } else {
                echo "<p>No folders found</p>";
            }
            ?>

            <!-- Header Row for Files -->
            <div class="file-header">
                <div class="file-name">Name</div>
                <div class="file-time">Date Modified</div>
                <div class="file-type">Type</div>
                <div class="file-size">Size</div>
            </div>

            <h4>Files:</h4>
            <?php
            // Determine sorting
            $sort_column = 'filename';
            $sort_order = 'ASC';

            switch ($sort_by) {
                case 'name_asc': $sort_column = 'filename'; $sort_order = 'ASC'; break;
                case 'name_desc': $sort_column = 'filename'; $sort_order = 'DESC'; break;
                case 'time_asc': $sort_column = 'uploaded_at'; $sort_order = 'ASC'; break;
                case 'time_desc': $sort_column = 'uploaded_at'; $sort_order = 'DESC'; break;
                case 'size_asc': $sort_column = 'size'; $sort_order = 'ASC'; break;
                case 'size_desc': $sort_column = 'size'; $sort_order = 'DESC'; break;
            }

            // Get files from database
            $query = "SELECT * FROM files 
                      WHERE user_id = ? 
                      AND folder = ? 
                      AND (file_type IS NULL OR file_type != 'folder')
                      AND deleted = 0";
            
            if ($search_query) {
                $query .= " AND filename LIKE ?";
            }
            
            $query .= " ORDER BY $sort_column $sort_order";
            
            $stmt = $pdo->prepare($query);
            
            $params = [$user_id, $current_folder];
            if ($search_query) {
                $params[] = "%$search_query%";
            }
            
            $stmt->execute($params);
            $db_files = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($db_files)) {
                foreach ($db_files as $file) {
                    $file_name = $file['filename'];
                    $file_size = $file['size'] ?? 0;
                    $formatted_size = formatBytes($file_size);
                    $file_icon = getFileTypeIcon($file_name);
                    $file_time = getBangladeshTime(strtotime($file['uploaded_at']));

                    echo "<div class='file-item'>
                            <div class='file-info'>
                                <img src='icons/$file_icon' alt='" . htmlspecialchars($file_name) . "'>
                                <a href='" . htmlspecialchars($file['filepath']) . "' class='file-name' target='_blank'>" . 
                                    htmlspecialchars($file_name) . "</a>
                                <div class='file-time'>$file_time</div>
                                <div class='file-type'>" . strtoupper(pathinfo($file_name, PATHINFO_EXTENSION)) . "</div>
                                <div class='file-size'>$formatted_size</div>
                            </div>
                            <div class='more-options' onclick=\"toggleMenu('menu-file-" . 
                                htmlspecialchars($file_name) . "')\">⋮</div>
                            <div class='file-options' id='menu-file-" . htmlspecialchars($file_name) . "'>
                                <a href='rename_file.php?path=" . urlencode(($current_folder ? $current_folder . '/' : '') . $file_name) . "'>Rename</a>
                                <a href='delete_file.php?path=" . urlencode(($current_folder ? $current_folder . '/' : '') . $file_name) . "' 
                                   onclick=\"return confirm('Move this file to trash?')\">Delete</a>
                            </div>
                          </div>";
                }
            } else {
                echo "<p>No files found</p>";
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

function toggleSortDropdown() {
    document.getElementById('sortDropdown').classList.toggle('show');
}

// Close the dropdown if clicked outside
window.onclick = function(event) {
    if (!event.target.matches('.sort-btn')) {
        document.querySelectorAll('.sort-dropdown').forEach(dropdown => {
            if (dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        });
    }
   
    if (!event.target.matches('.more-options')) {
        document.querySelectorAll('.file-options').forEach(menu => menu.style.display = 'none');
    }
}

// Profile dropdown toggle
document.getElementById('profilePic').addEventListener('click', function(e) {
    e.stopPropagation();
    document.getElementById('profileDropdown').style.display = 
        document.getElementById('profileDropdown').style.display === 'block' ? 'none' : 'block';
});

// Close dropdown when clicking outside
window.addEventListener('click', function() {
    var dropdown = document.getElementById('profileDropdown');
    if (dropdown.style.display === 'block') {
        dropdown.style.display = 'none';
    }
});
</script>
</body>
</html>
