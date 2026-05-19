<?php
// update_order.php
require_once 'auth.php';
require_once '../api/db.php';

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];

    // 使用預備語法 (Prepared Statement) 防止 SQL 注入
    $stmt = $conn->prepare("UPDATE order_master SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);

    if ($stmt->execute()) {
        // 更新成功後跳轉回原頁面
        header("Location: orders.php?msg=success");
    } else {
        echo "更新失敗: " . $conn->error;
    }
    $stmt->close();
} else {
    header("Location: orders.php");
}
exit();
?>