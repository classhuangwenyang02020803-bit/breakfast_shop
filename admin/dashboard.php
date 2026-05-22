<?php

require_once 'auth.php';
require_once '../api/db.php'; // 確保有引入資料庫連線

// 🔒 建立檢查是不是最高管理員的變數
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// 🧹 【新增功能】：每個月歷史舊訂單自動大掃除機制
// 只要老闆或員工登入進入後台儀表板，系統就會自動執行這段指令
$thirty_days_ago = date('Y-m-d', strtotime('-30 days')); // 計算出 30 天前的日期

// 1. 先清除 order_detail (訂單明細表) 裡面超過 30 天的舊資料
$conn->query("DELETE FROM order_detail WHERE order_no IN (SELECT order_no FROM order_master WHERE pickup_date < '$thirty_days_ago')");

// 2. 再清除 order_master (訂單主表) 裡面超過 30 天的舊資料
$conn->query("DELETE FROM order_master WHERE pickup_date < '$thirty_days_ago'");

// -----------------------------------------------------------------
// 以下是你原本就有的營收統計與利潤計算代碼（維持不動）
$month_total = 0;
$month_profit = 0;

// 🌟 【核心功能新增】：如果是最高管理員，就從資料庫撈取當月總營收與純利利潤
if ($isAdmin) {
    $current_month = date('Y-m'); // 自動獲取當前年月，例如 "2026-05"
    
    // 撈取當月狀態為「已完成」或「已取餐」的訂單總額
    $revenue_query = $conn->query("
        SELECT SUM(total_price) as month_total 
        FROM order_master 
        WHERE pickup_date LIKE '$current_month%' 
        AND status IN ('已完成', '已取餐')
    ");
    
    if ($revenue_row = $revenue_query->fetch_assoc()) {
        $month_total = $revenue_row['month_total'] ? intval($revenue_row['month_total']) : 0;
    }
    
    // 💡 估算淨利潤：以餐飲業常見的 40% 淨利來預估純利金額，方便管理營運進貨成本
    $month_profit = round($month_total * 0.4);
}
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

    <?php if ($isAdmin): ?>
    <div class="row g-4 mb-4 justify-content-center">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #ff9800, #f57c00); border-radius: 15px;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 text-uppercase fw-bold mb-1" style="font-size: 0.85rem; letter-spacing: 1px;">本月總營業額 (<?php echo date('m'); ?>月)</h6>
                            <h2 class="display-6 fw-bold m-0">$<?php echo number_format($month_total); ?> <span class="fs-6 fw-normal">元</span></h2>
                        </div>
                        <i class="bi bi-currency-dollar text-white-50" style="font-size: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-5">
            <div class="card border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #20c997, #0ea5e9); border-radius: 15px;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 text-uppercase fw-bold mb-1" style="font-size: 0.85rem; letter-spacing: 1px;">估算本月淨利潤 (純利 40%)</h6>
                            <h2 class="display-6 fw-bold m-0">$<?php echo number_format($month_profit); ?> <span class="fs-6 fw-normal">元</span></h2>
                        </div>
                        <i class="bi bi-graph-up-arrow text-white-50" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

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