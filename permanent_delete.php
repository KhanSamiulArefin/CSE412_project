<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$file_id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM files WHERE id = ? AND user_id = ?");
$stmt->execute([$file_id, $user_id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if ($file && file_exists($file['filepath'])) {
    unlink($file['filepath']);
}

$stmt = $pdo->prepare("DELETE FROM files WHERE id = ? AND user_id = ?");
$stmt->execute([$file_id, $user_id]);

header("Location: trash.php");
exit();
?>
