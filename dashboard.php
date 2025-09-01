<?php
// dashboard.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>GDrive Dashboard</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>

<div class="wrapper">

  <!-- Sidebar -->
  <div class="sidebar">
    <h2>GDRIVE</h2>
    <ul>
      <li><a href="dashboard.php">🏠 Dashboard</a></li>
      <li><a href="my_files.php">📁 My Files</a></li>
      <li><a href="recent.php">🕓 Recent Files</a></li>
      <li><a href="trash.php">🗑️ Trash</a></li>
    </ul>

  </div>

  <!-- Main Content -->
  <div class="main-content">

    <!-- Top Navbar -->
    <div class="topbar">
      <div class="profile">
        <img src="/gdrive/icons/profile 2.png" id="profilePic" alt="Profile" />
        <div class="dropdown" id="profileDropdown">
          <a href="#">👤 Profile</a>
          <a href="logout.php">🚪 Logout</a>
        </div>
      </div>
    </div>

    <!-- Dashboard Actions -->
    <div class="content-area">
      <h2>Welcome back!</h2>

      <div class="forms-wrapper">
  <!-- Upload Form -->
  <form action="upload.php" method="post" enctype="multipart/form-data" class="upload-form">
    <label>Upload File:</label>
    <input type="file" name="file" required />
    <button type="submit" name="upload">Upload</button>
  </form>

  <!-- Create Folder Form -->
  <form action="create_folder.php" method="post" class="folder-form">
    <label>Create New Folder:</label>
    <input type="text" name="folder_name" placeholder="Folder name" required />
    <button type="submit">Create Folder</button>
  </form>
</div>

    </div>
  </div>

</div>

<script src="sidebar.js"></script>
</body>
</html>
