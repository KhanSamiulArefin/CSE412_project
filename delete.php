<?php
if (isset($_POST['item'])) {
  $item = $_POST['item'];
  $source = "files/" . $item;
  $trash = "files/trash/" . $item;


  if (!file_exists("files/trash")) {
    mkdir("files/trash", 0777, true);
  }


  // Move file/folder to trash
  if (is_dir($source)) {
    rename($source, $trash);
  } else {
    rename($source, $trash);
  }


  header("Location: my_files.php");
  exit();
}
?>



