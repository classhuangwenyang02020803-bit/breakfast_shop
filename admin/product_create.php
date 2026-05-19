<?php
require_once 'auth.php';
require_once '../api/db.php';

if(isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $price = intval($_POST['price']);
    $price_m = intval($_POST['price_m']); // 新增中杯價
    $price_l = intval($_POST['price_l']); // 新增大杯價
    $status = intval($_POST['status']);

    $image = '';

    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $filename = time() . '_' . basename($_FILES['image']['name']);
        
        // --- 關鍵修正：跳出 admin 資料夾 ---
        // 使用 .. 跳到上一層，確保檔案存到最外層的 assets/uploads
        $targetDir = __DIR__ . '/../assets/uploads/'; 
        $targetPath = $targetDir . $filename;

        // 如果最外層的資料夾不存在就建立它
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        if(move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            // 【重要】存入資料庫的路徑請保持 assets/uploads/
            // 這樣前台 index.php 才能直接抓到，不需要加 admin/
            $image = 'assets/uploads/' . $filename;
        } else {
            die("圖片搬移失敗！請確認 " . $targetDir . " 是否存在且可寫入。");
        }
    }

    // SQL 同步增加 price_m, price_l
    $stmt = $conn->prepare(
        "INSERT INTO products (name, price, price_m, price_l, image, status) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param("siiisi", $name, $price, $price_m, $price_l, $image, $status);
    $stmt->execute();

    header('Location: products.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-tw">
<head>
    <meta charset="UTF-8">
    <title>新增商品</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
<div class="container py-5">
    <div class="card shadow border-0">
        <div class="card-body p-4">
            <h2 class="mb-4">新增商品</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label>商品名稱</label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <div class="row mb-3">
                    <div class="col-4">
                        <label>基本價格</label>
                        <input type="number" name="price" class="form-control" required>
                    </div>
                    <div class="col-4">
                        <label>小杯價格 (飲品選填)</label>
                        <input type="number" name="price_m" class="form-control" value="0">
                    </div>
                    <div class="col-4">
                        <label>大杯價格 (飲品選填)</label>
                        <input type="number" name="price_l" class="form-control" value="0">
                    </div>
                </div>

                <div class="mb-3">
                    <label>商品圖片</label>
                    <input type="file" name="image" class="form-control" required accept="image/*">
                </div>

                <div class="mb-4">
                    <label>商品狀態</label>
                    <select name="status" class="form-select">
                        <option value="1">上架</option>
                        <option value="0">下架</option>
                    </select>
                </div>

                <button type="submit" name="submit" class="btn btn-success">新增商品</button>
                <a href="products.php" class="btn btn-secondary">返回</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>