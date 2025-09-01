<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Not logged in'); window.location.href='login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$current_folder = isset($_GET['folder']) ? $_GET['folder'] : '';
$target_dir = "files/$user_id" . ($current_folder ? "/$current_folder" : '');

// Ensure folder exists
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

// Handle file upload
if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
    $original_name = basename($_FILES["file"]["name"]);
    $target_file = $target_dir . '/' . $original_name;
    $file_size = $_FILES["file"]["size"];
    $file_type = pathinfo($original_name, PATHINFO_EXTENSION);

    // Check if file already exists and isn't in trash
    $stmt = $pdo->prepare("SELECT id FROM files WHERE user_id = ? AND filename = ? AND folder = ? AND deleted = 0");
    $stmt->execute([$user_id, $original_name, $current_folder]);
    
    if ($stmt->rowCount() > 0) {
        echo "<script>alert('File already exists'); window.location.href='my_files.php" . ($current_folder ? "?folder=" . urlencode($current_folder) : "") . "';</script>";
        exit();
    }

    if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
        // Add to database
        $stmt = $pdo->prepare("INSERT INTO files (user_id, filename, filepath, folder, size, file_type) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $original_name, $target_file, $current_folder, $file_size, $file_type]);
        
        echo "<script>alert('File uploaded successfully'); window.location.href='my_files.php" . ($current_folder ? "?folder=" . urlencode($current_folder) : "") . "';</script>";
    } else {
        echo "<script>alert('Error uploading file'); window.location.href='my_files.php" . ($current_folder ? "?folder=" . urlencode($current_folder) : "") . "';</script>";
    }
}

// Handle folder creation
if (isset($_POST['new_folder'])) {
    $new_folder_name = trim($_POST['new_folder']);
    $new_folder_path = $target_dir . '/' . basename($new_folder_name);

    if (!file_exists($new_folder_path)) {
        if (mkdir($new_folder_path, 0777, true)) {
            // Add to database
            $stmt = $pdo->prepare("INSERT INTO files (user_id, filename, filepath, folder, file_type) 
                                  VALUES (?, ?, ?, ?, 'folder')");
            $stmt->execute([$user_id, $new_folder_name, $new_folder_path, $current_folder]);
            
            echo "<script>alert('Folder created successfully'); window.location.href='my_files.php" . ($current_folder ? "?folder=" . urlencode($current_folder) : "") . "';</script>";
        } else {
            echo "<script>alert('Error creating folder'); window.location.href='my_files.php" . ($current_folder ? "?folder=" . urlencode($current_folder) : "") . "';</script>";
        }
    } else {
        echo "<script>alert('Folder already exists'); window.location.href='my_files.php" . ($current_folder ? "?folder=" . urlencode($current_folder) : "") . "';</script>";
    }
}
?>