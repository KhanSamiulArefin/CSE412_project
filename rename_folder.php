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

$old_path = $_GET['path'];
$full_old = "files/$user_id/$old_path";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['new_name']);
    if ($new_name) {
        $parent_dir = dirname($full_old);
        $new_full_path = $parent_dir . '/' . basename($new_name);
        if (rename($full_old, $new_full_path)) {
            echo "<script>alert('Folder renamed'); window.location.href='my_files.php" . (dirname($old_path) != '.' ? '?folder=' . urlencode(dirname($old_path)) : '') . "';</script>";
        } else {
            echo "<script>alert('Rename failed'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Invalid name'); window.history.back();</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>Rename Folder</title></head>
<body>
<h3>Rename Folder</h3>
<form method="post">
    <input type="text" name="new_name" placeholder="New Folder Name" required>
    <button type="submit">Rename</button>
</form>
</body>
</html>
