<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/helpers.php";
require_once __DIR__ . "/../includes/auth.php";
require_admin();

$rows=[];
$r=$conn->query("SELECT id,title,cover_image,created_at FROM articles ORDER BY created_at DESC");
if($r) while($x=$r->fetch_assoc()) $rows[]=$x;

function nice_date($ts){
  $t=strtotime($ts ?? "");
  return $t ? date("M d, Y", $t) : "";
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Articles | Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="nav">
  <div class="navInner">
    <div class="brand">
      <div class="logoBox"><b style="color:rgba(234,242,255,.9)">A</b></div>
      <div class="brandTitle"><b>Admin</b><small>Articles</small></div>
    </div>
    <nav class="links">
      <a href="dashboard.php">Dashboard</a>
      <a href="profile_edit.php">Profile</a>
      <a href="skills_manage.php">Skills</a>
      <a href="projects_manage.php">Projects</a>
      <a href="articles_manage.php">Articles</a>
      <a href="messages.php">Messages</a>
      <a href="../index.php" target="_blank">View Site</a>
      <a href="logout.php">Logout</a>
    </nav>
  </div>
</header>

<main class="container">
  <section class="section">
    <div class="secHead">
      <h2>Articles</h2>
      <p>List + Edit + Delete + Cover upload ✅</p>
    </div>

    <div class="btnRow" style="margin-bottom:14px">
      <a class="btn primary" href="articles_add.php">+ Add Article</a>
    </div>

    <div class="card">
      <div class="cardTitleRow">
        <div class="cardTitle">All Articles</div>
        <small style="color:rgba(234,242,255,.7)">Total: <?= count($rows) ?></small>
      </div>

      <?php if(count($rows)===0): ?>
        <div class="emptyCard">No articles yet. Click “Add Article”.</div>
      <?php else: ?>
        <div class="list">
          <?php foreach($rows as $a): ?>
            <div class="listRow">
              <div class="thumb">
                <?php if(!empty($a["cover_image"])): ?>
                  <img src="../<?= e($a["cover_image"]) ?>" alt="">
                <?php else: ?>
                  <span>📝</span>
                <?php endif; ?>
              </div>

              <div class="listMain">
                <b><?= e($a["title"]) ?></b>
                <small><?= e(nice_date($a["created_at"])) ?></small>
              </div>

              <div class="listActions">
                <a class="aBtn" href="../article.php?id=<?= (int)$a["id"] ?>" target="_blank">View</a>
                <a class="aBtn" href="articles_edit.php?id=<?= (int)$a["id"] ?>">Edit</a>
                <a class="aBtn danger" href="articles_delete.php?id=<?= (int)$a["id"] ?>"
                   onclick="return confirm('Delete this article? Cover image will also be deleted.')">Delete</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </section>
</main>
</body>
</html>
