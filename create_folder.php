<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Not logged in'); window.location.href='login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$folder_name = trim($_POST['folder_name']);
$current_path = isset($_GET['folder']) ? $_GET['folder'] : ''; // Current path
$base_path = "files/$user_id" . ($current_path ? "/$current_path" : '');

if (!empty($folder_name)) {
    $new_folder_path = $base_path . '/' . basename($folder_name);
    if (!file_exists($new_folder_path)) {
        mkdir($new_folder_path, 0777, true);
        echo "<script>alert('Folder created successfully'); window.location.href='my_files.php" . ($current_path ? "?folder=" . urlencode($current_path) : "") . "';</script>";
    } else {
        echo "<script>alert('Folder already exists'); window.location.href='my_files.php" . ($current_path ? "?folder=" . urlencode($current_path) : "") . "';</script>";
    }
} else {
    echo "<script>alert('Folder name cannot be empty'); window.location.href='my_files.php';</script>";
}
?>
