<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/helpers.php";
require_once __DIR__ . "/../includes/auth.php";
require_admin();

$msg=""; $err="";

/* ✅ Ensure profile row id=1 exists */
$profile = $conn->query("SELECT * FROM profile WHERE id=1 LIMIT 1")->fetch_assoc();
if(!$profile){
  $conn->query("INSERT INTO profile (id, full_name, role_title, bio) VALUES (1,'','','')");
  $profile = $conn->query("SELECT * FROM profile WHERE id=1 LIMIT 1")->fetch_assoc();
}

if($_SERVER["REQUEST_METHOD"]==="POST"){
  $full_name  = trim($_POST["full_name"] ?? "");
  $roles      = trim($_POST["roles"] ?? "");       // UI name = roles
  $about      = trim($_POST["about"] ?? "");       // UI name = about
  $location   = trim($_POST["location"] ?? "");
  $email      = trim($_POST["email"] ?? "");
  $phone      = trim($_POST["phone"] ?? "");

  if($full_name==="" || $roles==="" || $about===""){
    $err = "Please fill required fields (Name, Roles, About).";
  } else {

    /* ✅ Upload profile image -> DB: profile_image */
    $photoPath = $profile["profile_image"] ?? null;

    if(!empty($_FILES["profile_photo"]["name"])){
      $dir = __DIR__ . "/../uploads/profile/";
      if(!is_dir($dir)) mkdir($dir, 0777, true);

      $ext = strtolower(pathinfo($_FILES["profile_photo"]["name"], PATHINFO_EXTENSION));
      $allow = ["jpg","jpeg","png","webp"];

      if(!in_array($ext,$allow)){
        $err = "Profile photo: Only JPG/PNG/WEBP allowed.";
      } else {
        $new = "profile_" . time() . "_" . rand(1000,9999) . "." . $ext;
        if(move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $dir.$new)){
          // delete old
          if(!empty($profile["profile_image"])){
            $oldAbs = __DIR__ . "/../" . ltrim($profile["profile_image"], "/");
            if(is_file($oldAbs)) @unlink($oldAbs);
          }
          $photoPath = "uploads/profile/".$new;
        } else {
          $err = "Profile photo upload failed.";
        }
      }
    }

    /* ✅ Upload CV -> DB: cv_file */
    $cvPath = $profile["cv_file"] ?? null;

    if($err==="" && !empty($_FILES["cv_file"]["name"])){
      $dir = __DIR__ . "/../uploads/cv/";
      if(!is_dir($dir)) mkdir($dir, 0777, true);

      $ext = strtolower(pathinfo($_FILES["cv_file"]["name"], PATHINFO_EXTENSION));
      $allow = ["pdf","doc","docx"];

      if(!in_array($ext,$allow)){
        $err = "CV: Only PDF/DOC/DOCX allowed.";
      } else {
        $new = "cv_" . time() . "_" . rand(1000,9999) . "." . $ext;
        if(move_uploaded_file($_FILES["cv_file"]["tmp_name"], $dir.$new)){
          // delete old
          if(!empty($profile["cv_file"])){
            $oldAbs = __DIR__ . "/../" . ltrim($profile["cv_file"], "/");
            if(is_file($oldAbs)) @unlink($oldAbs);
          }
          $cvPath = "uploads/cv/".$new;
        } else {
          $err = "CV upload failed.";
        }
      }
    }

    /* ✅ UPDATE — placeholders count == bind_param count (FIXED) */
    if($err===""){
      $st = $conn->prepare("
        UPDATE profile SET
          full_name=?,
          role_title=?,
          bio=?,
          location=?,
          email=?,
          phone=?,
          profile_image=?,
          cv_file=?,
          updated_at=NOW()
        WHERE id=1
      ");
      $st->bind_param(
        "ssssssss",
        $full_name,
        $roles,      // stored into role_title
        $about,      // stored into bio
        $location,
        $email,
        $phone,
        $photoPath,
        $cvPath
      );
      $st->execute();
      $st->close();

      $msg = "Profile updated ✅";
      $profile = $conn->query("SELECT * FROM profile WHERE id=1 LIMIT 1")->fetch_assoc();
    }
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Edit Profile</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<main class="container">
  <section class="section">
    <a class="backLink" href="dashboard.php">← Back to Dashboard</a>

    <div class="readCard">
      <h2 style="margin:0 0 10px">Profile / About / Contact</h2>
      <p style="margin:0 0 14px;color:rgba(234,242,255,.7)">Update name, roles, about, contact details, profile photo & CV.</p>

      <?php if($msg): ?><div class="okBox"><?= e($msg) ?></div><?php endif; ?>
      <?php if($err): ?><div class="errBox"><?= e($err) ?></div><?php endif; ?>

      <div class="previewRow" style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
        <div class="thumb big">
          <?php if(!empty($profile["profile_image"])): ?>
            <img src="../<?= e($profile["profile_image"]) ?>" alt="profile">
          <?php else: ?>
            <span>🙂</span>
          <?php endif; ?>
        </div>

        <div style="min-width:240px">
          <div style="font-weight:900;margin-bottom:8px">Current CV</div>
          <?php if(!empty($profile["cv_file"])): ?>
            <a class="btn" style="display:inline-flex;align-items:center;gap:8px;padding:10px 14px" href="../<?= e($profile["cv_file"]) ?>" target="_blank">📄 Open CV</a>
          <?php else: ?>
            <span style="color:rgba(234,242,255,.65)">No CV uploaded</span>
          <?php endif; ?>
        </div>
      </div>

      <form method="post" enctype="multipart/form-data" class="formGrid">
        <label class="full">Full Name* <input name="full_name" required value="<?= e($profile["full_name"] ?? "") ?>"></label>

        <!-- UI name = roles, DB = role_title -->
        <label class="full">Roles* (comma separated)
          <input name="roles" required value="<?= e($profile["role_title"] ?? "") ?>">
        </label>

        <!-- UI name = about, DB = bio -->
        <label class="full">About* <textarea name="about" rows="6" required><?= e($profile["bio"] ?? "") ?></textarea></label>

        <label>Location <input name="location" value="<?= e($profile["location"] ?? "") ?>"></label>
        <label>Email <input name="email" value="<?= e($profile["email"] ?? "") ?>"></label>
        <label>Phone <input name="phone" value="<?= e($profile["phone"] ?? "") ?>"></label>

        <div class="divider full"></div>

        <!-- UI file name = profile_photo, DB = profile_image -->
        <label class="full">Profile Photo (jpg/png/webp)
          <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp">
        </label>

        <label class="full">CV File (pdf/doc/docx)
          <input type="file" name="cv_file" accept=".pdf,.doc,.docx">
        </label>

        <button class="btn primary full" type="submit">Save Changes</button>
      </form>
    </div>
  </section>
</main>
</body>
</html>