<?php
header('Content-Type: application/json');
require_once 'db.php';

$name = $_GET['name'] ?? '';
$phone = $_GET['phone'] ?? '';

if (empty($name) || empty($phone)) {
    echo json_encode(["success" => false, "message" => "請輸入姓名與電話"]);
    exit;
}

try {
    // 1. 查詢主訂單
    $stmt = $conn->prepare(
        "SELECT order_no, pickup_date, pickup_time, total_price, status 
         FROM order_master 
         WHERE customer_name = ? AND phone = ? 
         ORDER BY id DESC LIMIT 5" // 建議用 id 或 created_at 排序
    );
    $stmt->bind_param("ss", $name, $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close(); // 用完即關閉

    if (empty($orders)) {
        echo json_encode(["success" => true, "orders" => []]);
        exit;
    }

    // 2. 針對每筆訂單抓取明細
    foreach ($orders as $key => $order) {
        $detail_stmt = $conn->prepare(
            "SELECT product_name, quantity, price, subtotal, options 
             FROM order_detail WHERE order_no = ?"
        );
        $detail_stmt->bind_param("s", $order['order_no']);
        $detail_stmt->execute();
        $orders[$key]['details'] = $detail_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $detail_stmt->close();
    }

    echo json_encode(["success" => true, "orders" => $orders], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // 實務上建議 error_log($e->getMessage()); 方便 debug
    echo json_encode(["success" => false, "message" => "系統查詢失敗，請稍後再試"]);
}

$conn->close();
?>