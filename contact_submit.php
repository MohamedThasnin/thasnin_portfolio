<?php
require_once __DIR__ . "/includes/db.php";

$name    = trim($_POST["name"] ?? "");
$email   = trim($_POST["email"] ?? "");
$subject = trim($_POST["subject"] ?? "");
$message = trim($_POST["message"] ?? "");

if($name === "" || $email === "" || $message === ""){
  header("Location: index.php#contact?err=1");
  exit;
}

if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
  header("Location: index.php#contact?err=2");
  exit;
}

$st = $conn->prepare("INSERT INTO messages (name, email, subject, message) VALUES (?,?,?,?)");
$st->bind_param("ssss", $name, $email, $subject, $message);
$st->execute();

header("Location: index.php#contact?sent=1");
exit;