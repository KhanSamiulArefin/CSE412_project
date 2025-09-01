<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$profile_pic = $user['profile_pic'] ? $user['profile_pic'] : 'default_avatar.png';
?>

<!DOCTYPE html>
<html>
<head>
  <title>My Profile</title>
  <link rel="stylesheet" href="styles.css">
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
      <li><a href="profile.php">👤 Profile</a></li>
    </ul>
  </div>

  <div class="main-content">
    <div class="topbar">
      <div class="profile">
        <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile" />
      </div>
    </div>

    <div class="content-area">
      <h2>Profile</h2>

      <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile" style="width:100px; border-radius:50%; margin-bottom:20px;" />

      <form action="upload_profile_pic.php" method="post" enctype="multipart/form-data">
        <input type="file" name="profile_pic" accept="image/*" required />
        <button type="submit">Upload Profile Picture</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
