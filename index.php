<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/helpers.php";

/* ===== helper: check column exists ===== */
function col_exists(mysqli $conn, string $table, string $col): bool {
  $t = $conn->real_escape_string($table);
  $c = $conn->real_escape_string($col);
  $r = $conn->query("SHOW COLUMNS FROM `$t` LIKE '$c'");
  return $r && $r->num_rows > 0;
}

/* ===== helper: get first available profile value from multiple column names ===== */
function pget(array $profile, array $keys, string $default = ""): string {
  foreach ($keys as $k) {
    if (isset($profile[$k]) && trim((string)$profile[$k]) !== "") return (string)$profile[$k];
  }
  return $default;
}

/* ===== helper: pick a column if exists ===== */
function pick_col(mysqli $conn, string $table, array $cands, string $fallback = ""): string {
  foreach ($cands as $c) {
    if (col_exists($conn, $table, $c)) return $c;
  }
  return $fallback;
}

/* ================= PROFILE ================= */
$profile = $conn->query("SELECT * FROM profile WHERE id=1 LIMIT 1")->fetch_assoc();

if (!$profile) {
  if (col_exists($conn, "profile", "id") && col_exists($conn, "profile", "full_name")) {
    $conn->query("INSERT INTO profile (id, full_name) VALUES (1,'') ON DUPLICATE KEY UPDATE id=id");
  } elseif (col_exists($conn, "profile", "id")) {
    $conn->query("INSERT INTO profile (id) VALUES (1) ON DUPLICATE KEY UPDATE id=id");
  }
  $profile = $conn->query("SELECT * FROM profile WHERE id=1 LIMIT 1")->fetch_assoc();
  if (!$profile) $profile = [];
}

$name      = pget($profile, ["full_name", "name"], "Mohamed Thasnin");
$rolesStr  = pget($profile, ["role_title", "roles", "title"], "Web Developer, PHP Developer, Problem Solver");
$aboutText = pget(
  $profile,
  ["bio", "about", "description"],
  "I build modern, responsive websites and dynamic web applications using PHP, MySQL, HTML, CSS, and JavaScript."
);
$photo     = pget($profile, ["profile_image", "profile_photo", "photo"], "uploads/profile/profile.jpg");
$location  = pget($profile, ["location"], "Sri Lanka");
$email     = pget($profile, ["email"], "yourmail@example.com");

$roles = array_values(array_filter(array_map("trim", explode(",", $rolesStr))));

/* ================= CV ================= */
$cvFile = pget($profile, ["cv_file", "cv_path", "cv"], "");
$cvPath = "";
if (!empty($cvFile)) {
  $check = __DIR__ . "/" . ltrim($cvFile, "/");
  $cvPath = file_exists($check) ? ltrim($cvFile, "/") : $cvFile;
}

/* ================= SOCIALS ================= */
$social = [];
$resSoc = $conn->query("SELECT platform, url FROM socials");
if ($resSoc) {
  while ($r = $resSoc->fetch_assoc()) {
    $social[$r["platform"]] = $r["url"];
  }
}

$whatsapp  = $social["whatsapp"]  ?? "https://wa.me/94710326767?text=Hi%20Mohamed%20Thasnin";
$facebook  = $social["facebook"]  ?? "#";
$instagram = $social["instagram"] ?? "#";
$github    = $social["github"]    ?? "https://github.com/";
$linkedin  = $social["linkedin"]  ?? "https://www.linkedin.com/";

/* ================= SKILLS ================= */
$skillsDB = [];
$skillCol = pick_col($conn, "skills", ["name", "skill", "title", "skill_name"], "name");
$sortCol  = pick_col($conn, "skills", ["sort_order", "sort", "position"], "");

$sqlS = "SELECT `$skillCol` AS skill_name FROM skills";
$sqlS .= ($sortCol !== "") ? " ORDER BY `$sortCol` ASC, id DESC" : " ORDER BY id DESC";

