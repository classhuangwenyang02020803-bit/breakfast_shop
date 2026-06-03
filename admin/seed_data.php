<?php
require_once 'auth.php';
require_once '../api/db.php';

if ($_SESSION['role'] !== 'admin') {
    die("權限不足");
}

set_time_limit(0);

echo "<h2>🚀 王媽媽早餐店 — 5,000筆【真人消費者行為心理學大數據】注入中...</h2>";
echo "<p>正在模擬客戶『初訪摸索期』到『常客偏好平穩期』點餐軌跡，請勿關閉網頁...</p>";
flush(); 

$products_pool = [
    ['name' => '水煎包', 'price' => 20],       
    ['name' => '韭菜盒', 'price' => 35],       
    ['name' => '韭菜煎餃 (個)', 'price' => 7],
    ['name' => '皮蛋瘦肉粥', 'price' => 55],
    ['name' => '蔥大餅(片)', 'price' => 30],
    ['name' => '豆漿', 'price' => 20],
    ['name' => '奶茶', 'price' => 25]
];

// 🌟【行為心理學核心一】：建立 120 組熟客，每人都有自己的生活作息，以及「他試吃後決定愛上的本命偏好餐點」
$vip_profile_pool = [];
for ($i = 0; $i < 120; $i++) {
    $phone = '09' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
    
    // 鎖定生理時鐘
    $roll = rand(1, 100);
    if ($roll <= 60) {
        $base_hour = 7; $base_minute = rand(15, 59);
    } elseif ($roll <= 85) {
        $base_hour = 6; $base_minute = rand(15, 50);
    } else {
        $base_hour = rand(8, 9); $base_minute = ($base_hour == 8) ? rand(45, 59) : rand(0, 30);
    }

    // 🎯 隨機決定這位常客試吃後，會「上癮」的 1~2 個本命餐點
    $favorite_keys = (array)array_rand($products_pool, rand(1, 2));
    $my_favorites = [];
    foreach($favorite_keys as $key) {
        $my_favorites[] = $products_pool[$key];
    }

    $vip_profile_pool[$phone] = [
        'fixed_hour' => $base_hour,
        'fixed_minute' => $base_minute,
        'favorites' => $my_favorites,
        'order_count' => 0 // 用來紀錄他是第幾次購買
    ];
}

// 數據大掃除
$conn->query("SET FOREIGN_KEY_CHECKS = 0;");
$conn->query("TRUNCATE TABLE order_detail;");
$conn->query("TRUNCATE TABLE order_master;");
$conn->query("SET FOREIGN_KEY_CHECKS = 1;");

$current_year_month = date('Y-m'); 
$total_inserted_orders = 0;
$order_counter = 1; 

$conn->begin_transaction();

try {
    for ($day = 1; $day <= 28; $day++) {
        $target_date = $current_year_month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
        $day_of_week = date('N', strtotime($target_date));

        if ($day_of_week == 1) continue; // 週一公休

        $daily_customers = ($day_of_week == 6 || $day_of_week == 7) ? rand(220, 250) : rand(150, 180);

        for ($c = 0; $c < $daily_customers; $c++) {
            $is_vip = (rand(1, 100) <= 65);
            $chosen_items = [];

            if ($is_vip) {
                $phone = array_rand($vip_profile_pool);
                $vip = &$vip_profile_pool[$phone];
                $vip['order_count']++;

                // 🌟【行為心理學核心二】：點餐偏好矩陣轉移
                if ($vip['order_count'] <= 5) {
                    // 摸索期 (前5次)：每天換不同的隨機亂點
                    $items_count = rand(1, 3);
                    $rand_keys = (array)array_rand($products_pool, $items_count);
                    foreach($rand_keys as $k) $chosen_items[] = $products_pool[$k];
                } else {
                    // 平穩期 (第6次以後)：有 75% 機率高度傾向吃本命餐點，25% 換口味
                    if (rand(1, 100) <= 75) {
                        $chosen_items = $vip['favorites'];
                    } else {
                        $chosen_items[] = $products_pool[array_rand($products_pool)];
                    }
                }

                // 擬真時間小波動
                $minute_fluctuation = rand(-10, 10);
                $final_minute = $vip['fixed_minute'] + $minute_fluctuation;
                $final_hour = $vip['fixed_hour'];
                if ($final_minute >= 60) { $final_minute -= 60; $final_hour++; }
                elseif ($final_minute < 0) { $final_minute += 60; $final_hour--; }
                $pickup_time = str_pad($final_hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($final_minute, 2, '0', STR_PAD_LEFT);

            } else {
                // 散客派單：維持大眾生理尖峰波峰
                $phone = '09' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
                $wave_roll = rand(1, 100);
                if ($wave_roll <= 55) {
                    $pickup_time = '07:' . str_pad(rand(15, 59), 2, '0', STR_PAD_LEFT);
                } elseif ($wave_roll <= 80) {
                    $pickup_time = '06:' . str_pad(rand(15, 59), 2, '0', STR_PAD_LEFT);
                } else {
                    $h = rand(8, 9); $m = ($h == 8) ? rand(45, 59) : rand(0, 35);
                    $pickup_time = str_pad($h, 2, '0', STR_PAD_LEFT) . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
                }
                
                $items_count = rand(1, 2);
                $rand_keys = (array)array_rand($products_pool, $items_count);
                foreach($rand_keys as $k) $chosen_items[] = $products_pool[$k];
            }
            
            $order_no = 'BK' . date('Ymd', strtotime($target_date)) . str_pad($order_counter, 5, '0', STR_PAD_LEFT);
            $total_price = 0;
            $order_details = [];

            foreach ($chosen_items as $prod) {
                $qty = rand(1, 2);
                $subtotal = $prod['price'] * $qty;
                $total_price += $subtotal;
                $order_details[] = ['product_name' => $prod['name'], 'price' => $prod['price'], 'quantity' => $qty];
            }

            $stmt1 = $conn->prepare("INSERT INTO order_master (order_no, phone, total_price, pickup_date, pickup_time, status) VALUES (?, ?, ?, ?, ?, '已完成')");
            $stmt1->bind_param("ssiss", $order_no, $phone, $total_price, $target_date, $pickup_time);
            
            if ($stmt1->execute()) {
                foreach ($order_details as $detail) {
                    $stmt2 = $conn->prepare("INSERT INTO order_detail (order_no, product_name, price, quantity) VALUES (?, ?, ?, ?)");
                    $stmt2->bind_param("ssii", $order_no, $detail['product_name'], $detail['price'], $detail['quantity']);
                    $stmt2->execute();
                    $stmt2->close();
                }
                $total_inserted_orders++;
            }
            $stmt1->close();
            $order_counter++;
        }
    }

    $conn->commit();
    echo "<h3 style='color: green;'>🎉 5,000筆【真人消費心理學軌跡數據】注入成功！</h3>";
    echo "<p>👉 現在請覆蓋第二步的網頁檔案，即可查看高質感的時間區間群組化面板！</p>";
    echo "<a href='analytics.php' style='display:inline-block; padding:12px 25px; background:#6366f1; color:white; text-decoration:none; border-radius:30px; font-weight:bold;'>進入大數據經營分析中心 &raquo;</a>";

} catch (Exception $e) {
    $conn->rollback();
    echo "<h3 style='color: red;'>❌ 生成失敗：{$e->getMessage()}</h3>";
}
?>