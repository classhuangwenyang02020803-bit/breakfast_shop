<?php
require_once 'auth.php';
?>

<!DOCTYPE html>
<html lang="zh-tw">
<head>
<meta charset="UTF-8">
<title>管理後台</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    .dashboard-card { transition: transform 0.3s ease; }
    .dashboard-card:hover { transform: translateY(-5px); }
</style>
</head>
<body class="bg-light">

<?php include 'navbar.php'; ?>

<div class="container py-4">

    <div class="row justify-content-center g-4 mt-2">

        <div class="col-md-5">
            <div class="card shadow-sm border-0 dashboard-card">
                <div class="card-body text-center p-5">
                    <i class="bi bi-box-seam text-primary" style="font-size: 4rem;"></i>
                    <h3 class="fw-bold mt-3">商品管理</h3>
                    <p class="text-muted small">新增、修改或下架早餐品項</p>
                    <a href="products.php" class="btn btn-dark mt-3 rounded-pill px-4">
                        進入管理
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card shadow-sm border-0 dashboard-card">
                <div class="card-body text-center p-5">
                    <i class="bi bi-receipt text-warning" style="font-size: 4rem;"></i>
                    <h3 class="fw-bold mt-3">訂單管理</h3>
                    <p class="text-muted small">查看與處理即時顧客預約訂單</p>
                    <a href="orders.php" class="btn btn-primary mt-3 rounded-pill px-4">
                        查看訂單
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>