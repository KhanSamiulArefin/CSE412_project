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

$folder_path = $_GET['path'];
$full_path = "files/$user_id/$folder_path";

try {
    $pdo->beginTransaction();
    
    // Mark folder as deleted
    $stmt = $pdo->prepare("UPDATE files 
                          SET deleted = 1, 
                              deleted_at = NOW(),
                              original_path = ?
                          WHERE user_id = ? 
                          AND filepath = ?");
    $stmt->execute([$folder_path, $user_id, $full_path]);
    
    // Mark all files in folder as deleted
    $stmt = $pdo->prepare("UPDATE files 
                          SET deleted = 1, 
                              deleted_at = NOW(),
                              original_path = CONCAT(?, '/', filename)
                          WHERE user_id = ? 
                          AND filepath LIKE ?");
    $stmt->execute([$folder_path, $user_id, "$full_path/%"]);
    
    $pdo->commit();
    
    // Return to previous folder or root if at top level
    $parent_folder = dirname($folder_path) === '.' ? '' : dirname($folder_path);
    header("Location: my_files.php" . ($parent_folder ? "?folder=" . urlencode($parent_folder) : ""));
    exit();
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "<script>alert('Error moving folder to trash: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
    exit();
}
?>