<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/helpers.php";
require_once __DIR__ . "/../includes/auth.php";

require_admin();

$msg=""; $err="";

if($_SERVER["REQUEST_METHOD"]==="POST"){
  $title = trim($_POST["title"] ?? "");
  $description = trim($_POST["description"] ?? "");
  $tech = trim($_POST["tech"] ?? "");
  $live = trim($_POST["live_link"] ?? "");
  $git  = trim($_POST["github_link"] ?? "");

  if($title==="" || $description==="" || $tech===""){
    $err="Fill required fields.";
  } else {
    $imgPath = NULL;

    if(!empty($_FILES["image"]["name"])){
      $dir = __DIR__ . "/../uploads/projects/";
      if(!is_dir($dir)) mkdir($dir, 0777, true);

      $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
      $allow = ["jpg","jpeg","png","webp"];
      if(!in_array($ext,$allow)){
        $err="Only JPG/PNG/WEBP allowed.";
      } else {
        $new = "p_" . time() . "_" . rand(1000,9999) . "." . $ext;
        if(move_uploaded_file($_FILES["image"]["tmp_name"], $dir.$new)){
          $imgPath = "uploads/projects/".$new;
        } else $err="Image upload failed.";
      }
    }

    if($err===""){
      $st = $conn->prepare("INSERT INTO projects (title, description, tech, live_link, github_link, image) VALUES (?,?,?,?,?,?)");
      $st->bind_param("ssssss", $title,$description,$tech,$live,$git,$imgPath);
      $st->execute();
      $msg="Project added ✅";
    }
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Add Project</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<main class="container">
  <section class="section">
    <a class="backLink" href="dashboard.php">← Back</a>

    <div class="readCard">
      <h2 style="margin:0 0 10px">Add Project</h2>

      <?php if($msg): ?><div class="okBox"><?= e($msg) ?></div><?php endif; ?>
      <?php if($err): ?><div class="errBox"><?= e($err) ?></div><?php endif; ?>

      <form method="post" enctype="multipart/form-data" class="formGrid">
        <label>Title* <input name="title" required></label>
        <label>Tech* <input name="tech" required></label>
        <label>Live Link <input name="live_link"></label>
        <label>GitHub Link <input name="github_link"></label>
        <label class="full">Description* <textarea name="description" rows="6" required></textarea></label>
        <label class="full">Project Image <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp"></label>
        <button class="btn primary" type="submit">Save</button>
      </form>
    </div>
  </section>
</main>
</body>
</html>
