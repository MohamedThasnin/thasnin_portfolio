<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/helpers.php";
require_once __DIR__ . "/../includes/auth.php";

require_admin();

function nice_date($ts){
  $t = strtotime($ts ?? "");
  return $t ? date("M d, Y h:i A", $t) : "";
}

$viewId = isset($_GET["view"]) ? (int)$_GET["view"] : 0;
$delId  = isset($_GET["del"])  ? (int)$_GET["del"]  : 0;

/* --- detect messages table name --- */
function table_exists(mysqli $conn, string $table): bool {
  $t = $conn->real_escape_string($table);
  $r = $conn->query("SHOW TABLES LIKE '$t'");
  return $r && $r->num_rows > 0;
}
function pick_table(mysqli $conn, array $cands): string {
  foreach($cands as $t){ if(table_exists($conn,$t)) return $t; }
  return "";
}
$msgTable = pick_table($conn, ["messages","contact_messages","contact_message","contacts","contact","contact_form_messages"]);
if(!$msgTable){
  die("Messages table not found in DB.");
}

/* --- columns map (safe) --- */
$cols = $conn->query("SHOW COLUMNS FROM `$msgTable`");
$has = [];
while($cols && $c=$cols->fetch_assoc()) $has[$c["Field"]] = true;

$idCol      = isset($has["id"]) ? "id" : array_key_first($has);
$nameCol    = isset($has["name"]) ? "name" : (isset($has["full_name"]) ? "full_name" : "");
$emailCol   = isset($has["email"]) ? "email" : "";
$subjectCol = isset($has["subject"]) ? "subject" : (isset($has["title"]) ? "title" : "");
$msgCol     = isset($has["message"]) ? "message" : (isset($has["content"]) ? "content" : "");
$dateCol    = isset($has["created_at"]) ? "created_at" : (isset($has["date"]) ? "date" : "");

/* --- delete --- */
if($delId > 0){
  $stmt = $conn->prepare("DELETE FROM `$msgTable` WHERE `$idCol`=?");
  $stmt->bind_param("i",$delId);
  $stmt->execute();
  header("Location: messages.php?deleted=1");
  exit;
}

/* --- inbox list --- */
$inbox = [];
$order = $dateCol ? " ORDER BY `$dateCol` DESC" : " ORDER BY `$idCol` DESC";
$sqlList = "SELECT
  `$idCol` AS id,
  ".($nameCol? "`$nameCol` AS name" : "'' AS name").",
  ".($emailCol? "`$emailCol` AS email" : "'' AS email").",
  ".($subjectCol? "`$subjectCol` AS subject" : "'' AS subject").",
  ".($dateCol? "`$dateCol` AS created_at" : "NOW() AS created_at")."
  FROM `$msgTable` $order";
$r = $conn->query($sqlList);
if($r) while($row=$r->fetch_assoc()) $inbox[] = $row;

$total = count($inbox);

/* --- view message --- */
$view = null;
if($viewId > 0){
  $sqlV = "SELECT
    `$idCol` AS id,
    ".($nameCol? "`$nameCol` AS name" : "'' AS name").",
    ".($emailCol? "`$emailCol` AS email" : "'' AS email").",
    ".($subjectCol? "`$subjectCol` AS subject" : "'' AS subject").",
    ".($msgCol? "`$msgCol` AS message" : "'' AS message").",
    ".($dateCol? "`$dateCol` AS created_at" : "NOW() AS created_at")."
    FROM `$msgTable` WHERE `$idCol`=? LIMIT 1";
  $st = $conn->prepare($sqlV);
  $st->bind_param("i",$viewId);
  $st->execute();
  $view = $st->get_result()->fetch_assoc();
}

