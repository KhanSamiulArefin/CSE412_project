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
    // Verify item belongs to user and is in trash
    $stmt = $pdo->prepare("SELECT * FROM files 
                          WHERE id = ? 
                          AND user_id = ? 
                          AND deleted = 1");
    $stmt->execute([$file_id, $user_id]);
    $item = $stmt->fetch();
    
    if ($item) {
        $pdo->beginTransaction();
        
        if ($item['file_type'] === 'folder') {
            // Delete all files in this folder
            $pdo->prepare("DELETE FROM files 
                          WHERE user_id = ? 
                          AND original_path LIKE ?")
               ->execute([$user_id, $item['original_path'].'/%']);
            
            // Delete physical folder if exists
            if (file_exists($item['filepath'])) {
                // Recursive directory deletion function
                function deleteDirectory($dir) {
                    if (!file_exists($dir)) return true;
                    if (!is_dir($dir)) return unlink($dir);
                    foreach (scandir($dir) as $item) {
                        if ($item == '.' || $item == '..') continue;
                        if (!deleteDirectory($dir.DIRECTORY_SEPARATOR.$item)) return false;
                    }
                    return rmdir($dir);
                }
                deleteDirectory($item['filepath']);
            }
        } else {
            // Delete physical file if exists
            if (file_exists($item['filepath'])) {
                unlink($item['filepath']);
            }
        }
        
        // Delete database record
        $pdo->prepare("DELETE FROM files WHERE id = ?")
           ->execute([$item['id']]);
        
        $pdo->commit();
        $_SESSION['success'] = "Item permanently deleted";
    } else {
        $_SESSION['error'] = "Item not found in trash";
    }
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Delete Error: ".$e->getMessage());
    $_SESSION['error'] = "Failed to delete item permanently";
}

header("Location: trash.php");
?>