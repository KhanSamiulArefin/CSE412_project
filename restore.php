<?php
if (isset($_POST['item'])) {
  $item = $_POST['item'];
  $trash = "files/trash/" . $item;
  $restore = "files/" . $item;

  rename($trash, $restore);
  header("Location: trash.php");
}
?>
