<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}

$user_id = $_SESSION['user_id'];
$file_id = $_GET['id'];

try {
    // Get file/folder info
    $stmt = $pdo->prepare("SELECT * FROM files WHERE id = ? AND user_id = ? AND deleted = 1");
    $stmt->execute([$file_id, $user_id]);
    $item = $stmt->fetch();
    
    if ($item) {
        if ($item['file_type'] === 'folder') {
            // Restore folder and its contents
            $pdo->beginTransaction();
            
            // Restore the folder itself
            $pdo->prepare("UPDATE files SET 
                          deleted = 0, 
                          deleted_at = NULL,
                          original_path = NULL
                          WHERE id = ?")
               ->execute([$item['id']]);
            
            // Restore all files in this folder
            $pdo->prepare("UPDATE files SET 
                          deleted = 0, 
                          deleted_at = NULL,
                          original_path = NULL
                          WHERE user_id = ? 
                          AND original_path LIKE ?")
               ->execute([$user_id, $item['original_path'].'/%']);
            
            $pdo->commit();
            $_SESSION['success'] = "Folder and contents restored successfully";
        } else {
            // Restore regular file
            $pdo->prepare("UPDATE files SET 
                          deleted = 0, 
                          deleted_at = NULL,
                          original_path = NULL
                          WHERE id = ?")
               ->execute([$item['id']]);
            
            $_SESSION['success'] = "File restored successfully";
        }
    } else {
        $_SESSION['error'] = "Item not found in trash";
    }
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Restore Error: ".$e->getMessage());
    $_SESSION['error'] = "Failed to restore item";
}

header("Location: trash.php");
?>