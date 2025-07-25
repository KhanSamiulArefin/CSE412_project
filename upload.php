<?php
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

    if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
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
        mkdir($new_folder_path, 0777, true);
        echo "<script>alert('Folder created successfully'); window.location.href='my_files.php" . ($current_folder ? "?folder=" . urlencode($current_folder) : "") . "';</script>";
    } else {
        echo "<script>alert('Folder already exists'); window.location.href='my_files.php" . ($current_folder ? "?folder=" . urlencode($current_folder) : "") . "';</script>";
    }
}
?>
