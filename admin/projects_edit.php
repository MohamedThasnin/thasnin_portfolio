<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/helpers.php";
require_once __DIR__ . "/../includes/auth.php";
require_admin();

$id = (int)($_GET["id"] ?? 0);
if($id<=0) { header("Location: projects_manage.php"); exit; }

$stmt = $conn->prepare("SELECT * FROM projects WHERE id=? LIMIT 1");
$stmt->bind_param("i",$id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$p){ header("Location: projects_manage.php"); exit; }

$msg = "";
$err = "";

function save_project_image(array $file): array {
  // returns [ok(bool), path(string), error(string)]
  if(empty($file["name"])) return [false,"",""];
  if($file["error"] !== UPLOAD_ERR_OK) return [false,"","Upload error."];
  $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
  $allowed = ["jpg","jpeg","png","webp"];
  if(!in_array($ext,$allowed)) return [false,"","Only JPG/PNG/WEBP allowed."];

  $dirAbs = __DIR__ . "/../uploads/projects/";
  if(!is_dir($dirAbs)) @mkdir($dirAbs, 0777, true);

  $new = "p_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
  $abs = $dirAbs . $new;
  if(!move_uploaded_file($file["tmp_name"], $abs)) return [false,"","Failed to move file."];

  $rel = "uploads/projects/" . $new; // store this in DB
  return [true,$rel,""];
}

if($_SERVER["REQUEST_METHOD"]==="POST"){
  $title = trim($_POST["title"] ?? "");
  $tech  = trim($_POST["tech"] ?? "");
  $desc  = trim($_POST["description"] ?? "");
  $gh    = trim($_POST["github_link"] ?? "");
  $live  = trim($_POST["live_link"] ?? "");

  if($title===""){ $err="Title required."; }
  else{
    $newImage = $p["image"] ?? "";

    // handle new upload
    if(isset($_FILES["image"]) && !empty($_FILES["image"]["name"])){
      [$ok,$path,$e] = save_project_image($_FILES["image"]);
      if(!$ok){ $err = $e ?: "Image upload failed."; }
      else{
        // delete old file
        if(!empty($p["image"])){
          $oldAbs = __DIR__ . "/../" . $p["image"];
          if(is_file($oldAbs)) @unlink($oldAbs);
        }
        $newImage = $path;
      }
    }

    if($err===""){
      $st = $conn->prepare("UPDATE projects SET title=?, tech=?, description=?, github_link=?, live_link=?, image=? WHERE id=?");
      $st->bind_param("ssssssi",$title,$tech,$desc,$gh,$live,$newImage,$id);
      $st->execute();
      $st->close();

      header("Location: projects_manage.php?updated=1");
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
  <title>Edit Project | Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="nav">
  <div class="navInner">
    <div class="brand">
      <div class="logoBox"><b style="color:rgba(234,242,255,.9)">A</b></div>
      <div class="brandTitle"><b>Admin</b><small>Edit Project</small></div>
    </div>
    <nav class="links">
      <a href="projects_manage.php">← Back</a>
      <a href="dashboard.php">Dashboard</a>
      <a href="logout.php">Logout</a>
    </nav>
  </div>
</header>

<main class="container">
  <section class="section">
    <div class="secHead">
      <h2>Edit Project</h2>
      <p>Update details + replace image ✅</p>
    </div>

    <?php if($err): ?><div class="errBox"><?= e($err) ?></div><?php endif; ?>

    <div class="card">
      <div class="cardTitle">Project Details</div>

      <form class="contactForm" method="post" enctype="multipart/form-data">
        <label>Title*
          <input name="title" required value="<?= e($p["title"] ?? "") ?>">
        </label>

        <label>Tech (ex: PHP, MySQL)
          <input name="tech" value="<?= e($p["tech"] ?? "") ?>">
        </label>

        <label>Description
          <textarea name="description" rows="5"><?= e($p["description"] ?? "") ?></textarea>
        </label>

        <label>GitHub Link
          <input name="github_link" value="<?= e($p["github_link"] ?? "") ?>">
        </label>

        <label>Live Link
          <input name="live_link" value="<?= e($p["live_link"] ?? "") ?>">
        </label>

        <div class="divider"></div>

        <label>Replace Image (JPG/PNG/WEBP)
          <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
        </label>

        <?php if(!empty($p["image"])): ?>
          <div style="margin-top:10px">
            <small style="color:rgba(234,242,255,.7)">Current:</small><br>
            <img src="../<?= e($p["image"]) ?>" style="max-width:260px;border-radius:14px;border:1px solid rgba(255,255,255,.12)">
          </div>
        <?php endif; ?>

        <div class="btnRow" style="margin-top:14px">
          <button class="btn primary" type="submit">Save Changes</button>
          <a class="btn" href="projects_manage.php">Cancel</a>
        </div>
      </form>
    </div>

  </section>
</main>
</body>
</html>
