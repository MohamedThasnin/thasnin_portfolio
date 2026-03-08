<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/helpers.php";
require_once __DIR__ . "/../includes/auth.php";
require_admin();

$id=(int)($_GET["id"] ?? 0);
if($id<=0){ header("Location: articles_manage.php"); exit; }

$st=$conn->prepare("SELECT * FROM articles WHERE id=? LIMIT 1");
$st->bind_param("i",$id);
$st->execute();
$a=$st->get_result()->fetch_assoc();
$st->close();

if(!$a){ header("Location: articles_manage.php"); exit; }

$err="";

function save_cover(array $file): array {
  if(empty($file["name"])) return [false,"",""];
  if($file["error"]!==UPLOAD_ERR_OK) return [false,"","Upload error."];
  $ext=strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
  $allowed=["jpg","jpeg","png","webp"];
  if(!in_array($ext,$allowed)) return [false,"","Only JPG/PNG/WEBP allowed."];

  $dirAbs = __DIR__ . "/../uploads/articles/";
  if(!is_dir($dirAbs)) @mkdir($dirAbs, 0777, true);

  $new="a_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
  $abs=$dirAbs.$new;
  if(!move_uploaded_file($file["tmp_name"], $abs)) return [false,"","Failed to move file."];

  $rel="uploads/articles/".$new;
  return [true,$rel,""];
}

if($_SERVER["REQUEST_METHOD"]==="POST"){
  $title=trim($_POST["title"] ?? "");
  $content=trim($_POST["content"] ?? "");

  if($title===""){ $err="Title required."; }
  else{
    $newCover = $a["cover_image"] ?? "";

    if(isset($_FILES["cover"]) && !empty($_FILES["cover"]["name"])){
      [$ok,$path,$e]=save_cover($_FILES["cover"]);
      if(!$ok){ $err=$e ?: "Cover upload failed."; }
      else{
        if(!empty($a["cover_image"])){
          $oldAbs=__DIR__."/../".$a["cover_image"];
          if(is_file($oldAbs)) @unlink($oldAbs);
        }
        $newCover=$path;
      }
    }

    if($err===""){
      $up=$conn->prepare("UPDATE articles SET title=?, content=?, cover_image=? WHERE id=?");
      $up->bind_param("sssi",$title,$content,$newCover,$id);
      $up->execute();
      $up->close();

      header("Location: articles_manage.php?updated=1");
      exit;
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Edit Article | Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="nav">
  <div class="navInner">
    <div class="brand">
      <div class="logoBox"><b style="color:rgba(234,242,255,.9)">A</b></div>
      <div class="brandTitle"><b>Admin</b><small>Edit Article</small></div>
    </div>
    <nav class="links">
      <a href="articles_manage.php">← Back</a>
      <a href="dashboard.php">Dashboard</a>
      <a href="logout.php">Logout</a>
    </nav>
  </div>
</header>

<main class="container">
  <section class="section">
    <div class="secHead">
      <h2>Edit Article</h2>
      <p>Update content + replace cover ✅</p>
    </div>

    <?php if($err): ?><div class="errBox"><?= e($err) ?></div><?php endif; ?>

    <div class="card">
      <div class="cardTitle">Article Details</div>

      <form class="contactForm" method="post" enctype="multipart/form-data">
        <label>Title*
          <input name="title" required value="<?= e($a["title"] ?? "") ?>">
        </label>

        <label>Content (HTML allowed)
          <textarea name="content" rows="10"><?= e($a["content"] ?? "") ?></textarea>
        </label>

        <div class="divider"></div>

        <label>Replace Cover (JPG/PNG/WEBP)
          <input type="file" name="cover" accept=".jpg,.jpeg,.png,.webp">
        </label>

        <?php if(!empty($a["cover_image"])): ?>
          <div style="margin-top:10px">
            <small style="color:rgba(234,242,255,.7)">Current:</small><br>
            <img src="../<?= e($a["cover_image"]) ?>" style="max-width:320px;border-radius:14px;border:1px solid rgba(255,255,255,.12)">
          </div>
        <?php endif; ?>

        <div class="btnRow" style="margin-top:14px">
          <button class="btn primary" type="submit">Save Changes</button>
          <a class="btn" href="articles_manage.php">Cancel</a>
        </div>
      </form>
    </div>

  </section>
</main>
</body>
</html>
