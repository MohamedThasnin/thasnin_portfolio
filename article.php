<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/helpers.php";

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) { header("Location: index.php#articles"); exit; }

$st = $conn->prepare("SELECT * FROM articles WHERE id=? LIMIT 1");
$st->bind_param("i", $id);
$st->execute();
$article = $st->get_result()->fetch_assoc();

function nice_date($ts){
  if(!$ts) return "";
  $t = strtotime($ts);
  return $t ? date("M d, Y", $t) : "";
}

$title = $article["title"] ?? "Article Not Found";
$date  = $article ? nice_date($article["created_at"] ?? "") : "";
$content = $article["content"] ?? "";
$cover = $article["cover_image"] ?? "";
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($title) ?> | Mohamed Thasnin</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="nav">
  <div class="navInner">
    <div class="brand">
      <div class="logoBox" title="Logo">
        <svg viewBox="0 0 64 64" aria-hidden="true">
          <path fill="rgba(234,242,255,.92)" d="M14 48V16c0-2.2 1.8-4 4-4h4.2c1.5 0 2.9.8 3.6 2.1L32 25.5l6.2-11.4c.7-1.3 2.1-2.1 3.6-2.1H46c2.2 0 4 1.8 4 4v32h-7V26.8l-8.2 14.6c-.7 1.2-2 1.9-3.4 1.9s-2.7-.7-3.4-1.9L20.9 26.8V48h-6.9z"/>
        </svg>
      </div>
      <div class="brandTitle">
        <b>Mohamed Thasnin</b>
        <small>Portfolio</small>
      </div>
    </div>

    <nav class="links">
      <a href="index.php#home">Home</a>
      <a href="index.php#about">About</a>
      <a href="index.php#projects">Projects</a>
      <a href="index.php#articles" class="active">Articles</a>
      <a href="index.php#contact">Contact</a>
      <a href="admin/dashboard.php">Admin</a>
    </nav>
  </div>
</header>

<main class="container">
  <section class="section">
    <a class="backLink" href="index.php#articles">← Back to Articles</a>

    <div class="readCard">
      <?php if(!$article): ?>
        <div class="emptyCard">Article not found ❌</div>
      <?php else: ?>
        <?php if($cover): ?>
          <img class="readCover" src="<?= e($cover) ?>" alt="<?= e($title) ?>">
        <?php endif; ?>

        <div class="readMeta">
          <span class="aTag">Blog</span>
          <span class="aDate"><?= e($date) ?></span>
        </div>

        <h1 class="readTitle"><?= e($title) ?></h1>

        <div class="readContent"><?= nl2br(e($content)) ?></div>
      <?php endif; ?>
    </div>
  </section>
</main>
</body>
</html>
