<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/helpers.php";
require_once __DIR__ . "/../includes/auth.php";
require_admin();

$msg=""; $err="";

$platforms = ["whatsapp","facebook","instagram","github","linkedin"];

if($_SERVER["REQUEST_METHOD"]==="POST"){
  foreach($platforms as $p){
    $url = trim($_POST[$p] ?? "");
    if($url!==""){
      $st=$conn->prepare("INSERT INTO socials (platform,url) VALUES (?,?) ON DUPLICATE KEY UPDATE url=VALUES(url)");
      $st->bind_param("ss",$p,$url);
      $st->execute();
    } else {
      // empty => delete row (optional clean)
      $st=$conn->prepare("DELETE FROM socials WHERE platform=?");
      $st->bind_param("s",$p);
      $st->execute();
    }
  }
  $msg="Social links updated ✅";
}

$rows = $conn->query("SELECT platform,url FROM socials")->fetch_all(MYSQLI_ASSOC);
$social = [];
foreach($rows as $r) $social[$r["platform"]] = $r["url"];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manage Social Links</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<main class="container">
  <section class="section">
    <a class="backLink" href="dashboard.php">← Back to Dashboard</a>

    <div class="readCard">
      <h2 style="margin:0 0 10px">Social Links</h2>
      <p style="margin:0 0 14px;color:rgba(234,242,255,.7)">Paste full links. Empty = removed.</p>

      <?php if($msg): ?><div class="okBox"><?= e($msg) ?></div><?php endif; ?>
      <?php if($err): ?><div class="errBox"><?= e($err) ?></div><?php endif; ?>

      <form method="post" class="formGrid" style="grid-template-columns:1fr">
        <label>WhatsApp (example: https://wa.me/94710326767?text=Hi)
          <input name="whatsapp" value="<?= e($social["whatsapp"] ?? "") ?>">
        </label>
        <label>Facebook
          <input name="facebook" value="<?= e($social["facebook"] ?? "") ?>">
        </label>
        <label>Instagram
          <input name="instagram" value="<?= e($social["instagram"] ?? "") ?>">
        </label>
        <label>GitHub
          <input name="github" value="<?= e($social["github"] ?? "") ?>">
        </label>
        <label>LinkedIn
          <input name="linkedin" value="<?= e($social["linkedin"] ?? "") ?>">
        </label>

        <button class="btn primary" type="submit">Save Links</button>
      </form>
    </div>
  </section>
</main>
</body>
</html>
