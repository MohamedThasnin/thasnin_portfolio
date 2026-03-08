<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/helpers.php";
require_once __DIR__ . "/../includes/auth.php";
require_admin();

$msg=""; $err="";

if($_SERVER["REQUEST_METHOD"]==="POST"){
  $action = $_POST["action"] ?? "";

  if($action==="add"){
    $name = trim($_POST["name"] ?? "");
    $level = trim($_POST["level"] ?? "");
    $sort = (int)($_POST["sort_order"] ?? 0);

    if($name==="") $err="Skill name required.";
    else{
      $st=$conn->prepare("INSERT INTO skills (name, level, sort_order) VALUES (?,?,?)");
      $st->bind_param("ssi",$name,$level,$sort);
      $st->execute();
      $msg="Skill added ✅";
    }
  }

  if($action==="update"){
    $id=(int)($_POST["id"] ?? 0);
    $name = trim($_POST["name"] ?? "");
    $level = trim($_POST["level"] ?? "");
    $sort = (int)($_POST["sort_order"] ?? 0);

    if($id<=0 || $name==="") $err="Invalid update.";
    else{
      $st=$conn->prepare("UPDATE skills SET name=?, level=?, sort_order=? WHERE id=?");
      $st->bind_param("ssii",$name,$level,$sort,$id);
      $st->execute();
      $msg="Skill updated ✅";
    }
  }

  if($action==="delete"){
    $id=(int)($_POST["id"] ?? 0);
    if($id>0){
      $st=$conn->prepare("DELETE FROM skills WHERE id=?");
      $st->bind_param("i",$id);
      $st->execute();
      $msg="Skill deleted ✅";
    }
  }
}

$skills=[];
$r=$conn->query("SELECT * FROM skills ORDER BY sort_order ASC, id DESC");
if($r) while($row=$r->fetch_assoc()) $skills[]=$row;
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manage Skills</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<main class="container">
  <section class="section">
    <a class="backLink" href="dashboard.php">← Back to Dashboard</a>

    <div class="readCard">
      <h2 style="margin:0 0 10px">Skills</h2>

      <?php if($msg): ?><div class="okBox"><?= e($msg) ?></div><?php endif; ?>
      <?php if($err): ?><div class="errBox"><?= e($err) ?></div><?php endif; ?>

      <h3 style="margin:18px 0 10px">Add New Skill</h3>
      <form method="post" class="formGrid">
        <input type="hidden" name="action" value="add">
        <label>Skill Name* <input name="name" required></label>
        <label>Level (optional) <input name="level" placeholder="Beginner / Intermediate / Advanced"></label>
        <label>Sort Order <input name="sort_order" type="number" value="0"></label>
        <div class="full">
          <button class="btn primary" type="submit">Add Skill</button>
        </div>
      </form>

      <div class="divider"></div>

      <h3 style="margin:0 0 10px">Existing Skills</h3>

      <?php if(count($skills)===0): ?>
        <div class="emptyCard">No skills yet.</div>
      <?php else: ?>
        <div class="list">
          <?php foreach($skills as $s): ?>
            <div class="listRow">
              <div class="listMain">
                <b><?= e($s["name"]) ?></b>
                <small><?= e($s["level"] ?? "") ?> • sort: <?= (int)$s["sort_order"] ?></small>
              </div>

              <div class="listActions">
                <!-- Inline edit -->
                <form method="post" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="id" value="<?= (int)$s["id"] ?>">
                  <input name="name" value="<?= e($s["name"]) ?>" style="width:160px">
                  <input name="level" value="<?= e($s["level"] ?? "") ?>" style="width:160px">
                  <input name="sort_order" type="number" value="<?= (int)$s["sort_order"] ?>" style="width:90px">
                  <button class="aBtn" type="submit">Update</button>
                </form>

                <form method="post" onsubmit="return confirm('Delete this skill?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$s["id"] ?>">
                  <button class="aBtn danger" type="submit">Delete</button>
                </form>
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
