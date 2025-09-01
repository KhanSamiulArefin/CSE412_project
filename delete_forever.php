<?php
if (isset($_POST['item'])) {
  $item = $_POST['item'];
  $path = "files/trash/" . $item;

  if (is_dir($path)) {
    rmdir($path); // For now, only deletes empty folders
  } else {
    unlink($path);
  }

  header("Location: trash.php");
}
?>
