<?php
header('Content-Type: application/json');
require_once 'db.php';

// 取得前端傳來的 JSON 資料
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['items'])) {
    echo json_encode([
        "success" => false,
        "message" => "訂單資料錯誤"
    ]);
    exit;
}

$customer = $data['customer'];
$items = $data['items'];

// 計算總金額
$total = 0;
foreach ($items as $item) {
    $total += $item['subtotal'];
}

// 啟動交易(Transaction)，確保主單與明細同時成功或失敗
$conn->begin_transaction();

try {
    // 1. 新增主訂單 (order_no 先留空，等待 ID 產生)
    $stmt = $conn->prepare(
        "INSERT INTO order_master 
        (customer_name, phone, pickup_date, pickup_time, total_price, status) 
        VALUES (?, ?, ?, ?, ?, '待處理')"
    );

    $stmt->bind_param(
        "ssssi",
        $customer['name'],
        $customer['phone'],
        $customer['date'],
        $customer['time'],
        $total
    );

    $stmt->execute();

    // 2. 取得剛剛產生的自動遞增 ID (流水號關鍵)
    $new_id = $conn->insert_id;

    // 3. 格式化流水號：WM + 當前年月日 + 補零至四位的ID
    $order_no = "WM" . date("Ymd") . str_pad($new_id, 4, "0", STR_PAD_LEFT);

    // 4. 更新該筆訂單的 order_no 欄位
    $conn->query("UPDATE order_master SET order_no = '$order_no' WHERE id = $new_id");

    // 5. 新增訂單明細
    $detail = $conn->prepare(
        "INSERT INTO order_detail 
        (order_no, product_name, quantity, price, subtotal, options) 
        VALUES (?, ?, ?, ?, ?, ?)"
    );

    foreach ($items as $item) {
        // 如果有備註，將其與選項文字合併存入資料庫
        $final_options = $item['options'];
        if (!empty($item['note'])) {
            $final_options .= " (備註: " . $item['note'] . ")";
        }

        $detail->bind_param(
            "ssiiis",
            $order_no,
            $item['name'],
            $item['qty'],
            $item['price'],
            $item['subtotal'],
            $final_options
        );

        $detail->execute();
    }

    $conn->commit();

    echo json_encode([
        "success" => true,
        "order_no" => $order_no
    ]);

} catch(Exception $e) {
    $conn->rollback();
    echo json_encode([
        "success" => false,
        "message" => "資料庫寫入失敗: " . $e->getMessage()
    ]);
}

$conn->close();
?>