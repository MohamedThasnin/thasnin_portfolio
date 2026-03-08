<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/helpers.php";
require_once __DIR__ . "/../includes/auth.php";

require_admin();

$msg=""; $err="";

if($_SERVER["REQUEST_METHOD"]==="POST"){
  $title = trim($_POST["title"] ?? "");
  $content = trim($_POST["content"] ?? "");

  if($title==="" || $content===""){
    $err="Fill required fields.";
  } else {
    $coverPath = NULL;

    if(!empty($_FILES["cover"]["name"])){
      $dir = __DIR__ . "/../uploads/articles/";
      if(!is_dir($dir)) mkdir($dir, 0777, true);

      $ext = strtolower(pathinfo($_FILES["cover"]["name"], PATHINFO_EXTENSION));
      $allow = ["jpg","jpeg","png","webp"];
      if(!in_array($ext,$allow)){
        $err="Only JPG/PNG/WEBP allowed.";
      } else {
        $new = "a_" . time() . "_" . rand(1000,9999) . "." . $ext;
        if(move_uploaded_file($_FILES["cover"]["tmp_name"], $dir.$new)){
          $coverPath = "uploads/articles/".$new;
        } else $err="Cover upload failed.";
      }
    }

    if($err===""){
      $st = $conn->prepare("INSERT INTO articles (title, content, cover_image) VALUES (?,?,?)");
      $st->bind_param("sss", $title,$content,$coverPath);
      $st->execute();
      $msg="Article added ✅";
    }
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Add Article</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<main class="container">
  <section class="section">
    <a class="backLink" href="dashboard.php">← Back</a>

    <div class="readCard">
      <h2 style="margin:0 0 10px">Add Article</h2>

      <?php if($msg): ?><div class="okBox"><?= e($msg) ?></div><?php endif; ?>
      <?php if($err): ?><div class="errBox"><?= e($err) ?></div><?php endif; ?>

      <form method="post" enctype="multipart/form-data" class="formGrid">
        <label class="full">Title* <input name="title" required></label>
        <label class="full">Cover Image <input type="file" name="cover" accept=".jpg,.jpeg,.png,.webp"></label>
        <label class="full">Content* <textarea name="content" rows="9" required></textarea></label>
        <button class="btn primary" type="submit">Save</button>
      </form>
    </div>
  </section>
</main>
</body>
</html>
