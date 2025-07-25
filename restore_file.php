<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$file_id = $_GET['id'];

$stmt = $pdo->prepare("UPDATE files SET deleted = 0 WHERE id = ? AND user_id = ?");
$stmt->execute([$file_id, $user_id]);

header("Location: trash.php");
exit();
?>
