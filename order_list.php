<?php
$conn = new mysqli("localhost", "root", "", "breakfast_shop");
$conn->set_charset("utf8");
$result = $conn->query("SELECT * FROM orders ORDER BY order_time DESC");
?>
<!DOCTYPE html>
<html lang="zh-tw">
<head>
    <meta charset="UTF-8">
    <title>我的點餐紀錄</title>
    <link rel="stylesheet" href="bootstrap.min.css">
    <style>
        body { background-color: #FFFDF5; padding-top: 50px; }
        .order-item { background: white; border-radius: 15px; margin-bottom: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="container" style="max-width: 600px;">
        <h2 class="text-center mb-4">📋 點餐清單</h2>
        <div class="text-center mb-4"><a href="index.html" class="btn btn-outline-warning">返回菜單</a></div>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="order-item d-flex justify-content-between align-items-center">
                <div>
                    <strong class="fs-5"><?php echo $row['product_name']; ?></strong><br>
                    <small class="text-muted"><?php echo $row['order_time']; ?></small>
                </div>
                <span class="badge rounded-pill bg-warning text-dark px-3 py-2">數量：<?php echo $row['quantity']; ?></span>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>