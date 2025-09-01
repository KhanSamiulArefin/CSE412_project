<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();
    
    // Get all trashed items first so we can delete physical files
    $stmt = $pdo->prepare("SELECT * FROM files WHERE user_id = ? AND deleted = 1");
    $stmt->execute([$user_id]);
    $trashed_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($trashed_items as $item) {
        if (file_exists($item['filepath'])) {
            if (isset($item['file_type']) && $item['file_type'] === 'folder') {
                // Delete folder recursively
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($item['filepath'], RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                
                foreach ($files as $fileinfo) {
                    if ($fileinfo->isDir()) {
                        rmdir($fileinfo->getRealPath());
                    } else {
                        unlink($fileinfo->getRealPath());
                    }
                }
                rmdir($item['filepath']);
            } else {
                // Delete file
                unlink($item['filepath']);
            }
        }
    }
    
    // Now delete all database records
    $pdo->prepare("DELETE FROM files WHERE user_id = ? AND deleted = 1")
       ->execute([$user_id]);
    
    $pdo->commit();
    $_SESSION['success'] = "Trash emptied successfully";
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Empty Trash Error: ".$e->getMessage());
    $_SESSION['error'] = "Failed to empty trash";
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("File Deletion Error: ".$e->getMessage());
    $_SESSION['error'] = "Error deleting some files";
}

header("Location: trash.php");
?>