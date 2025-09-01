<?php
require 'db.php';
session_start();

// Set Bangladesh timezone at the very beginning
date_default_timezone_set('Asia/Dhaka');

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Not logged in'); window.location.href='login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

function getFileTypeIcon($extension) {
    if ($extension === '__FOLDER__') return 'folder-icon.png';
    $extension = strtolower($extension);
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
        'mov' => 'video-icon.png'
    ];
    return isset($icons[$extension]) ? $icons[$extension] : 'file-icon.png';
}

function formatBytes($bytes, $precision = 2) {
    if ($bytes === null) return '—';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max((int)$bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function getBangladeshTimeFromAny($ts_or_string) {
    if (!$ts_or_string) return '—';
    // Accept either unix timestamp (int) or datetime string
    if (is_numeric($ts_or_string)) {
        $dt = new DateTime('@' . $ts_or_string);
    } else {
        $t = strtotime($ts_or_string);
        if ($t === false) return '—';
        $dt = new DateTime('@' . $t);
    }
    $dt->setTimezone(new DateTimeZone('Asia/Dhaka'));
    return $dt->format("F d Y H:i:s");
}

/* -------------------------
   Fetch 5 recent folders
------------------------- */
$recent_folders = [];
$stmt = $pdo->prepare("
    SELECT id, filename, folder, filepath, uploaded_at, file_type
    FROM files
    WHERE user_id = ? AND deleted = 0 AND file_type = 'folder'
    ORDER BY uploaded_at DESC, id DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* -------------------------
   Fetch 5 recent files
------------------------- */
$recent_files = [];
$stmt = $pdo->prepare("
    SELECT id, filename, folder, filepath, size, uploaded_at, file_type
    FROM files
    WHERE user_id = ? AND deleted = 0 AND (file_type IS NULL OR file_type != 'folder')
    ORDER BY uploaded_at DESC, id DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_files = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recent Files</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Import Montserrat for sidebar text formatting (as requested) */
        @import url('https://fonts.googleapis.com/css?family=Montserrat:400,800');

        /* Base (unchanged elsewhere) */
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

        /* ===== Sidebar updated to match your spec (color, text style, width, position) ===== */
        .sidebar {
            background-color: #e7d6ff;   /* your color */
            width: 220px;                 /* your width */
            padding: 20px;                /* your padding */
            height: 100vh;                /* full viewport height */
            position: fixed;              /* fixed like your spec */
            top: 0;
            left: 0;
            overflow-y: auto;             /* scrollable */
            z-index: 1;
            font-family: 'Montserrat', sans-serif; /* your font only for sidebar */
            color: #333;
        }
        
        .sidebar h2 {
            color: #430457;               /* your heading color */
            margin-bottom: 30px;          /* your spacing */
            text-align: left;             /* matches your example's default */
            padding: 0 0;                 /* remove extra padding to match spec */
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }

        .sidebar li a {
            text-decoration: none;
            color: #333;                  /* your link color */
            display: block;
            padding: 10px;
            border-radius: 8px;
            transition: background-color .25s ease, color .25s ease;
        }

        .sidebar li a:hover {
            background-color: #d1bbfc;    /* your hover bg */
        }

        /* Active state adjusted to fit the new palette */
        .sidebar li a.active {
            background-color: #d1bbfc;
            color: #333;
        }

        /* ===== Topbar updated to match your spec (color, fixed position, height, offset) ===== */
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
            box-shadow: none;             /* remove previous white bar style */
        }

        /* ===== Main content shifted to account for 220px sidebar and 60px fixed topbar ===== */
        .main-content {
            flex: 1;
            margin-left: 220px;           /* match sidebar width */
            background: #f5f5f5;
        }
        
        .content-area {
            padding: 20px;
            margin-top: 60px;             /* space for fixed topbar */
        }
        
        .content-area h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .section-title {
            margin-top: 10px;
            margin-bottom: 10px;
            color: #333;
            font-size: 18px;
        }
        
        /* Reuse your existing list/item styles */
        .file-list {
            list-style: none;
        }
        
        .file-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            background: #fff;
            margin-bottom: 10px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }
        
        .file-item:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .file-icon {
            width: 24px;
            height: 24px;
            margin-right: 15px;
        }
        
        .file-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .file-details {
            flex: 1;
        }
        
        .file-name {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
            font-size: 15px;
        }
        
        .file-meta {
            display: flex;
            font-size: 13px;
            color: #666;
            flex-wrap: wrap;
        }
        
        .file-meta span {
            margin-right: 15px;
        }
        
        .file-link {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            flex: 1;
        }
        
        .no-files {
            background: white;
            padding: 30px;
            text-align: center;
            border-radius: 5px;
            color: #666;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
        }
    </style>
</head>
<body class="recent-file-page">
<div class="wrapper">
    <div class="sidebar">
        <h2>GDRIVE</h2>
        <ul>
            <li><a href="dashboard.php">🏠 Dashboard</a></li>
            <li><a href="my_files.php">📁 My Files</a></li>
            <li><a href="recent.php" class="active">🕓 Recent Files</a></li>
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
            <h2>Recent</h2>

            <!-- Recent Folders (up to 5) -->
            <h3 class="section-title">Recent Folders</h3>
            <?php if (!empty($recent_folders)) : ?>
                <ul class="file-list">
                    <?php foreach ($recent_folders as $folder): 
                        $folder_full_path = $folder['folder'] ? ($folder['folder'] . '/' . $folder['filename']) : $folder['filename'];
                        $when = $folder['uploaded_at'] ?: null;
                    ?>
                        <li class="file-item">
                            <a href="my_files.php?folder=<?= urlencode($folder_full_path) ?>" class="file-link">
                                <div class="file-icon">
                                    <img src="icons/<?= getFileTypeIcon('__FOLDER__') ?>" alt="FOLDER">
                                </div>
                                <div class="file-details">
                                    <div class="file-name"><?= htmlspecialchars($folder['filename']) ?></div>
                                    <div class="file-meta">
                                        <span><?= getBangladeshTimeFromAny($when) ?></span>
                                        <span>FOLDER</span>
                                        <span>—</span>
                                    </div>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="no-files"><p>No recent folders.</p></div>
            <?php endif; ?>

            <!-- Recent Files (up to 5) -->
            <h3 class="section-title">Recent Files</h3>
            <?php if (!empty($recent_files)) : ?>
                <ul class="file-list">
                    <?php foreach ($recent_files as $file): 
                        $ext = strtolower(pathinfo($file['filename'], PATHINFO_EXTENSION));
                        $icon = getFileTypeIcon($ext);
                        $when = $file['uploaded_at'] ?: null;
                    ?>
                        <li class="file-item">
                            <a href="<?= htmlspecialchars($file['filepath']) ?>" target="_blank" class="file-link">
                                <div class="file-icon">
                                    <img src="icons/<?= $icon ?>" alt="<?= htmlspecialchars($ext ?: 'file') ?>">
                                </div>
                                <div class="file-details">
                                    <div class="file-name"><?= htmlspecialchars($file['filename']) ?></div>
                                    <div class="file-meta">
                                        <span><?= getBangladeshTimeFromAny($when) ?></span>
                                        <span><?= strtoupper($ext) ?: 'FILE' ?></span>
                                        <span><?= formatBytes($file['size']) ?></span>
                                    </div>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="no-files"><p>No recent files.</p></div>
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
