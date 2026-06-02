<?php

require_once 'auth.php';
require_once '../api/db.php'; // 確保有引入資料庫連線

// 🔒 嚴格防禦：只有最高管理員 (admin) 才能查看此大數據分析頁面
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

$loyal_customers_detail = [];

// 🔍 高階 SQL GROUP_CONCAT 技術：撈取所有熟客（回購>=2次）的終身消費軌跡明細
$loyal_detail_query = $conn->query("
    SELECT m.phone, COUNT(m.order_no) as total_orders,
           GROUP_CONCAT(CONCAT(d.product_name, ' x', d.sum_qty) SEPARATOR ' 、 ') as favorite_items
    FROM order_master m
    JOIN (
        SELECT order_no, product_name, SUM(quantity) as sum_qty 
        FROM order_detail 
        GROUP BY order_no, product_name
    ) d ON m.order_no = d.order_no
    WHERE m.status IN ('已完成', '已取餐')
    GROUP BY m.phone
    HAVING total_orders >= 2
    ORDER BY total_orders DESC
");

if ($loyal_detail_query) {
    while ($row = $loyal_detail_query->fetch_assoc()) {
        $loyal_customers_detail[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-tw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CRM 熟客大數據分析 | 管理後台</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    body { background-color: #f8f9fa; font-family: 'PingFang TC', sans-serif; }
    .loyal-item-btn { transition: background-color 0.2s; cursor: pointer; border: 1px solid #e2e8f0 !important; }
    .loyal-item-btn:hover { background-color: #f8fafc !important; border-color: #cbd5e1 !important; }
    
    /* 🌟 行動裝置防擠壓特調 */
    @media (max-width: 576px) {
        .card-body { padding: 1.15rem !important; }
        .loyal-item-btn { padding: 0.9rem 0.65rem !important; }
        .loyal-item-btn .fs-5 { font-size: 0.95rem !important; }
        .favorite-box { font-size: 0.82rem !important; padding: 0.75rem !important; }
    }
</style>
</head>
<body class="bg-light">

<?php include 'navbar.php'; ?>

<div class="container px-3 px-sm-4 py-4">
    
    <div class="row justify-content-center mb-3">
        <div class="col-12 col-lg-10">
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb mb-2 ps-1">
                <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">後台首頁</a></li>
                <li class="breadcrumb-item active" aria-current="page">熟客大數據追蹤</li>
              </ol>
            </nav>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-header border-0 bg-white pt-4 px-3 px-sm-4 pb-2">
                    <h4 class="fw-bold text-dark m-0"><i class="bi bi-person-lines-fill text-primary me-2"></i>熟客精準消費習慣追蹤系統 (CRM)</h4>
                    <p class="text-muted small mb-0 mt-1">此區塊專供最高管理員查閱。系統透過大數據自動彙整在店內成功回購兩次以上的核心常客名單，利於分析常客的餐點黏著度與備料走向。</p>
                </div>
                <div class="card-body px-3 px-sm-4 pb-4 pt-2">
                    <div class="list-group">
                        <?php if (empty($loyal_customers_detail)): ?>
                            <div class="text-muted text-center py-5 rounded-3 bg-light border border-dashed">
                                <i class="bi bi-people text-muted display-4 d-block mb-2"></i>
                                尚未累積符合回購次數條件的常客大數據資料。
                            </div>
                        <?php else: ?>
                            <?php foreach ($loyal_customers_detail as $idx => $cust): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center bg-white rounded-3 mb-2 px-3 px-sm-4 py-3 loyal-item-btn shadow-sm" 
                                     data-bs-toggle="collapse" 
                                     data-bs-target="#loyal-page-detail-<?php echo $idx; ?>" 
                                     aria-expanded="false">
                                    <div class="d-flex align-items-center overflow-hidden me-2">
                                        <i class="bi bi-shield-check text-success me-2 me-sm-3 fs-4"></i>
                                        <div class="text-truncate">
                                            <span class="fw-bold text-dark fs-5" style="letter-spacing:0.5px;"><?php echo $cust['phone']; ?></span>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1 rounded-pill d-none d-sm-inline-block" style="font-size:0.7rem;">核心常客</span>
                                        </div>
                                    </div>
                                    <div class="text-end flex-shrink-0">
                                        <span class="text-muted small d-none d-sm-inline-block me-1">累計完成</span>
                                        <span class="badge bg-danger rounded-pill px-2.5 py-1.5 fw-bold" style="font-size:0.82rem;"><?php echo $cust['total_orders']; ?> 次預約</span>
                                        <i class="bi bi-chevron-down ms-1.5 text-secondary small"></i>
                                    </div>
                                </div>

                                <div class="collapse mb-2" id="loyal-page-detail-<?php echo $idx; ?>">
                                    <div class="card card-body border-0 shadow-sm p-3 bg-light" style="border-radius: 12px;">
                                        <div class="row g-2">
                                            <div class="col-12 text-muted mb-1">
                                                <i class="bi bi-info-circle me-1"></i> <span class="fw-bold">往來客戶唯一識別碼：</span> <span class="text-dark fw-bold"><?php echo $cust['phone']; ?></span>
                                            </div>
                                            <div class="col-12">
                                                <div class="p-3 bg-white border rounded-3 favorite-box">
                                                    <strong class="text-dark d-block mb-2"><i class="bi bi-bookmark-star-fill text-warning me-1"></i>固定喜好與常吃品項加總明細：</strong>
                                                    <span class="text-secondary fw-semibold lh-base" style="letter-spacing: 0.2px; font-size: 0.9rem;"><?php echo $cust['favorite_items']; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>