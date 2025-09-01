<?php
require 'db.php';
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

try {
    // First mark as deleted in database
    $stmt = $pdo->prepare("UPDATE files 
                          SET deleted = 1, 
                              deleted_at = NOW(),
                              original_path = ?
                          WHERE user_id = ? 
                          AND filepath = ?");
    $stmt->execute([$file_path, $user_id, "files/$user_id/$file_path"]);

    echo "<script>alert('File moved to trash'); window.location.href='my_files.php" . (dirname($file_path) != '.' ? '?folder=' . urlencode(dirname($file_path)) : '') . "';</script>";
} catch (PDOException $e) {
    echo "<script>alert('Error moving file to trash'); window.history.back();</script>";
}
?>