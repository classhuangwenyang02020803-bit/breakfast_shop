<?php
session_start();

// 1. 如果 Session 不存在，但有 30天免登入的 Cookie，自動恢復登入狀態
if (!isset($_SESSION['admin']) && isset($_COOKIE['admin_cookie'])) {
    $_SESSION['admin'] = $_COOKIE['admin_cookie'];
}

// 2. 如果連 Cookie 都沒有，就踢回登入頁
if(!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
?>