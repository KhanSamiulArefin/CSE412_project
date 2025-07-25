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

$folder = $_GET['path'];
$full_path = "files/$user_id/$folder";

function deleteDirectory($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) return unlink($dir);
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
    }
    return rmdir($dir);
}

if (deleteDirectory($full_path)) {
    echo "<script>alert('Folder deleted successfully'); window.location.href='my_files.php" . (dirname($folder) != '.' ? '?folder=' . urlencode(dirname($folder)) : '') . "';</script>";
} else {
    echo "<script>alert('Error deleting folder'); window.history.back();</script>";
}
?>
