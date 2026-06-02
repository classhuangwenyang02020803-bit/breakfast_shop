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
// 營收統計與利潤計算代碼
$month_total = 0;
$month_profit = 0;

// 🌟 建立市場調查分析專用變數
$hot_products = [];
$loyal_favorites = [];
$walkin_favorites = [];
$chart_dates = [];  
$chart_counts = []; 

// 🌟 如果是最高管理員，就從資料庫撈取大數據指標
if ($isAdmin) {
    $current_month = date('Y-m'); 
    
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
    
    $month_profit = round($month_total * 0.4);

    // 📊 當月明星商品熱銷排行榜 (前5名)
    $hot_query = $conn->query("
        SELECT d.product_name, SUM(d.quantity) as total_qty
        FROM order_detail d
        JOIN order_master m ON d.order_no = m.order_no
        WHERE m.pickup_date LIKE '$current_month%' AND m.status IN ('已完成', '已取餐')
        GROUP BY d.product_name
        ORDER BY total_qty DESC
        LIMIT 5
    ");
    while ($row = $hot_query->fetch_assoc()) {
        $hot_products[] = $row;
    }

    // 👥 常客/熟客最愛購買商品前3名 (訂購 2 次以上的聯絡電話)
    $loyal_query = $conn->query("
        SELECT d.product_name, SUM(d.quantity) as total_qty
        FROM order_detail d
        JOIN order_master m ON d.order_no = m.order_no
        WHERE m.status IN ('已完成', '已取餐') AND m.phone IN (
            SELECT phone FROM order_master WHERE status IN ('已完成', '已取餐') GROUP BY phone HAVING COUNT(order_no) >= 2
        )
        GROUP BY d.product_name 
        ORDER BY total_qty DESC 
        LIMIT 3
    ");
    while ($row = $loyal_query->fetch_assoc()) {
        $loyal_favorites[] = $row;
    }

    // 🚶 散客/新客最愛購買商品前3名 (在系統中只訂購過 1 次的電話)
    $walkin_query = $conn->query("
        SELECT d.product_name, SUM(d.quantity) as total_qty
        FROM order_detail d
        JOIN order_master m ON d.order_no = m.order_no
        WHERE m.status IN ('已完成', '已取餐') AND m.phone IN (
            SELECT phone FROM order_master WHERE status IN ('已完成', '已取餐') GROUP BY phone HAVING COUNT(order_no) = 1
        )
        GROUP BY d.product_name 
        ORDER BY total_qty DESC 
        LIMIT 3
    ");
    while ($row = $walkin_query->fetch_assoc()) {
        $walkin_favorites[] = $row;
    }

    // 📈 撈取當月每日銷售商品總數量
    $trend_query = $conn->query("
        SELECT m.pickup_date, SUM(d.quantity) as daily_qty
        FROM order_master m
        JOIN order_detail d ON m.order_no = d.order_no
        WHERE m.pickup_date LIKE '$current_month%' AND m.status IN ('已完成', '已取餐')
        GROUP BY m.pickup_date
        ORDER BY m.pickup_date ASC
    ");
    while ($row = $trend_query->fetch_assoc()) {
        $chart_dates[] = date('m/d', strtotime($row['pickup_date'])); 
        $chart_counts[] = intval($row['daily_qty']);
    }
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    body { background-color: #f8f9fa; font-family: 'PingFang TC', 'Microsoft JhengHei', sans-serif; }
    .dashboard-card { transition: transform 0.3s ease; border: none !important; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075) !important; }
    .dashboard-card:hover { transform: translateY(-5px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1) !important; }
    
    /* 🌟 手機版極致 RWD 特調，消除任何擠壓與不對齊 */
    @media (max-width: 576px) {
        .display-6 { font-size: 1.65rem !important; }
        .card-body { padding: 1.25rem !important; }
        h3 { font-size: 1.25rem !important; }
        h5 { font-size: 1.1rem !important; }
        .container { padding-left: 12px !important; padding-right: 12px !important; }
        .row { margin-left: -6px !important; margin-right: -6px !important; }
        .col-12, .col-sm-6 { padding-left: 6px !important; padding-right: 6px !important; }
    }
</style>
</head>
<body class="bg-light">

<?php include 'navbar.php'; ?>

<div class="container py-3 py-sm-4">

    <div class="row justify-content-center mb-3 mb-sm-4">
        <div class="col-12 col-lg-10">
            <h2 class="fw-bold text-dark m-0 px-1"><i class="bi bi-speedometer2 me-2 text-primary"></i>後台控制面板</h2>
        </div>
    </div>

    <?php if ($isAdmin): ?>
    <div class="row g-3 g-sm-4 mb-4 justify-content-center">
        <div class="col-12 col-sm-6 col-lg-5">
            <div class="card border-0 shadow-sm text-white h-100" style="background: linear-gradient(135deg, #ff9800, #f57c00); border-radius: 15px;">
                <div class="card-body p-4 d-flex flex-column justify-content-center">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 text-uppercase fw-bold mb-1" style="font-size: 0.82rem; letter-spacing: 0.5px;">本月總營業額 (<?php echo date('m'); ?>月)</h6>
                            <h2 class="display-6 fw-bold m-0">$<?php echo number_format($month_total); ?> <span class="fs-6 fw-normal">元</span></h2>
                        </div>
                        <i class="bi bi-currency-dollar text-white-50 d-none d-sm-block" style="font-size: 2.5rem运行;"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-sm-6 col-lg-5">
            <div class="card border-0 shadow-sm text-white h-100" style="background: linear-gradient(135deg, #20c997, #0ea5e9); border-radius: 15px;">
                <div class="card-body p-4 d-flex flex-column justify-content-center">
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

    <div class="row mb-4 justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-header border-0 bg-white pt-4 px-3 px-sm-4 pb-0">
                    <h5 class="fw-bold text-dark m-0"><i class="bi bi-activity text-danger me-2"></i>每日商品銷量市場走向圖</h5>
                    <p class="text-muted small mb-0 mt-1">分析每日售出餐點總份數，用作離尖峰營運參考。</p>
                </div>
                <div class="card-body p-2 p-sm-4">
                    <?php if (empty($chart_dates)): ?>
                        <p class="text-muted small text-center py-5">尚無本月的銷售波動數據</p>
                    <?php else: ?>
                        <div style="position: relative;">
                            <canvas id="marketTrendChart"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 g-sm-4 mb-4 justify-content-center">
        <div class="col-12 col-sm-6 col-lg-5">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
                <div class="card-header border-0 bg-white pt-4 px-3 px-sm-4 pb-2">
                    <h5 class="fw-bold text-dark m-0"><i class="bi bi-fire text-danger me-2"></i>本月熱銷商品 (Top 5)</h5>
                </div>
                <div class="card-body px-3 px-sm-4 pb-4 pt-2">
                    <div class="list-group list-group-flush">
                        <?php if (empty($hot_products)): ?>
                            <p class="text-muted small my-3">本月暫無足夠的銷售數據</p>
                        <?php else: ?>
                            <?php foreach ($hot_products as $index => $item): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2">
                                    <div>
                                        <span class="badge rounded-circle bg-dark text-white me-2 d-inline-flex align-items-center justify-content-center" style="width: 20px; height: 20px; font-size: 0.72rem;"><?php echo $index + 1; ?></span>
                                        <span class="fw-bold text-secondary" style="font-size: 0.9rem;"><?php echo $item['product_name']; ?></span>
                                    </div>
                                    <span class="badge rounded-pill bg-light text-dark border px-2 py-1 fw-bold small">售出 <?php echo $item['total_qty']; ?> 份</span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-5">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
                <div class="card-header border-0 bg-white pt-4 px-3 px-sm-4 pb-2">
                    <h5 class="fw-bold text-dark m-0"><i class="bi bi-pie-chart-fill text-primary me-2"></i>往來客群偏好調查</h5>
                </div>
                <div class="card-body p-3 p-sm-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="mb-3">
                            <h6 class="fw-bold text-primary mb-2" style="font-size: 0.88rem;"><i class="bi bi-heart-fill me-1"></i> 熟客最愛餐點 (回購&ge;2次)</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <?php if (empty($loyal_favorites)): ?>
                                    <span class="text-muted small">精準數據累積中...</span>
                                <?php else: ?>
                                    <?php foreach ($loyal_favorites as $lf): ?>
                                        <span class="badge border border-primary text-primary bg-white rounded-pill px-2.5 py-1.5 fw-bold" style="font-size: 0.75rem;">👍 <?php echo $lf['product_name']; ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <hr class="text-muted my-3" style="opacity: 0.15;">
                        
                        <div class="mb-4">
                            <h6 class="fw-bold text-success mb-2" style="font-size: 0.88rem;"><i class="bi bi-geo-alt-fill me-1"></i> 散客最愛餐點 (首次購買)</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <?php if (empty($walkin_favorites)): ?>
                                    <span class="text-muted small">精準數據累積中...</span>
                                <?php else: ?>
                                    <?php foreach ($walkin_favorites as $wf): ?>
                                        <span class="badge border border-success text-success bg-white rounded-pill px-2.5 py-1.5 fw-bold" style="font-size: 0.75rem;">🚶 <?php echo $wf['product_name']; ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2">
                        <a href="customers.php" class="btn btn-outline-primary btn-sm w-100 rounded-pill py-2 fw-bold shadow-sm">
                            <i class="bi bi-people-fill me-1"></i> 進入 CRM 熟客大數據追蹤系統 &raquo;
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-3 g-sm-4 justify-content-center">
        <div class="col-12 col-sm-6 col-lg-5">
            <div class="card shadow-sm border-0 dashboard-card" style="border-radius: 15px; background: #ffffff;">
                <div class="card-body text-center p-4 p-sm-5">
                    <i class="bi bi-box-seam text-primary" style="font-size: 3.5rem;"></i>
                    <h3 class="fw-bold mt-2 fs-4 text-dark">商品管理</h3>
                    <p class="text-muted small mb-3">新增、修改或下架早餐品項</p>
                    <a href="products.php" class="btn btn-dark w-100 rounded-pill py-2 fw-bold">進入管理</a>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-5">
            <div class="card shadow-sm border-0 dashboard-card" style="border-radius: 15px; background: #ffffff;">
                <div class="card-body text-center p-4 p-sm-5">
                    <i class="bi bi-receipt text-warning" style="font-size: 3.5rem;"></i>
                    <h3 class="fw-bold mt-2 fs-4 text-dark">訂單管理</h3>
                    <p class="text-muted small mb-3">查看與處理即時顧客預約訂單</p>
                    <a href="orders.php" class="btn btn-primary w-100 rounded-pill py-2 fw-bold">查看訂單</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($isAdmin && !empty($chart_dates)): ?>
<script>
    const ctx = document.getElementById('marketTrendChart').getContext('2d');
    const labelsData = <?php echo json_encode($chart_dates); ?>;
    const salesData = <?php echo json_encode($chart_counts); ?>;

    const isMobile = window.innerWidth < 576;
    ctx.canvas.parentNode.style.height = isMobile ? '180px' : '260px';

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labelsData,
            datasets: [{
                label: '每日售出產品總件數',
                data: salesData,
                borderColor: '#ef4444',
                borderWidth: isMobile ? 2 : 3, 
                pointBackgroundColor: '#b91c1c',
                pointRadius: isMobile ? 2 : 4,
                pointHoverRadius: 6,
                tension: 0.3, 
                fill: true, 
                backgroundColor: (context) => {
                    const chart = context.chart;
                    const {ctx, chartArea} = chart;
                    if (!chartArea) return null;
                    const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    gradient.addColorStop(0, 'rgba(239, 68, 68, 0.22)');
                    gradient.addColorStop(1, 'rgba(239, 68, 68, 0.0)');
                    return gradient;
                }
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, 
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    grid: { display: false }, 
                    ticks: { color: '#64748b', font: { size: isMobile ? 9 : 11 }, maxRotation: 0 }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#e2e8f0', drawBorder: false },
                    ticks: { color: '#64748b', font: { size: isMobile ? 9 : 11 }, stepSize: isMobile ? 10 : 5 }
                }
            }
        }
    });
</script>
<?php endif; ?>
</body>
</html>