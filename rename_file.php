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

// Normalize and validate the file path (relative to user's root)
$old_rel = str_replace('\\', '/', trim($_GET['path'], '/'));
if ($old_rel === '' || strpos($old_rel, '..') !== false) {
    echo "<script>alert('Invalid path'); window.history.back();</script>";
    exit();
}

$full_old = "files/$user_id/$old_rel";
if (!is_file($full_old)) {
    echo "<script>alert('File not found'); window.history.back();</script>";
    exit();
}

$parent_rel = str_replace('\\', '/', trim(dirname($old_rel), './'));
$parent_rel = ($parent_rel === '.' ? '' : $parent_rel);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = isset($_POST['new_name']) ? trim($_POST['new_name']) : '';
    // No slashes in filename
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

    // 2) Update DB row for this file (so my_files shows updated name/path)
    try {
        $pdo->beginTransaction();

        $new_filepath = "files/$user_id/" . $new_rel;

        $upd = $pdo->prepare("
            UPDATE files
            SET filename = ?, filepath = ?
            WHERE user_id = ? AND deleted = 0 AND folder = ? AND filename = ?
        ");
        $upd->execute([$new_name, $new_filepath, $user_id, $parent_rel, basename($old_rel)]);

        $pdo->commit();
    } catch (Exception $e) {
        // Revert FS rename if DB fails
        @rename($full_new, $full_old);
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo "<script>alert('Database update failed'); window.history.back();</script>";
        exit();
    }

    // Back to containing folder
    $redir = 'my_files.php' . ($parent_rel !== '' ? ('?folder=' . urlencode($parent_rel)) : '');
    echo "<script>alert('File renamed successfully'); window.location.href='$redir';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Rename File</title></head>
<body>
<h3>Rename File</h3>
<form method="post">
    <input type="text" name="new_name" value="<?= htmlspecialchars(basename($old_rel)) ?>" placeholder="New File Name" required>
    <button type="submit">Rename</button>
</form>
</body>
</html>
