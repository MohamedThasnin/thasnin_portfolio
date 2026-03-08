<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/helpers.php";
require_once __DIR__ . "/../includes/auth.php";

require_admin();

/* ===== helpers ===== */
function table_exists(mysqli $conn, string $table): bool {
  $t = $conn->real_escape_string($table);
  $r = $conn->query("SHOW TABLES LIKE '$t'");
  return $r && $r->num_rows > 0;
}

function pick_table(mysqli $conn, array $cands): string {
  foreach ($cands as $t) {
    if (table_exists($conn, $t)) return $t;
  }
  return "";
}

function cnt_safe(mysqli $conn, string $table): int {
  if (!$table) return 0;
  $r = $conn->query("SELECT COUNT(*) c FROM `$table`");
  return $r ? (int)$r->fetch_assoc()["c"] : 0;
}

function nice_date($ts){
  $t=strtotime($ts ?? "");
  return $t ? date("M d, Y", $t) : "";
}

/* ===== detect messages table ===== */
$msgTable = pick_table($conn, ["messages","contact_messages","contact_message","contacts","contact","contact_form_messages"]);

/* ===== counts ===== */
$counts = [
  "projects"  => cnt_safe($conn,"projects"),
  "articles"  => cnt_safe($conn,"articles"),
  "skills"    => cnt_safe($conn,"skills"),
  "socials"   => cnt_safe($conn,"socials"),
  "messages"  => cnt_safe($conn,$msgTable),
];

/* ===== latest projects ===== */
$projects = [];
$rP = $conn->query("SELECT id,title,tech,github_link,live_link,image,created_at FROM projects ORDER BY created_at DESC LIMIT 6");
if($rP) while($row=$rP->fetch_assoc()) $projects[]=$row;

/* ===== latest articles ===== */
$articles = [];
$rA = $conn->query("SELECT id,title,cover_image,created_at FROM articles ORDER BY created_at DESC LIMIT 6");
if($rA) while($row=$rA->fetch_assoc()) $articles[]=$row;

