<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔒 鐵門：只要發現不是 admin，立刻吐出失敗訊息並中斷
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        "success" => false,
        "message" => "【權限不足】一般員工無法修改或刪除商品資料！"
    ]);
    exit; // 直接切斷，不執行下方的資料庫操作
}

require_once 'auth.php';
require_once '../api/db.php';

$id = intval($_GET['id']);

$stmt = $conn->prepare(
    "DELETE FROM products
    WHERE id=?"
);

$stmt->bind_param("i", $id);

$stmt->execute();

header('Location: products.php');
exit;
?>