$resS = $conn->query($sqlS);
if ($resS) {
  while ($row = $resS->fetch_assoc()) {
    $val = trim($row["skill_name"] ?? "");
    if ($val !== "") $skillsDB[] = $val;
  }
}

/* ================= HIGHLIGHTS ================= */
$highlights = [
  ["title" => "Speciality", "value" => "Web Development"],
  ["title" => "Stack", "value" => "PHP + MySQL"],
  ["title" => "Strength", "value" => "Problem Solving"],
];

/* ================= PROJECTS ================= */
$projects = [];
$resP = $conn->query("SELECT * FROM projects ORDER BY created_at DESC");
if ($resP) while ($row = $resP->fetch_assoc()) $projects[] = $row;

/* ================= ARTICLES ================= */
$articles = [];
$resA = $conn->query("SELECT * FROM articles ORDER BY created_at DESC");
if ($resA) while ($row = $resA->fetch_assoc()) $articles[] = $row;

function nice_date($ts): string {
  if (!$ts) return "";
  $t = strtotime($ts);
  return $t ? date("M d, Y", $t) : "";
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($name) ?> | Portfolio</title>
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
        <b><?= e($name) ?></b>
        <small>Portfolio</small>
      </div>
    </div>

    <nav class="links">
      <a href="#home">Home</a>
      <a href="#about">About</a>
      <a href="#projects">Projects</a>
      <a href="#articles">Articles</a>
      <a href="#contact">Contact</a>
      <a href="admin/dashboard.php">Admin</a>
    </nav>

    <button class="menuBtn" id="menuBtn" type="button">☰</button>
  </div>

  <div class="mobileMenu" id="mobileMenu">
    <a href="#home">Home</a>
    <a href="#about">About</a>
    <a href="#projects">Projects</a>
    <a href="#articles">Articles</a>
    <a href="#contact">Contact</a>
    <a href="admin/dashboard.php">Admin</a>
  </div>
</header>

<main class="container">

  <!-- HERO -->
  <section class="hero" id="home">
    <div class="heroContent">
      <span class="badge">👋 Hello, I’m</span>
      <h1><?= e($name) ?></h1>
      <p class="typing" id="typing"></p>

      <div class="btnRow">
        <a class="btn primary" href="#contact">Contact Me</a>
        <?php if (!empty($cvPath)): ?>
          <a class="btn" href="<?= e($cvPath) ?>" target="_blank" rel="noopener">Download CV</a>
        <?php else: ?>
          <button class="btn disabledBtn" type="button" disabled>CV Not Uploaded</button>
        <?php endif; ?>
      </div>
    </div>

    <div class="avatarWrap">
      <div class="ring">
        <img
          class="avatar"
          src="<?= e($photo) ?>"
          alt="Profile"
          loading="eager"
          decoding="async"
          onerror="this.src='uploads/profile/profile.jpg'">
      </div>
    </div>

    <div class="socialRow">
      <a class="iconBtn wa" href="<?= e($whatsapp) ?>" target="_blank" rel="noopener" aria-label="WhatsApp" title="WhatsApp">
        <svg viewBox="0 0 32 32" aria-hidden="true">
          <path fill="currentColor" d="M19.11 17.53c-.27-.14-1.62-.8-1.87-.89-.25-.09-.44-.14-.62.14-.18.28-.71.89-.87 1.07-.16.18-.32.21-.59.07-.27-.14-1.15-.42-2.2-1.35-.82-.73-1.37-1.63-1.53-1.9-.16-.28-.02-.43.12-.56.12-.12.27-.32.41-.48.14-.16.18-.28.27-.46.09-.18.05-.35-.02-.48-.07-.14-.62-1.49-.85-2.04-.23-.53-.46-.46-.62-.47l-.53-.01c-.18 0-.46.07-.71.35-.25.28-.93.91-.93 2.22s.96 2.58 1.09 2.76c.14.18 1.88 2.86 4.55 4.01.64.28 1.14.44 1.53.56.65.21 1.24.18 1.71.11.52-.08 1.62-.66 1.85-1.33.23-.66.23-1.23.16-1.33-.07-.11-.25-.18-.52-.32z"/>
          <path fill="currentColor" d="M26.67 5.33A14.9 14.9 0 0 0 16.05 1C7.83 1 1.14 7.69 1.14 15.91c0 2.62.68 5.19 1.98 7.45L1 31l7.87-2.07a14.83 14.83 0 0 0 7.18 1.83h.01c8.22 0 14.91-6.69 14.91-14.91 0-3.98-1.55-7.72-4.3-10.52zM16.06 28.2h-.01a12.32 12.32 0 0 1-6.28-1.72l-.45-.27-4.67 1.23 1.25-4.55-.29-.47a12.33 12.33 0 0 1-1.89-6.61C3.72 9.04 9.2 3.56 16.06 3.56c3.32 0 6.44 1.29 8.79 3.64a12.35 12.35 0 0 1 3.64 8.79c0 6.86-5.58 12.21-12.43 12.21z"/>
        </svg>
      </a>

      <a class="iconBtn fb" href="<?= e($facebook) ?>" target="_blank" rel="noopener" aria-label="Facebook" title="Facebook">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path fill="currentColor" d="M22 12.07C22 6.5 17.52 2 12 2S2 6.5 2 12.07C2 17.1 5.66 21.27 10.44 22v-7.03H7.9v-2.9h2.54V9.85c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.23.2 2.23.2v2.46h-1.26c-1.24 0-1.63.78-1.63 1.58v1.9h2.78l-.44 2.9h-2.34V22C18.34 21.27 22 17.1 22 12.07z"/>
        </svg>
      </a>

      <a class="iconBtn ig" href="<?= e($instagram) ?>" target="_blank" rel="noopener" aria-label="Instagram" title="Instagram">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path fill="currentColor" d="M7.5 2h9A5.5 5.5 0 0 1 22 7.5v9A5.5 5.5 0 0 1 16.5 22h-9A5.5 5.5 0 0 1 2 16.5v-9A5.5 5.5 0 0 1 7.5 2zm0 2A3.5 3.5 0 0 0 4 7.5v9A3.5 3.5 0 0 0 7.5 20h9A3.5 3.5 0 0 0 20 16.5v-9A3.5 3.5 0 0 0 16.5 4h-9z"/>
          <path fill="currentColor" d="M12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/>
          <path fill="currentColor" d="M17.75 6.8a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4z"/>
        </svg>
      </a>

      <a class="iconBtn gh" href="<?= e($github) ?>" target="_blank" rel="noopener" aria-label="GitHub" title="GitHub">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path fill="currentColor" d="M12 .5C5.73.5.75 5.6.75 11.96c0 5.1 3.2 9.43 7.64 10.95.56.1.76-.25.76-.55v-1.96c-3.11.69-3.77-1.54-3.77-1.54-.51-1.33-1.25-1.68-1.25-1.68-1.02-.71.08-.69.08-.69 1.13.08 1.73 1.18 1.73 1.18 1.01 1.77 2.65 1.26 3.3.96.1-.75.39-1.26.71-1.55-2.48-.29-5.09-1.26-5.09-5.69 0-1.26.44-2.29 1.17-3.09-.12-.3-.51-1.46.11-3.05 0 0 .96-.31 3.15 1.18.91-.26 1.88-.39 2.84-.4.96.01 1.93.14 2.84.4 2.19-1.49 3.15-1.18 3.15-1.18.62 1.59.23 2.75.11 3.05.73.8 1.17 1.83 1.17 3.09 0 4.44-2.62 5.4-5.12 5.68.4.36.76 1.07.76 2.15v3.19c0 .3.2.66.77.55 4.43-1.52 7.63-5.85 7.63-10.95C23.25 5.6 18.27.5 12 .5z"/>
        </svg>
      </a>

      <a class="iconBtn in" href="<?= e($linkedin) ?>" target="_blank" rel="noopener" aria-label="LinkedIn" title="LinkedIn">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path fill="currentColor" d="M20.45 20.45h-3.55v-5.56c0-1.33-.03-3.04-1.85-3.04-1.86 0-2.14 1.45-2.14 2.95v5.65H9.32V9h3.41v1.56h.05c.47-.9 1.63-1.85 3.36-1.85 3.6 0 4.26 2.37 4.26 5.46v6.28zM5.34 7.43a2.06 2.06 0 1 1 0-4.12 2.06 2.06 0 0 1 0 4.12zM7.11 20.45H3.56V9h3.55v11.45zM22 0H2C.9 0 0 .9 0 2v20c0 1.1.9 2 2 2h20c1.1 0 2-.9 2-2V2c0-1.1-.9-2-2-2z"/>
        </svg>
      </a>
    </div>
  </section>

  <!-- ABOUT -->
  <section class="section" id="about">
    <div class="secHead">
      <h2>About Me</h2>
      <p><?= e($aboutText) ?></p>
    </div>

    <div class="aboutGrid">
      <div class="card">
        <div class="cardTitle">Quick Info</div>
        <div class="infoList">
          <div class="infoRow"><span>Full Name</span><span><?= e($name) ?></span></div>
          <div class="infoRow"><span>Location</span><span><?= e($location) ?></span></div>
          <div class="infoRow"><span>Email</span><span><?= e($email) ?></span></div>
        </div>

        <div class="miniCards">
          <?php foreach ($highlights as $h): ?>
            <div class="mini">
              <div class="miniTop"><?= e($h["title"]) ?></div>
              <div class="miniVal"><?= e($h["value"]) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card">
        <div class="cardTitle">Skills</div>
        <div class="chipWrap">
          <?php if (count($skillsDB) === 0): ?>
            <div class="emptyCard cleanEmpty">No skills added yet. Go to <b>Admin → Skills</b> and add your skills.</div>
          <?php else: ?>
            <?php foreach ($skillsDB as $s): ?>
              <span class="chip"><?= e($s) ?></span>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div class="divider"></div>

        <div class="cardTitle">What I Do</div>
        <ul class="tickList">
          <li>Build responsive websites for mobile and desktop</li>
          <li>Create PHP + MySQL dynamic projects</li>
          <li>Design clean and modern user interfaces</li>
          <li>Learn and improve through real-world projects</li>
        </ul>
      </div>
    </div>
  </section>

  <!-- PROJECTS -->
  <section class="section" id="projects">
    <div class="secHead">
      <h2>Projects</h2>
      <p>Here are some of my recent works and practical development projects.</p>
    </div>

    <div class="projectsGrid">
      <?php if (count($projects) === 0): ?>
        <div class="emptyCard cleanEmpty">No projects added yet.</div>
      <?php else: ?>
        <?php foreach ($projects as $p):
          $main = $p["github_link"] ?: ($p["live_link"] ?: "");
          $img = $p["image"] ?? "";
        ?>
          <article class="pCard <?= $main ? 'clickable' : '' ?>" <?= $main ? 'onclick="window.open(\''.e($main).'\',\'_blank\')"' : '' ?>>
            <div class="pTop">
              <?php if ($img): ?>
                <img class="pImg" src="<?= e($img) ?>" alt="<?= e($p["title"]) ?>">
              <?php else: ?>
                <div class="pImg placeholder"><span>📌</span></div>
              <?php endif; ?>

              <div class="pTitleRow">
                <h3><?= e($p["title"]) ?></h3>
                <div class="pTech"><?= e($p["tech"]) ?></div>
              </div>
            </div>

            <p class="pDesc"><?= e($p["description"]) ?></p>

            <div class="pBtns">
              <?php if (!empty($p["github_link"])): ?>
                <a class="pBtn primary" href="<?= e($p["github_link"]) ?>" target="_blank" rel="noopener" onclick="event.stopPropagation()">GitHub</a>
              <?php endif; ?>
              <?php if (!empty($p["live_link"])): ?>
                <a class="pBtn" href="<?= e($p["live_link"]) ?>" target="_blank" rel="noopener" onclick="event.stopPropagation()">Live</a>
              <?php endif; ?>
              <?php if (empty($p["github_link"]) && empty($p["live_link"])): ?>
                <span class="pHint">Link will be added soon</span>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- ARTICLES -->
  <section class="section" id="articles">
    <div class="secHead">
      <h2>Articles</h2>
      <p>Thoughts, notes, and simple writeups from my learning journey.</p>
    </div>

    <div class="articlesGrid">
      <?php if (count($articles) === 0): ?>
        <div class="emptyCard cleanEmpty">No articles added yet.</div>
      <?php else: ?>
        <?php foreach ($articles as $a):
          $date = nice_date($a["created_at"] ?? "");
          $plain = trim(strip_tags($a["content"] ?? ""));
          $preview = mb_substr($plain, 0, 140) . (mb_strlen($plain) > 140 ? "..." : "");
          $cover = $a["cover_image"] ?? "";
        ?>
          <article class="aCard clickable" onclick="window.location.href='article.php?id=<?= (int)$a['id'] ?>'">
            <?php if ($cover): ?>
              <img class="aCover" src="<?= e($cover) ?>" alt="<?= e($a["title"]) ?>">
            <?php endif; ?>

            <div class="aMeta">
              <span class="aTag">Blog</span>
              <span class="aDate"><?= e($date) ?></span>
            </div>

            <h3 class="aTitle"><?= e($a["title"]) ?></h3>
            <p class="aPreview"><?= e($preview) ?></p>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- CONTACT -->
  <section class="section" id="contact">
    <div class="secHead">
      <h2>Contact</h2>
      <p>Feel free to send me a message. I will get back to you soon.</p>
    </div>

    <?php
      $sent = isset($_GET["sent"]);
      $err  = $_GET["err"] ?? "";
    ?>

    <?php if ($sent): ?>
      <div class="okBox">Message sent successfully ✅</div>
    <?php endif; ?>

    <?php if ($err === "1"): ?>
      <div class="errBox">Please fill Name, Email and Message.</div>
    <?php elseif ($err === "2"): ?>
      <div class="errBox">Invalid email address.</div>
    <?php endif; ?>

    <div class="contactGrid">
      <div class="card">
        <div class="cardTitle">Send Message</div>

        <form class="contactForm" method="post" action="contact_submit.php">
          <label>Name*
            <input name="name" required placeholder="Your name">
          </label>

          <label>Email*
            <input name="email" required placeholder="yourmail@gmail.com">
          </label>

          <label>Subject
            <input name="subject" placeholder="Hiring / Project / Question">
          </label>

          <label>Message*
            <textarea name="message" rows="5" required placeholder="Type your message..."></textarea>
          </label>

          <button class="btn primary" type="submit">Send</button>
        </form>
      </div>

      <div class="card">
        <div class="cardTitle">Direct Contact</div>

        <div class="infoList">
          <div class="infoRow"><span>Email</span><span><?= e($email) ?></span></div>
          <div class="infoRow"><span>Location</span><span><?= e($location) ?></span></div>
        </div>

        <div class="divider"></div>

        <p style="color:rgba(234,242,255,.75);margin:0 0 12px">
          You can also contact me through WhatsApp or LinkedIn.
        </p>

        <a class="btn" href="<?= e($whatsapp) ?>" target="_blank" rel="noopener">WhatsApp Chat</a>
      </div>
    </div>
  </section>

</main>

<script>
  window.__ROLES__ = <?= json_encode($roles) ?>;
</script>
<script src="assets/js/app.js"></script>
</body>
</html>