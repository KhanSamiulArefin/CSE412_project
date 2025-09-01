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

// Normalize and validate the folder path (relative to user's root)
$old_rel = str_replace('\\', '/', trim($_GET['path'], '/'));
if ($old_rel === '' || strpos($old_rel, '..') !== false) {
    echo "<script>alert('Invalid path'); window.history.back();</script>";
    exit();
}

$full_old = "files/$user_id/$old_rel";
if (!is_dir($full_old)) {
    echo "<script>alert('Folder not found'); window.history.back();</script>";
    exit();
}

$parent_rel = str_replace('\\', '/', trim(dirname($old_rel), './'));
$parent_rel = ($parent_rel === '.' ? '' : $parent_rel);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = isset($_POST['new_name']) ? trim($_POST['new_name']) : '';
    // No slashes/backtracking in names
    $new_name = preg_replace('/[\/\\\\]+/', '', $new_name);

    if ($new_name === '') {
        echo "<script>alert('Invalid name'); window.history.back();</script>";
        exit();
    }

    $new_rel = ($parent_rel ? $parent_rel . '/' : '') . $new_name;
    $full_new = "files/$user_id/$new_rel";

    if (file_exists($full_new)) {
        echo "<script>alert('A file or folder with that name already exists'); window.history.back();</script>";
        exit();
    }

    // 1) Rename on filesystem
    if (!@rename($full_old, $full_new)) {
        echo "<script>alert('Rename failed at filesystem'); window.history.back();</script>";
        exit();
    }

    // 2) Update DB to keep UI in sync
    try {
        $pdo->beginTransaction();

        // 2a) Update the folder row itself
        $stmt = $pdo->prepare("
            UPDATE files
            SET filename = ?, filepath = ?
            WHERE user_id = ? AND file_type = 'folder' AND deleted = 0
              AND folder = ? AND filename = ?
        ");
        $folder_filepath = "files/$user_id/" . $new_rel; // in case you store filepath for folders
        $stmt->execute([$new_name, $folder_filepath, $user_id, $parent_rel, basename($old_rel)]);

        // 2b) Update all child rows whose 'folder' is under the renamed folder
        $like = $old_rel . '/%';
        $select = $pdo->prepare("
            SELECT id, folder, filepath
            FROM files
            WHERE user_id = ? AND deleted = 0
              AND (folder = ? OR folder LIKE ?)
        ");
        $select->execute([$user_id, $old_rel, $like]);
        $rows = $select->fetchAll(PDO::FETCH_ASSOC);

        $upd = $pdo->prepare("UPDATE files SET folder = ?, filepath = ? WHERE id = ?");

        $old_fs_prefix = "files/$user_id/" . $old_rel . '/';
        $new_fs_prefix = "files/$user_id/" . $new_rel . '/';

        foreach ($rows as $r) {
            // New folder value
            if ($r['folder'] === $old_rel) {
                $new_folder = $new_rel;
            } elseif (strpos($r['folder'], $old_rel . '/') === 0) {
                $new_folder = $new_rel . substr($r['folder'], strlen($old_rel));
            } else {
                $new_folder = $r['folder']; // safety fallback
            }

            // New filepath (may be NULL for folders—preserve NULL)
            $new_fp = $r['filepath'];
            if (!empty($new_fp) && strpos($new_fp, $old_fs_prefix) === 0) {
                $new_fp = $new_fs_prefix . substr($new_fp, strlen($old_fs_prefix));
            }

            $upd->execute([$new_folder, $new_fp, $r['id']]);
        }

        $pdo->commit();
    } catch (Exception $e) {
        // Try to revert FS rename if DB failed
        @rename($full_new, $full_old);
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo "<script>alert('Database update failed'); window.history.back();</script>";
        exit();
    }

    // Go back to the parent folder view
    $redir = 'my_files.php' . ($parent_rel !== '' ? ('?folder=' . urlencode($parent_rel)) : '');
    echo "<script>alert('Folder renamed'); window.location.href='$redir';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Rename Folder</title></head>
<body>
<h3>Rename Folder</h3>
<form method="post">
    <input type="text" name="new_name" value="<?= htmlspecialchars(basename($old_rel)) ?>" placeholder="New Folder Name" required>
    <button type="submit">Rename</button>
</form>
</body>
</html>
