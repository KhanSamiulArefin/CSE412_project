<?php
require 'db.php';
session_start();

date_default_timezone_set('Asia/Dhaka');

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Not logged in'); window.location.href='login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Get trashed items from database
$stmt = $pdo->prepare("SELECT * FROM files WHERE user_id = ? AND deleted = 1 ORDER BY deleted_at DESC");
$stmt->execute([$user_id]);
$trashed_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getFileTypeIcon($item) {
    // Check if it's a folder first
    if (isset($item['file_type']) && $item['file_type'] === 'folder') {
        return 'folder-icon.png';
    }
    
    // Otherwise check file extension
    $extension = isset($item['file_type']) ? $item['file_type'] : strtolower(pathinfo($item['filename'], PATHINFO_EXTENSION));
    $icons = [
        'pdf' => 'pdf-icon.png',
        'doc' => 'word-icon.png',
        'docx' => 'word-icon.png',
        'txt' => 'text-icon.png',
        'jpg' => 'image-icon.png',
         'JPG' => 'image-icon.png',
        'jpeg' => 'image-icon.png',
        'png' => 'image-icon.png',
        'gif' => 'image-icon.png',
        'mp3' => 'audio-icon.png',
        'wav' => 'audio-icon.png',
        'mp4' => 'video-icon.png',
        'mov' => 'video-icon.png'
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

function formatDeletedTime($timestamp) {
    $dt = new DateTime($timestamp);
    $dt->setTimezone(new DateTimeZone('Asia/Dhaka'));
    return $dt->format("F d, Y H:i:s");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trash - GDRIVE</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Import Montserrat for the sidebar text formatting (as per your spec) */
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
        
        /* ===== Sidebar updated to follow your exact spec (color, text, width, position) ===== */
        .sidebar {
            background-color: #e7d6ff;   /* your color */
            width: 220px;                 /* your width */
            padding: 20px;                /* your padding */
            height: 100vh;                /* full height */
            position: fixed;              /* fixed like your spec */
            top: 0;
            left: 0;
            overflow-y: auto;             /* scrollable */
            z-index: 1;
            font-family: 'Montserrat', sans-serif; /* your font for sidebar */
            color: #333;                  /* link/text base color */
        }
        
        .sidebar h2 {
            color: #430457;               /* your heading color */
            margin-bottom: 30px;          /* your spacing */
            text-align: left;             /* match your default (no centering) */
            padding: 0 0;
        }
        
        .sidebar ul {
            list-style: none;             /* per spec */
            padding: 0;                   /* per spec */
        }
        
        .sidebar li a {
            color: #333;                  /* per spec */
            text-decoration: none;        /* per spec */
            display: block;               /* per spec */
            padding: 10px;                /* per spec */
            border-radius: 8px;           /* per spec */
            transition: background-color .25s ease, color .25s ease;
        }
        
        .sidebar li a:hover {
            background-color: #d1bbfc;    /* per spec */
        }
        /* ===== End Sidebar changes ===== */

        .main-content {
            flex: 1;
            margin-left: 220px;           /* match sidebar width as in your spec */
            background: #f5f5f5;
        }
        
        /* ===== Topbar updated to follow your spec (color, fixed, offset, height) ===== */
        .topbar {
            background-color: #430457;    /* your color */
            padding: 15px 20px;           /* your padding */
            color: white;                 /* your text color */
            display: flex;
            justify-content: flex-end;
            align-items: center;
            height: 60px;                 /* your height */
            position: fixed;              /* fixed like your spec */
            top: 0;
            left: 220px;                  /* offset by sidebar width */
            right: 0;
            z-index: 100;
            box-shadow: none;             /* remove previous white-bar shadow */
        }
        /* ===== End Topbar changes ===== */
        
        .profile {
            position: relative;
        }
        
        .profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            object-fit: cover;
        }
        
        .dropdown {
            display: none;
            position: absolute;
            right: 0;
            background: white;
            min-width: 150px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 5px;
            z-index: 100;
            overflow: hidden;
        }
        
        .dropdown a {
            display: block;
            padding: 10px 15px;
            text-decoration: none;
            color: #333;
            transition: background 0.2s;
        }
        
        .dropdown a:hover {
            background: #f0f0f0;
        }
        
        .content-area {
            padding: 20px;
            margin-top: 60px;             /* room for fixed topbar */
        }
        
        .content-area h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .trash-list {
            list-style: none;
        }
        
        .trash-grid {
            display: grid;
            grid-template-columns: 40px 2fr 2fr 1fr 1fr 130px;
            align-items: center;
            gap: 15px;
            padding: 12px 15px;
            background: #fff;
            margin-bottom: 10px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }
        
        .trash-grid:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .item-icon {
            width: 24px;
            height: 24px;
        }
        
        .item-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .item-name {
            font-weight: 500;
            color: #333;
            font-size: 15px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .item-time {
            font-size: 13px;
            color: #666;
        }
        
        .item-type {
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
        }
        
        .item-size {
            font-size: 13px;
            color: #666;
            text-align: right;
        }
        
        .item-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }
        
        .action-btn {
            padding: 6px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            white-space: nowrap;
        }
        
        .restore-btn {
            background-color: #7B68EE;
            color: white;
        }
        
        .restore-btn:hover {
            background-color: #6A5ACD;
        }
        
        .delete-btn {
            background-color: #e74c3c;
            color: white;
        }
        
        .delete-btn:hover {
            background-color: #c0392b;
        }
        
        .empty-trash-btn {
            background-color: #e74c3c;
            color: white;
            padding: 10px 15px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .empty-trash-btn:hover {
            background-color: #c0392b;
        }
        
        .no-items {
            background: white;
            padding: 30px;
            text-align: center;
            border-radius: 5px;
            color: #666;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .grid-header {
            display: grid;
            grid-template-columns: 40px 2fr 2fr 1fr 1fr 130px;
            gap: 15px;
            padding: 10px 15px;
            background-color: #ddd;
            font-weight: bold;
            border-radius: 5px;
            margin-bottom: 10px;
            align-items: center;
        }
        
        .header-name {
            text-align: left;
        }
        
        .header-time {
            text-align: left;
        }
        
        .header-type {
            text-align: left;
        }
        
        .header-size {
            text-align: right;
        }
        
        .header-actions {
            text-align: right;
        }
        
        @media (max-width: 1024px) {
            .trash-grid,
            .grid-header {
                grid-template-columns: 30px 1fr 1fr 1fr;
                gap: 10px;
            }
            
            .item-time {
                grid-column: 2;
            }
            
            .item-type {
                grid-column: 3;
            }
            
            .item-size {
                grid-column: 4;
                grid-row: 1;
            }
            
            .item-actions {
                grid-column: 2 / span 3;
                grid-row: 2;
                justify-content: flex-start;
                margin-top: 10px;
            }
            
            .header-time {
                grid-column: 2;
            }
            
            .header-type {
                grid-column: 3;
            }
            
            .header-size {
                grid-column: 4;
            }
            
            .header-actions {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            .topbar {
                left: 200px;
            }
            .main-content {
                margin-left: 200px;
            }
            
            .trash-grid,
            .grid-header {
                grid-template-columns: 30px 1fr;
                gap: 8px;
                padding: 10px;
            }
            
            .item-name {
                grid-column: 2;
            }
            
            .item-time {
                grid-column: 1 / span 2;
                font-size: 12px;
            }
            
            .item-type {
                grid-column: 1;
                grid-row: 3;
                justify-self: start;
            }
            
            .item-size {
                grid-column: 2;
                grid-row: 3;
                text-align: right;
            }
            
            .item-actions {
                grid-column: 1 / span 2;
                grid-row: 4;
                justify-content: center;
                margin-top: 10px;
            }
            
            .grid-header {
                display: none;
            }
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
            <h3>Trash</h3>
            
            <?php if (!empty($trashed_items)): ?>
                <form method="post" action="empty_trash.php" onsubmit="return confirm('Are you sure you want to permanently delete ALL items in trash? This cannot be undone.');">
                    <button type="submit" class="empty-trash-btn">🗑️ Empty Trash</button>
                </form>
            <?php endif; ?>
            
            <?php if (empty($trashed_items)) : ?>
                <div class="no-items">
                    <p>No items in trash.</p>
                </div>
            <?php else : ?>
                <!-- Header Row for Items -->
                <div class="grid-header">
                    <div></div>
                    <div class="header-name">Name</div>
                    <div class="header-time">Deleted At</div>
                    <div class="header-type">Type</div>
                    <div class="header-size">Size</div>
                    <div class="header-actions">Actions</div>
                </div>
                
                <ul class="trash-list">
                    <?php foreach ($trashed_items as $item): ?>
                        <li class="trash-grid">
                            <div class="item-icon">
                                <img src="icons/<?= getFileTypeIcon($item) ?>" alt="icon">
                            </div>
                            <span class="item-name"><?= htmlspecialchars($item['filename']) ?></span>
                            <span class="item-time"><?= formatDeletedTime($item['deleted_at']) ?></span>
                            <span class="item-type"><?= isset($item['file_type']) && $item['file_type'] === 'folder' ? 'FOLDER' : strtoupper(pathinfo($item['filename'], PATHINFO_EXTENSION)) ?></span>
                            <span class="item-size"><?= isset($item['size']) ? formatBytes($item['size']) : '—' ?></span>
                            <div class="item-actions">
                                <a href="restore_file.php?id=<?= $item['id'] ?>" class="action-btn restore-btn">Restore</a>
                                <a href="permanent_delete.php?id=<?= $item['id'] ?>" 
                                   onclick="return confirm('Are you sure you want to permanently delete this item?')" 
                                   class="action-btn delete-btn">Delete</a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
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