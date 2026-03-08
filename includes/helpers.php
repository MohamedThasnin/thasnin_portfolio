<?php
// includes/helpers.php
function e($str){
  return htmlspecialchars((string)$str, ENT_QUOTES, "UTF-8");
}
