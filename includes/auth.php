<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) session_start();

function require_admin(){
  if (empty($_SESSION["admin_id"])) {
    header("Location: ../login.php");
    exit;
  }
}
