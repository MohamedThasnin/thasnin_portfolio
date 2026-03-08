<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/helpers.php";

if (session_status() === PHP_SESSION_NONE) session_start();

$err = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $username = trim($_POST["username"] ?? "");
  $password = $_POST["password"] ?? "";

  $st = $conn->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
  $st->bind_param("s", $username);
  $st->execute();
  $user = $st->get_result()->fetch_assoc();

  if ($user && password_verify($password, $user["password_hash"])) {
    $_SESSION["admin_id"] = $user["id"];
    $_SESSION["admin_username"] = $user["username"];
    header("Location: admin/dashboard.php");
    exit;
  } else {
    $err = "Invalid username or password";
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Login</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="container">
  <section class="section">
    <div class="readCard">
      <h2 style="margin:0 0 10px">Admin Login</h2>
      <p style="margin:0 0 14px;color:rgba(234,242,255,.7)">Login to manage Projects & Articles.</p>

      <?php if($err): ?>
        <div class="errBox"><?= e($err) ?></div>
      <?php endif; ?>

      <form method="post" class="formGrid" style="grid-template-columns:1fr">
        <label>Username
          <input name="username" required>
        </label>
        <label>Password
          <input type="password" name="password" required>
        </label>
        <button class="btn primary" type="submit">Login</button>
        <a class="backLink" href="index.php">← Back to website</a>
      </form>
    </div>
  </section>
</main>
</body>
</html>
