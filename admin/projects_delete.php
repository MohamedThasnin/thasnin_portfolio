<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/helpers.php";
require_once __DIR__ . "/../includes/auth.php";
require_admin();

$id = (int)($_GET["id"] ?? 0);
if($id<=0){ header("Location: projects_manage.php"); exit; }

// get image path first
$st = $conn->prepare("SELECT image FROM projects WHERE id=? LIMIT 1");
$st->bind_param("i",$id);
$st->execute();
$row = $st->get_result()->fetch_assoc();
$st->close();

if($row){
  // delete db row
  $del = $conn->prepare("DELETE FROM projects WHERE id=?");
  $del->bind_param("i",$id);
  $del->execute();
  $del->close();

  // delete file
  $img = $row["image"] ?? "";
  if($img){
    $abs = __DIR__ . "/../" . $img;
    if(is_file($abs)) @unlink($abs);
  }
}

header("Location: projects_manage.php?deleted=1");
exit;
