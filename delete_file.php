<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Not logged in'); window.location.href='login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
if (!isset($_GET['path'])) {
    echo "<script>alert('Invalid request'); window.history.back();</script>";
    exit();
}

$file_path = $_GET['path']; // This includes folder structure if any
$full_path = "files/$user_id/$file_path";

if (file_exists($full_path) && is_file($full_path)) {
    if (unlink($full_path)) {
        echo "<script>alert('File deleted successfully'); window.location.href='my_files.php" . (dirname($file_path) != '.' ? '?folder=' . urlencode(dirname($file_path)) : '') . "';</script>";
    } else {
        echo "<script>alert('Error deleting file'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('File not found'); window.history.back();</script>";
}
?>
