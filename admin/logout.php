<?php
session_start();
session_destroy();

// 清除 30 天免登入的 Cookie
setcookie('admin_cookie', '', time() - 3600, "/");

header('Location: login.php');
exit;
?>