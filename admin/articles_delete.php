<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/helpers.php";
require_once __DIR__ . "/../includes/auth.php";
require_admin();

$id=(int)($_GET["id"] ?? 0);
if($id<=0){ header("Location: articles_manage.php"); exit; }

$st=$conn->prepare("SELECT cover_image FROM articles WHERE id=? LIMIT 1");
$st->bind_param("i",$id);
$st->execute();
$row=$st->get_result()->fetch_assoc();
$st->close();

if($row){
  $del=$conn->prepare("DELETE FROM articles WHERE id=?");
  $del->bind_param("i",$id);
  $del->execute();
  $del->close();

  $cover=$row["cover_image"] ?? "";
  if($cover){
    $abs=__DIR__."/../".$cover;
    if(is_file($abs)) @unlink($abs);
  }
}

header("Location: articles_manage.php?deleted=1");
exit;
