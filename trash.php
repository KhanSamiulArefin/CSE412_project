<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM files WHERE user_id = ? AND deleted = 1 ORDER BY uploaded_at DESC");
$stmt->execute([$user_id]);
$trashed_files = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Trash</title>
  <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
</head>
<body>
<div class="wrapper">
  <div class="sidebar">
    <h2>GDRIVE</h2>
    <ul>
      <li><a href="dashboard.php">🏠 Dashboard</a></li>
      <li><a href="my_files.php">📁 My Files</a></li>
      <li><a href="recent.php">🕓 Recent Files</a></li>
      <li><a href="trash.php">🗑️ Trash</a></li>
    </ul>
  </div>

  <div class="main-content">
    <div class="topbar">
      <div class="profile">
        <img src="" alt="Profile" />
      </div>
    </div>

    <div class="content-area">
      <h3>Trash</h3>
      <div class="file-list">
        <?php if (empty($trashed_files)): ?>
          <p>No files in trash.</p>
        <?php else: ?>
          <?php foreach ($trashed_files as $file): ?>
            <div class="file-item">
              🗑️ <?= htmlspecialchars($file['filename']) ?>
              <a href="restore_file.php?id=<?= $file['id'] ?>" style="margin-left: 15px;">Restore</a>
              <a href="permanent_delete.php?id=<?= $file['id'] ?>" onclick="return confirm('Permanently delete this file?')" style="margin-left: 10px; color: red;">Delete Forever</a>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>
