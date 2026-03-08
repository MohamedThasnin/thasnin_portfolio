<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/helpers.php";
require_once __DIR__ . "/../includes/auth.php";
require_admin();

$rows = [];
$r = $conn->query("SELECT id,title,tech,github_link,live_link,image,created_at FROM projects ORDER BY created_at DESC");
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
  <title>Projects | Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="nav">
  <div class="navInner">
    <div class="brand">
      <div class="logoBox"><b style="color:rgba(234,242,255,.9)">A</b></div>
      <div class="brandTitle"><b>Admin</b><small>Projects</small></div>
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
      <h2>Projects</h2>
      <p>List + Edit + Delete + Image upload ✅</p>
    </div>

    <div class="btnRow" style="margin-bottom:14px">
      <a class="btn primary" href="projects_add.php">+ Add Project</a>
    </div>

    <div class="card">
      <div class="cardTitleRow">
        <div class="cardTitle">All Projects</div>
        <small style="color:rgba(234,242,255,.7)">Total: <?= count($rows) ?></small>
      </div>

      <?php if(count($rows)===0): ?>
        <div class="emptyCard">No projects yet. Click “Add Project”.</div>
      <?php else: ?>
        <div class="list">
          <?php foreach($rows as $p): ?>
            <div class="listRow">
              <div class="thumb">
                <?php if(!empty($p["image"])): ?>
                  <img src="../<?= e($p["image"]) ?>" alt="">
                <?php else: ?>
                  <span>📌</span>
                <?php endif; ?>
              </div>

              <div class="listMain">
                <b><?= e($p["title"]) ?></b>
                <small><?= e($p["tech"]) ?> • <?= e(nice_date($p["created_at"])) ?></small>
              </div>

              <div class="listActions">
                <?php
                  $open = $p["github_link"] ?: ($p["live_link"] ?: "");
                ?>
                <?php if($open): ?>
                  <a class="aBtn" href="<?= e($open) ?>" target="_blank">Open</a>
                <?php endif; ?>
                <a class="aBtn" href="projects_edit.php?id=<?= (int)$p["id"] ?>">Edit</a>
                <a class="aBtn danger" href="projects_delete.php?id=<?= (int)$p["id"] ?>"
                   onclick="return confirm('Delete this project? Image will also be deleted.')">Delete</a>
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
