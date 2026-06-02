<?php
require_once 'auth.php';
require_once '../api/db.php'; // 確保有引入資料庫連線

// 🔒 建立檢查是不是最高管理員的變數
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// 🧹 【自動大掃除機制】：每個月歷史舊訂單自動大掃除
$thirty_days_ago = date('Y-m-d', strtotime('-30 days')); 
$conn->query("DELETE FROM order_detail WHERE order_no IN (SELECT order_no FROM order_master WHERE pickup_date < '$thirty_days_ago')");
$conn->query("DELETE FROM order_master WHERE pickup_date < '$thirty_days_ago'");

// -----------------------------------------------------------------
$month_total = 0;
$month_profit = 0;

if ($isAdmin) {
    $current_month = date('Y-m'); 
    $revenue_query = $conn->query("
        SELECT SUM(total_price) as month_total 
        FROM order_master 
        WHERE pickup_date LIKE '$current_month%' 
        AND status IN ('已完成', '已取餐')
    ");
    if ($revenue_row = $revenue_query->fetch_assoc()) {
        $month_total = $revenue_row['month_total'] ? intval($revenue_row['month_total']) : 0;
    }
    $month_profit = round($month_total * 0.4);
}
?>
<!DOCTYPE html>
<html lang="zh-tw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>管理後台 | 儀表板</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    body { background-color: #f8f9fa; font-family: 'PingFang TC', 'Microsoft JhengHei', sans-serif; }
    .dashboard-card { transition: transform 0.3s ease; border: none !important; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075) !important; border-radius: 15px !important; }
    .dashboard-card:hover { transform: translateY(-5px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1) !important; }
    
    @media (max-width: 576px) {
        .display-6 { font-size: 1.65rem !important; }
        .card-body { padding: 1.25rem !important; }
        h3 { font-size: 1.25rem !important; }
    }
</style>
</head>
<body class="bg-light">

<?php include 'navbar.php'; ?>

<div class="container px-3 px-sm-4 py-3 py-sm-4">

    <div class="row justify-content-center mb-3 mb-sm-4">
        <div class="col-12 col-lg-10">
            <h2 class="fw-bold text-dark m-0"><i class="bi bi-speedometer2 me-2 text-primary"></i>後台控制面板</h2>
        </div>
    </div>

    <?php if ($isAdmin): ?>
    <div class="row g-3 g-sm-4 mb-4 justify-content-center">
        <div class="col-12 col-sm-6 col-lg-5">
            <div class="card border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #ff9800, #f57c00); border-radius: 15px;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 text-uppercase fw-bold mb-1" style="font-size: 0.82rem; letter-spacing: 0.5px;">本月總營業額 (<?php echo date('m'); ?>月)</h6>
                            <h2 class="display-6 fw-bold m-0">$<?php echo number_format($month_total); ?> <span class="fs-6 fw-normal">元</span></h2>
                        </div>
                        <i class="bi bi-currency-dollar text-white-50 d-none d-sm-block" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-sm-6 col-lg-5">
            <div class="card border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #20c997, #0ea5e9); border-radius: 15px;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 text-uppercase fw-bold mb-1" style="font-size: 0.82rem; letter-spacing: 0.5px;">估算本月淨利潤 (純利 40%)</h6>
                            <h2 class="display-6 fw-bold m-0">$<?php echo number_format($month_profit); ?> <span class="fs-6 fw-normal">元</span></h2>
                        </div>
                        <i class="bi bi-graph-up-arrow text-white-50 d-none d-sm-block" style="font-size: 2.2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-3 g-sm-4 justify-content-center">
        
        <div class="col-12 col-sm-6 col-lg-5">
            <div class="card shadow-sm border-0 dashboard-card">
                <div class="card-body text-center p-4 p-sm-5">
                    <i class="bi bi-receipt text-warning" style="font-size: 3.5rem;"></i>
                    <h3 class="fw-bold mt-2 fs-4 text-dark">訂單管理</h3>
                    <p class="text-muted small mb-3">查看與處理即時顧客預約訂單</p>
                    <a href="orders.php" class="btn btn-primary w-100 rounded-pill py-2 fw-bold">查看訂單</a>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-5">
            <div class="card shadow-sm border-0 dashboard-card">
                <div class="card-body text-center p-4 p-sm-5">
                    <i class="bi bi-box-seam text-primary" style="font-size: 3.5rem;"></i>
                    <h3 class="fw-bold mt-2 fs-4 text-dark">商品管理</h3>
                    <p class="text-muted small mb-3">新增、修改或下架早餐品項</p>
                    <a href="products.php" class="btn btn-dark w-100 rounded-pill py-2 fw-bold">進入管理</a>
                </div>
            </div>
        </div>

        <?php if ($isAdmin): ?>
        <div class="col-12 col-lg-10">
            <div class="card shadow-sm border-0 dashboard-card" style="background: linear-gradient(135deg, #6366f1, #4f46e5); color: #ffffff;">
                <div class="card-body text-center p-4 py-sm-5">
                    <i class="bi bi-bar-chart-line-fill text-white" style="font-size: 3.5rem;"></i>
                    <h3 class="fw-bold mt-2 fs-4 text-white">大數據經營分析中心</h3>
                    <p class="text-white-50 small mb-3">包含：熟客習慣追蹤、每月明星餐點排行、股票式每日銷售峰值走向趨勢圖</p>
                    <a href="analytics.php" class="btn btn-light text-primary w-100 rounded-pill py-2 fw-bold shadow-sm">進入數據戰情室 &raquo;</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>