$deleted = isset($_GET["deleted"]);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Messages | Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .actionsCell{ display:flex; gap:10px; justify-content:flex-end; flex-wrap:nowrap; }
    .btnSm{
      display:inline-flex; align-items:center; justify-content:center;
      height:38px; min-width:76px; padding:0 14px;
      border-radius:14px; font-weight:700; font-size:14px;
      border:1px solid rgba(234,242,255,.16);
      background:rgba(255,255,255,.06);
      color:rgba(234,242,255,.92);
      text-decoration:none;
      transition:.18s ease;
      cursor:pointer;
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
    }
    .btnView{
      border-color: rgba(56,189,248,.35);
      background: linear-gradient(135deg, rgba(14,165,233,.35), rgba(139,92,246,.28));
    }
    .btnView:hover{ transform: translateY(-1px); filter:brightness(1.08); }
    .btnDel{
      border-color: rgba(239,68,68,.45);
      background: rgba(239,68,68,.08);
      color: rgba(255,230,230,.95);
    }
    .btnDel:hover{ background: rgba(239,68,68,.22); transform: translateY(-1px); }
    .msgPreviewBox{
      padding:16px 18px;
      border-radius:18px;
      background: rgba(255,255,255,.04);
      border:1px solid rgba(234,242,255,.10);
      color: rgba(234,242,255,.92);
      line-height:1.55;
      white-space: pre-wrap;
    }
    .toastOk{
      margin: 10px 0 0;
      padding: 10px 14px;
      border-radius: 14px;
      background: rgba(34,197,94,.12);
      border: 1px solid rgba(34,197,94,.35);
      color: rgba(234,242,255,.92);
      display:inline-block;
    }
    .tableWrap{ overflow:auto; }
    table{ width:100%; border-collapse:separate; border-spacing:0 10px; }
    th{ text-align:left; color: rgba(234,242,255,.72); font-weight:700; font-size:13px; padding:0 10px; }
    td{
      padding: 10px;
      background: rgba(255,255,255,.03);
      border-top:1px solid rgba(234,242,255,.08);
      border-bottom:1px solid rgba(234,242,255,.08);
      color: rgba(234,242,255,.92);
      vertical-align: middle;
    }
    td:first-child{ border-left:1px solid rgba(234,242,255,.08); border-radius:14px 0 0 14px; }
    td:last-child{ border-right:1px solid rgba(234,242,255,.08); border-radius:0 14px 14px 0; }
    .muted{ color: rgba(234,242,255,.68); font-size:13px; }
  </style>
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
        <b>Admin Panel</b>
        <small>Thasnin Portfolio</small>
      </div>
    </div>

    <nav class="links">
      <a href="dashboard.php">Dashboard</a>
      <a href="profile_edit.php">Profile</a>
      <a href="skills_manage.php">Skills</a>
      <a href="projects_manage.php">Projects</a>
      <a href="articles_manage.php">Articles</a>
      <a href="messages.php" class="active">Messages</a>
      <a href="../index.php" target="_blank">View Site</a>
      <a href="logout.php">Logout</a>
    </nav>
  </div>
</header>

<main class="container">
  <section class="section">
    <div class="secHead">
      <h2>Contact Messages</h2>
      <p>Total: <?= (int)$total ?> message(s)</p>
      <?php if($deleted): ?>
        <div class="toastOk">Deleted ✅</div>
      <?php endif; ?>
    </div>

    <!-- VIEW PANEL -->
    <div class="card">
      <div class="cardTitle">Select a message to view</div>

      <?php if(!$view): ?>
        <div class="msgPreviewBox muted">Inbox-la irukura message la “View” click pannu ✅</div>
      <?php else: ?>
        <div class="infoList">
          <div class="infoRow"><span>Name</span><b><?= e($view["name"]) ?></b></div>
          <div class="infoRow"><span>Email</span><b><?= e($view["email"]) ?></b></div>
          <div class="infoRow"><span>Subject</span><b><?= e($view["subject"]) ?></b></div>
          <div class="infoRow"><span>Date</span><b><?= e(nice_date($view["created_at"])) ?></b></div>
        </div>

        <div class="divider"></div>

        <div class="msgPreviewBox"><?= e($view["message"]) ?></div>

        <div class="btnRow" style="margin-top:14px">
          <a class="btn" href="messages.php">Back</a>

          <a class="btn primary" target="_blank"
             href="https://mail.google.com/mail/?view=cm&fs=1&to=<?= urlencode($view["email"]) ?>&su=<?= urlencode('Re: '.$view["subject"]) ?>">
             Reply Email
          </a>

          <a class="btn" style="border-color:rgba(239,68,68,.55);color:rgba(255,230,230,.95);background:rgba(239,68,68,.10)"
             href="messages.php?del=<?= (int)$view["id"] ?>" onclick="return confirm('Delete this message?')">Delete</a>
        </div>
      <?php endif; ?>
    </div>

    <div class="divider"></div>

    <!-- INBOX TABLE -->
    <div class="card">
      <div class="cardTitle">Inbox</div>

      <?php if($total===0): ?>
        <div class="emptyCard">No messages yet.</div>
      <?php else: ?>
        <div class="tableWrap">
          <table>
            <thead>
              <tr>
                <th style="width:60px">#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Subject</th>
                <th style="width:170px">Date</th>
                <th style="width:220px;text-align:right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($inbox as $m): ?>
                <tr>
                  <td><?= (int)$m["id"] ?></td>
                  <td><?= e($m["name"]) ?></td>
                  <td><?= e($m["email"]) ?></td>
                  <td><?= e($m["subject"]) ?></td>
                  <td><?= e(nice_date($m["created_at"])) ?></td>
                  <td>
                    <div class="actionsCell">
                      <a class="btnSm btnView" href="messages.php?view=<?= (int)$m["id"] ?>">View</a>
                      <a class="btnSm btnDel" href="messages.php?del=<?= (int)$m["id"] ?>" onclick="return confirm('Delete this message?')">Delete</a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </section>
</main>

</body>
</html>