/* ===== latest messages (safe) ===== */
$messages = [];
if($msgTable){
  // try common columns
  $cols = $conn->query("SHOW COLUMNS FROM `$msgTable`");
  $has = [];
  if($cols){
    while($c=$cols->fetch_assoc()) $has[$c["Field"]] = true;
  }

  $idCol      = isset($has["id"]) ? "id" : array_key_first($has);
  $nameCol    = isset($has["name"]) ? "name" : (isset($has["full_name"]) ? "full_name" : "");
  $emailCol   = isset($has["email"]) ? "email" : "";
  $subjectCol = isset($has["subject"]) ? "subject" : (isset($has["title"]) ? "title" : "");
  $dateCol    = isset($has["created_at"]) ? "created_at" : (isset($has["date"]) ? "date" : "");

  // build select with fallbacks
  $sel = [];
  $sel[] = "`$idCol` AS id";
  $sel[] = $nameCol ? "`$nameCol` AS name" : "'' AS name";
  $sel[] = $emailCol ? "`$emailCol` AS email" : "'' AS email";
  $sel[] = $subjectCol ? "`$subjectCol` AS subject" : "'' AS subject";
  $sel[] = $dateCol ? "`$dateCol` AS created_at" : "NOW() AS created_at";

  $order = $dateCol ? " ORDER BY `$dateCol` DESC" : "";
  $sqlM = "SELECT ".implode(",", $sel)." FROM `$msgTable` $order LIMIT 6";
  $rM = $conn->query($sqlM);
  if($rM) while($row=$rM->fetch_assoc()) $messages[]=$row;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="nav">
  <div class="navInner">
    <div class="brand">
      <div class="logoBox" title="Admin">
        <svg viewBox="0 0 64 64" aria-hidden="true">
          <path fill="rgba(234,242,255,.92)" d="M32 6c9.9 0 18 8.1 18 18 0 7.2-4.3 13.4-10.5 16.3 5.5 2.4 9.5 7.9 9.5 14.2V58H15v-3.5c0-6.3 4-11.8 9.5-14.2C18.3 37.4 14 31.2 14 24 14 14.1 22.1 6 32 6zm0 8c-5.5 0-10 4.5-10 10s4.5 10 10 10 10-4.5 10-10-4.5-10-10-10z"/>
        </svg>
      </div>
      <div class="brandTitle">
        <b>Admin</b>
        <small>Dashboard</small>
      </div>
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
      <h2>Dashboard</h2>
      <p>Profile + Skills + Social Links + Projects + Articles + Messages ✅</p>
      <?php if(!$msgTable): ?>
        <p style="color:rgba(234,242,255,.7);margin-top:6px">
          ⚠️ Messages table not found. (No error now) — create table OR keep your existing messages page.
        </p>
      <?php endif; ?>
    </div>

    <div class="dashGrid">
      <div class="dashCard">
        <div class="dashTop"><b>Projects</b><span class="dashNum"><?= $counts["projects"] ?></span></div>
        <p>Add GitHub projects with images.</p>
        <div class="btnRow">
          <a class="btn primary" href="projects_add.php">Add</a>
          <a class="btn" href="projects_manage.php">Manage</a>
        </div>
      </div>

      <div class="dashCard">
        <div class="dashTop"><b>Articles</b><span class="dashNum"><?= $counts["articles"] ?></span></div>
        <p>Add blog posts with cover images.</p>
        <div class="btnRow">
          <a class="btn primary" href="articles_add.php">Add</a>
          <a class="btn" href="articles_manage.php">Manage</a>
        </div>
      </div>

      <div class="dashCard">
        <div class="dashTop"><b>Skills</b><span class="dashNum"><?= $counts["skills"] ?></span></div>
        <p>Add / Edit / Delete skills.</p>
        <div class="btnRow">
          <a class="btn primary" href="skills_manage.php">Manage</a>
        </div>
      </div>

      <div class="dashCard">
        <div class="dashTop"><b>Social Links</b><span class="dashNum"><?= $counts["socials"] ?></span></div>
        <p>Update WhatsApp / FB / IG / GitHub / LinkedIn.</p>
        <div class="btnRow">
          <a class="btn primary" href="socials_manage.php">Manage</a>
        </div>
      </div>

      <div class="dashCard">
        <div class="dashTop"><b>Messages</b><span class="dashNum"><?= $counts["messages"] ?></span></div>
        <p>View contact form messages.</p>
        <div class="btnRow">
          <a class="btn primary" href="messages.php">Open Inbox</a>
        </div>
      </div>
    </div>

    <div class="divider"></div>

    <div class="twoCols">

      <div class="card">
        <div class="cardTitleRow">
          <div class="cardTitle">Latest Projects</div>
          <div style="display:flex;gap:10px;align-items:center">
            <a class="miniLink" href="projects_add.php">+ Add</a>
            <a class="miniLink" href="projects_manage.php">Manage</a>
          </div>
        </div>

        <?php if(count($projects)===0): ?>
          <div class="emptyCard">No projects yet.</div>
        <?php else: ?>
          <div class="list">
            <?php foreach($projects as $p): ?>
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
                  <?php $open = $p["github_link"] ?: ($p["live_link"] ?: ""); ?>
                  <?php if($open): ?><a class="aBtn" href="<?= e($open) ?>" target="_blank">Open</a><?php endif; ?>
                  <a class="aBtn" href="projects_edit.php?id=<?= (int)$p["id"] ?>">Edit</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="card">
        <div class="cardTitleRow">
          <div class="cardTitle">Latest Articles</div>
          <div style="display:flex;gap:10px;align-items:center">
            <a class="miniLink" href="articles_add.php">+ Add</a>
            <a class="miniLink" href="articles_manage.php">Manage</a>
          </div>
        </div>

        <?php if(count($articles)===0): ?>
          <div class="emptyCard">No articles yet.</div>
        <?php else: ?>
          <div class="list">
            <?php foreach($articles as $a): ?>
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
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="card">
        <div class="cardTitleRow">
          <div class="cardTitle">Latest Messages</div>
          <a class="miniLink" href="messages.php">Open</a>
        </div>

        <?php if(!$msgTable): ?>
          <div class="emptyCard">Messages table not found (DB). Create it if needed.</div>
        <?php elseif(count($messages)===0): ?>
          <div class="emptyCard">No messages yet.</div>
        <?php else: ?>
          <div class="list">
            <?php foreach($messages as $m): ?>
              <div class="listRow">
                <div class="thumb"><span>💬</span></div>
                <div class="listMain">
                  <b><?= e($m["name"]) ?></b>
                  <small><?= e($m["email"]) ?> • <?= e($m["subject"]) ?> • <?= e(nice_date($m["created_at"])) ?></small>
                </div>
                <div class="listActions">
                  <a class="aBtn" href="messages.php?view=<?= (int)$m["id"] ?>">View</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </div>

  </section>
</main>

</body>
</html>
