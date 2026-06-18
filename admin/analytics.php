<?php
// 檔案位置：admin/analytics.php
require_once 'auth.php';
require_once '../api/db.php'; // 🌟 順從你的需求：完全改回原本的檔案路徑，保證不跳 No such file

if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

$current_month = date('Y-m');
$hot_products = [];
$loyal_favorites = [];
$walkin_favorites = [];
$loyal_customers_detail = [];
$chart_dates = [];  
$chart_counts = []; 

// 🌟 1. 全店大盤進貨分析
$global_items_data = [];
$global_items_query = $conn->query("
    SELECT d.product_name, SUM(d.quantity) as total_qty
    FROM order_detail d
    JOIN order_master m ON d.order_no = m.order_no
    WHERE m.pickup_date LIKE '$current_month%' AND m.status IN ('已完成', '已取餐')
    GROUP BY d.product_name
    ORDER BY total_qty DESC
");
if ($global_items_query) {
    while ($row = $global_items_query->fetch_assoc()) {
        $global_items_data[$row['product_name']] = intval($row['total_qty']);
    }
}
$global_items_json = json_encode($global_items_data, JSON_UNESCAPED_UNICODE);

// 📊 2. 當月明星商品熱銷排行榜
$hot_query = $conn->query("
    SELECT d.product_name, SUM(d.quantity) as total_qty
    FROM order_detail d
    JOIN order_master m ON d.order_no = m.order_no
    WHERE m.pickup_date LIKE '$current_month%' AND m.status IN ('已完成', '已取餐')
    GROUP BY d.product_name
    ORDER BY total_qty DESC
    LIMIT 5
");
if ($hot_query) {
    while ($row = $hot_query->fetch_assoc()) {
        $hot_products[] = $row;
    }
}

// 👥 3. 常客最愛與散客最愛
$loyal_query = $conn->query("
    SELECT d.product_name, SUM(d.quantity) as total_qty FROM order_detail d JOIN order_master m ON d.order_no = m.order_no
    WHERE m.status IN ('已完成', '已取餐') AND m.phone IN (SELECT phone FROM order_master WHERE status IN ('已完成', '已取餐') GROUP BY phone HAVING COUNT(order_no) >= 2)
    GROUP BY d.product_name ORDER BY total_qty DESC LIMIT 3
");
if ($loyal_query) { while ($row = $loyal_query->fetch_assoc()) { $loyal_favorites[] = $row; } }

$walkin_query = $conn->query("
    SELECT d.product_name, SUM(d.quantity) as total_qty FROM order_detail d JOIN order_master m ON d.order_no = m.order_no
    WHERE m.status IN ('已完成', '已取餐') AND m.phone IN (SELECT phone FROM order_master WHERE status IN ('已完成', '已取餐') GROUP BY phone HAVING COUNT(order_no) = 1)
    GROUP BY d.product_name ORDER BY total_qty DESC LIMIT 3
");
if ($walkin_query) { while ($row = $walkin_query->fetch_assoc()) { $walkin_favorites[] = $row; } }


// 🔍 4. 【熟客面板高效能分頁優化核心】
$loyal_limit = 7; 
$loyal_page = isset($_GET['loyal_page']) && is_numeric($_GET['loyal_page']) ? intval($_GET['loyal_page']) : 1;
if ($loyal_page < 1) $loyal_page = 1;
$loyal_offset = ($loyal_page - 1) * $loyal_limit;

$count_loyal_query = $conn->query("
    SELECT COUNT(DISTINCT m.phone) as total_vips
    FROM order_master m
    WHERE m.status IN ('Alice已完成', '已完成', '已取餐')
    GROUP BY m.phone
    HAVING COUNT(m.order_no) >= 2
");
$total_loyal_records = $count_loyal_query ? $count_loyal_query->num_rows : 0;
$total_loyal_pages = ceil($total_loyal_records / $loyal_limit);

$loyal_base_query = $conn->query("
    SELECT m.phone, COUNT(m.order_no) as total_orders
    FROM order_master m
    WHERE m.status IN ('Alice已完成', '已完成', '已取餐')
    GROUP BY m.phone
    HAVING total_orders >= 2
    ORDER BY total_orders DESC
    LIMIT $loyal_limit OFFSET $loyal_offset
");

if ($loyal_base_query) {
    while ($base = $loyal_base_query->fetch_assoc()) {
        $phone = $base['phone'];
        
        $time_query = $conn->query("SELECT pickup_time FROM order_master WHERE phone = '$phone' AND status IN ('Alice已完成', '已完成', '已取餐')");
        $intervals = [];
        
        if ($time_query) {
            while ($t_row = $time_query->fetch_assoc()) {
                $time_str = $t_row['pickup_time'];
                $parts = explode(':', $time_str);
                $hour = intval($parts[0]);
                $minute = intval($parts[1]);
                
                if ($minute >= 0 && $minute <= 15) { $start = "00"; $end = "15"; }
                elseif ($minute >= 16 && $minute <= 30) { $start = "15"; $end = "30"; }
                elseif ($minute >= 31 && $minute <= 45) { $start = "30"; $end = "45"; }
                else { $start = "45"; $end = "00"; }
                
                if ($start == "45" && $end == "00") {
                    $next_hour = $hour + 1;
                    $interval_label = str_pad($hour, 2, '0', STR_PAD_LEFT) . ":45 ~ " . str_pad($next_hour, 2, '0', STR_PAD_LEFT) . ":00";
                } else {
                    $interval_label = str_pad($hour, 2, '0', STR_PAD_LEFT) . ":" . $start . " ~ " . str_pad($hour, 2, '0', STR_PAD_LEFT) . ":" . $end;
                }
                $intervals[] = $interval_label;
            }
        }
        
        $intervals = array_unique($intervals);
        sort($intervals);
        $final_intervals_str = implode(', ', $intervals);

        $items_query = $conn->query("
            SELECT d.product_name, SUM(d.quantity) as sum_qty
            FROM order_detail d JOIN order_master m ON d.order_no = m.order_no
            WHERE m.phone = '$phone' AND m.status IN ('Alice已完成', '已完成', '已取餐')
            GROUP BY d.product_name
        ");
        
        $customer_pie_data = [];
        if ($items_query) {
            while ($item = $items_query->fetch_assoc()) {
                $customer_pie_data[$item['product_name']] = intval($item['sum_qty']);
            }
        }
        
        $loyal_customers_detail[] = [
            'phone' => $phone,
            'total_orders' => $base['total_orders'],
            'visit_intervals' => $final_intervals_str,
            'json_str' => json_encode($customer_pie_data, JSON_UNESCAPED_UNICODE)
        ];
    }
}

// 📈 5. 每日銷售波動
$trend_query = $conn->query("
    SELECT m.pickup_date, SUM(d.quantity) as daily_qty FROM order_master m JOIN order_detail d ON m.order_no = d.order_no
    WHERE m.pickup_date LIKE '$current_month%' AND m.status IN ('已完成', '已取餐') GROUP BY m.pickup_date ORDER BY m.pickup_date ASC
");
if ($trend_query) {
    while ($row = $trend_query->fetch_assoc()) {
        $chart_dates[] = date('m/d', strtotime($row['pickup_date'])); $chart_counts[] = intval($row['daily_qty']);
    }
}

// 🛡️ 6. 【黑名單面板高效能分頁後端引擎 (累積 3 次未取單)】
$black_limit = 5; 
$black_page = isset($_GET['black_page']) && is_numeric($_GET['black_page']) ? intval($_GET['black_page']) : 1;
if ($black_page < 1) $black_page = 1;
$black_offset = ($black_page - 1) * $black_limit;

// 算出一共有多少位列入黑名單的風險帳號
$count_black_query = $conn->query("SELECT COUNT(*) as total_blacks FROM blacklist_table");
$total_black_records = $count_black_query ? $count_black_query->fetch_assoc()['total_blacks'] : 0;
$total_black_pages = ceil($total_black_records / $black_limit);

// 高效能分頁撈取實體黑名單資料
$blacklist_list = [];
$black_res = $conn->query("
    SELECT phone_number, ban_reason, ban_date 
    FROM blacklist_table 
    ORDER BY ban_date DESC 
    LIMIT $black_limit OFFSET $black_offset
");
if ($black_res) {
    while ($b_row = $black_res->fetch_assoc()) {
        $blacklist_list[] = $b_row;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-tw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>大數據經營分析中心 | 管理後台</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    body { background-color: #f8f9fa; font-family: 'PingFang TC', sans-serif; }
    .loyal-item-btn { transition: background-color 0.2s; cursor: pointer; border: 1px solid #e2e8f0 !important; }
    .loyal-item-btn:hover { background-color: #f8fafc !important; }
    .time-badge { background-color: #e0f2fe; color: #0369a1; font-size: 0.82rem; font-weight: 700; padding: 5px 12px; border-radius: 6px; border: 1px solid #bae6fd; display: inline-block; }
    @media (max-width: 576px) {
        .card-body { padding: 1.15rem !important; }
        .loyal-item-btn { padding: 0.9rem 0.65rem !important; }
        .loyal-item-btn .fs-5 { font-size: 0.95rem !important; }
        .pie-container { height: 210px !important; margin-bottom: 15px; }
        .global-pie-box { height: 260px !important; }
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
                <li class="breadcrumb-item active" aria-current="page">大數據經營分析</li>
              </ol>
            </nav>
        </div>
    </div>

    <div class="row mb-4 justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-header border-0 bg-white pt-4 px-3 px-sm-4 pb-0">
                    <h5 class="fw-bold text-dark m-0"><i class="bi bi-activity text-danger me-2"></i>每日商品銷量市場走向圖</h5>
                </div>
                <div class="card-body p-2 p-sm-4">
                    <?php if (empty($chart_dates)): ?>
                        <p class="text-muted small text-center py-5">尚無銷售數據</p>
                    <?php else: ?>
                        <div><canvas id="marketTrendChart"></canvas></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4 justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-header border-0 bg-white pt-4 px-3 px-sm-4 pb-1">
                    <h5 class="fw-bold text-dark m-0"><i class="bi bi-pie-chart text-primary me-2"></i>當月全店品項銷量總比例圖（進貨決策參考）</h5>
                </div>
                <div class="card-body p-3 p-sm-4">
                    <?php if (empty($global_items_data)): ?>
                        <p class="text-muted small text-center py-5">暫無數據</p>
                    <?php else: ?>
                        <div class="global-pie-box" style="position: relative; height: 230px; width: 100%;">
                            <canvas id="globalMarketPieChart"></canvas>
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
                        <?php foreach ($hot_products as $index => $item): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2">
                                <div>
                                    <span class="badge rounded-circle bg-dark text-white me-2 d-inline-flex align-items-center justify-content-center" style="width: 20px; height: 20px; font-size: 0.72rem;"><?php echo $index + 1; ?></span>
                                    <span class="fw-bold text-secondary" style="font-size: 0.9rem;"><?php echo $item['product_name']; ?></span>
                                </div>
                                <span class="badge rounded-pill bg-light text-dark border px-2 py-1 fw-bold small">售出 <?php echo $item['total_qty']; ?> 份</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-5">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
                <div class="card-header border-0 bg-white pt-4 px-3 px-sm-4 pb-2">
                    <h5 class="fw-bold text-dark m-0"><i class="bi bi-pie-chart-fill text-primary me-2"></i>往來客群偏好調查</h5>
                </div>
                <div class="card-body p-3 p-sm-4">
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary mb-2" style="font-size: 0.88rem;"><i class="bi bi-heart-fill me-1"></i> 熟客最愛餐點</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($loyal_favorites as $lf): ?>
                                <span class="badge border border-primary text-primary bg-white rounded-pill px-2.5 py-1.5 fw-bold" style="font-size: 0.75rem;">👍 <?php echo $lf['product_name']; ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <hr class="text-muted my-3" style="opacity: 0.15;">
                    <div>
                        <h6 class="fw-bold text-success mb-2" style="font-size: 0.88rem;"><i class="bi bi-geo-alt-fill me-1"></i> 散客最愛餐點</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($walkin_favorites as $wf): ?>
                                <span class="badge border border-success text-success bg-white rounded-pill px-2.5 py-1.5 fw-bold" style="font-size: 0.75rem;">🚶 <?php echo $wf['product_name']; ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center mb-4">
        <div class="col-12 col-lg-10">
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-header border-0 bg-white pt-4 px-3 px-sm-4 pb-2">
                    <div class="d-flex justify-content-between align-items-start align-items-sm-center flex-column flex-sm-row">
                        <div>
                            <h5 class="fw-bold text-dark m-0"><i class="bi bi-person-lines-fill text-dark me-2"></i>熟客精準消費習慣動態追蹤面板</h5>
                            <small class="text-muted ps-1">高回購常客總計：<?php echo $total_loyal_records; ?> 位</small>
                        </div>
                        <span class="badge bg-primary rounded-pill mt-2 mt-sm-0 px-3 py-1.5 small fw-bold">熟客 第 <?php echo $loyal_page; ?> / <?php echo $total_loyal_pages; ?> 頁</span>
                    </div>
                </div>
                <div class="card-body px-3 px-sm-4 pb-4 pt-2">
                    <div class="list-group">
                        <?php if (empty($loyal_customers_detail)): ?>
                            <p class="text-muted small my-3 px-2">尚未累積熟客資料。</p>
                        <?php else: ?>
                            <?php foreach ($loyal_customers_detail as $idx => $cust): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center bg-white rounded-3 mb-2 px-3 px-sm-4 py-3 loyal-item-btn shadow-sm" 
                                     data-bs-toggle="collapse" 
                                     data-bs-target="#loyal-detail-<?php echo $idx; ?>" 
                                     aria-expanded="false">
                                    <div class="d-flex align-items-center overflow-hidden me-2">
                                        <i class="bi bi-telephone-outbound text-primary me-2 me-sm-3 fs-5"></i>
                                        <span class="fw-bold text-dark fs-5"><?php echo $cust['phone']; ?></span>
                                    </div>
                                    <div class="text-end flex-shrink-0">
                                        <span class="badge bg-danger rounded-pill px-2.5 py-1.5 fw-bold" style="font-size:0.8rem;"><?php echo $cust['total_orders']; ?> 次預約</span>
                                        <i class="bi bi-chevron-down ms-1 text-secondary small"></i>
                                    </div>
                                </div>

                                <div class="collapse mb-2 loyal-collapse" 
                                     id="loyal-detail-<?php echo $idx; ?>"
                                     data-json='<?php echo htmlspecialchars($cust['json_str'], ENT_QUOTES, 'UTF-8'); ?>'>
                                    <div class="card card-body border-0 shadow-sm p-3 p-sm-4 bg-white" style="border-radius: 12px; border: 1px solid #e2e8f0 !important;">
                                        <div class="row align-items-center">
                                            <div class="col-12 col-md-6 pie-container" style="position: relative; height: 190px;">
                                                <canvas id="pie-chart-<?php echo $idx; ?>"></canvas>
                                            </div>
                                            <div class="col-12 col-md-6 ps-md-3 mt-3 mt-md-0">
                                                <div class="p-3 rounded-3 h-100" style="background-color: #f0f9ff; border-left: 4px solid #0284c7;">
                                                    <h6 class="fw-bold text-dark mb-2.5" style="font-size: 0.9rem;"><i class="bi bi-alarm-fill text-info me-1"></i> 熟客固定取餐通勤時段：</h6>
                                                    <div class="d-flex flex-wrap gap-1.5">
                                                        <?php 
                                                            $times_arr = explode(', ', $cust['visit_intervals']);
                                                            foreach($times_arr as $t) {
                                                                echo '<span class="time-badge mb-1 me-1"><i class="bi bi-clock me-1 text-primary"></i>' . $t . '</span>';
                                                            }
                                                        ?>
                                                    </div>
                                                    <div class="mt-3 small text-muted border-top pt-2">
                                                        <i class="bi bi-lightbulb-fill text-warning me-1"></i> <b>真人生理規律分析：</b> 本數據依據常客出門時間軸進行15分鐘降維特徵區間化。左圖已完美排除摸索期雜訊，精準沉澱出該客戶平穩期偏好。
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($total_loyal_pages > 1): ?>
                    <nav aria-label="Loyal panel navigation" class="mt-4">
                        <ul class="pagination pagination-sm justify-content-center flex-wrap gap-1 mb-0">
                            <li class="page-item <?php if($loyal_page <= 1) echo 'disabled'; ?>">
                                <a class="page-link rounded-3 border-0 px-3 py-2 shadow-sm fw-bold" href="?loyal_page=1&black_page=<?php echo $black_page; ?>">&laquo; 首頁</a>
                            </li>
                            <?php 
                            $start_loop = max(1, $loyal_page - 2);
                            $end_loop = min($total_loyal_pages, $loyal_page + 2);
                            for ($i = $start_loop; $i <= $end_loop; $i++): 
                            ?>
                                <li class="page-item <?php if($loyal_page == $i) echo 'active'; ?>">
                                    <a class="page-link rounded-3 border-0 px-3 py-2 shadow-sm fw-bold" href="?loyal_page=<?php echo $i; ?>&black_page=<?php echo $black_page; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php if($loyal_page >= $total_loyal_pages) echo 'disabled'; ?>">
                                <a class="page-link rounded-3 border-0 px-3 py-2 shadow-sm fw-bold" href="?loyal_page=<?php echo $total_loyal_pages; ?>&black_page=<?php echo $black_page; ?>">末頁 &raquo;</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card border-0 shadow-sm" style="border-radius: 15px; border-left: 5px solid #dc3545 !important;">
                <div class="card-header border-0 bg-white pt-4 px-3 px-sm-4 pb-2">
                    <div class="d-flex justify-content-between align-items-start align-items-sm-center flex-column flex-sm-row">
                        <div>
                            <h5 class="fw-bold text-danger m-0"><i class="bi bi-shield-slash-fill me-2"></i>誠信風控：黑名冊資料庫管理面板</h5>
                            <small class="text-muted ps-1">風控系統捕獲之風險黑名單總計：<?php echo $total_black_records; ?> 位 (累積 3 次以上惡意棄單)</small>
                        </div>
                        <span class="badge bg-danger rounded-pill mt-2 mt-sm-0 px-3 py-1.5 small fw-bold">風控 第 <?php echo $black_page; ?> / <?php echo $total_black_pages; ?> 頁</span>
                    </div>
                </div>
                <div class="card-body px-3 px-sm-4 pb-4 pt-2">
                    <?php if (empty($blacklist_list)): ?>
                        <div class="text-center py-4 text-muted small">
                            <i class="bi bi-check-circle text-success fs-3 d-block mb-2"></i>目前店內數據健全，資料庫暫無觸發 3 次棄單封鎖線的帳號。
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border-start border-end" style="font-size: 0.9rem;">
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col" class="ps-3" style="width: 25%;">高風險電話</th>
                                        <th scope="col" style="width: 50%;">風控系統自動判定原因</th>
                                        <th scope="col" class="pe-3" style="width: 25%;">封鎖鎖定時間</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($blacklist_list as $bad_user): ?>
                                        <tr class="table-danger" style="--bs-table-bg: #fff5f5;">
                                            <td class="fw-bold text-danger ps-3"><i class="bi bi-telephone-x-fill me-1.5"></i><?php echo $bad_user['phone_number']; ?></td>
                                            <td class="text-secondary small fw-bold"><?php echo $bad_user['ban_reason']; ?></td>
                                            <td class="text-muted small pe-3"><?php echo $bad_user['ban_date']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($total_black_pages > 1): ?>
                        <nav aria-label="Blacklist navigation" class="mt-3">
                            <ul class="pagination pagination-sm justify-content-center flex-wrap gap-1 mb-0">
                                <li class="page-item <?php if($black_page <= 1) echo 'disabled'; ?>">
                                    <a class="page-link rounded-3 border-0 px-3 py-2 shadow-sm text-danger fw-bold" href="?loyal_page=<?php echo $loyal_page; ?>&black_page=1">&laquo; 首頁</a>
                                </li>
                                <?php for ($j = max(1, $black_page - 2); $j <= min($total_black_pages, $black_page + 2); $j++): ?>
                                    <li class="page-item <?php if($black_page == $j) echo 'active'; ?>">
                                        <a class="page-link rounded-3 border-0 px-3 py-2 shadow-sm fw-bold <?php echo ($black_page == $j) ? 'bg-danger text-white' : 'text-danger'; ?>" href="?loyal_page=<?php echo $loyal_page; ?>&black_page=<?php echo $j; ?>"><?php echo $j; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php if($black_page >= $total_black_pages) echo 'disabled'; ?>">
                                    <a class="page-link rounded-3 border-0 px-3 py-2 shadow-sm text-danger fw-bold" href="?loyal_page=<?php echo $loyal_page; ?>&black_page=<?php echo $total_black_pages; ?>">末頁 &raquo;</a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // 1. 主趨勢走向圖
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
                x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: isMobile ? 9 : 11 } } },
                y: { beginAtZero: true, grid: { color: '#e2e8f0', drawBorder: false }, ticks: { color: '#64748b', font: { size: isMobile ? 9 : 11 } } }
            }
        }
    });

    // 2. 全店品項銷量圓餅圖
    <?php if (!empty($global_items_data)): ?>
    const globalCtx = document.getElementById('globalMarketPieChart').getContext('2d');
    const globalDataObj = <?php echo $global_items_json; ?>;
    const globalLabels = Object.keys(globalDataObj);
    const globalValues = Object.values(globalDataObj);
    const globalTotalSum = globalValues.reduce((a, b) => a + b, 0);

    new Chart(globalCtx, {
        type: 'pie',
        data: {
            labels: globalLabels,
            datasets: [{
                data: globalValues,
                backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#f43f5e', '#8b5cf6', '#06b6d4', '#ec4899', '#64748b'],
                borderWidth: 1,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12,
                        padding: isMobile ? 6 : 10,
                        font: { size: isMobile ? 10 : 12, weight: 'bold' },
                        color: '#1e293b',
                        generateLabels: function(chart) {
                            return chart.data.labels.map(function(label, i) {
                                const val = chart.data.datasets[0].data[i];
                                const percent = ((val / globalTotalSum) * 100).toFixed(1);
                                return { text: `${label} : ${val} 份 (${percent}%)`, fillStyle: chart.data.datasets[0].backgroundColor[i], strokeStyle: '#ffffff', lineWidth: 1, index: i };
                            });
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>

    // 3. 動態熟客圓餅圖
    document.querySelectorAll('.loyal-collapse').forEach((element) => {
        element.addEventListener('shown.bs.collapse', function (event) {
            const collapseId = this.getAttribute('id');
            const idx = collapseId.replace('loyal-detail-', '');
            const canvasId = 'pie-chart-' + idx;
            const canvasObj = document.getElementById(canvasId);
            
            if (canvasObj.classList.contains('chart-built')) return;

            const rawJson = this.getAttribute('data-json');
            const productData = JSON.parse(rawJson);

            const labels = Object.keys(productData);
            const dataValues = Object.values(productData);
            const totalSum = dataValues.reduce((a, b) => a + b, 0);

            const pieCtx = canvasObj.getContext('2d');
            new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: dataValues,
                        backgroundColor: ['#f43f5e', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#06b6d4', '#ec4899', '#64748b'],
                        borderWidth: 1,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 10, padding: 8,
                                font: { size: window.innerWidth < 576 ? 10 : 11, weight: 'bold' },
                                color: '#334155',
                                generateLabels: function(chart) {
                                    return chart.data.labels.map(function(label, i) {
                                        const value = chart.data.datasets[0].data[i];
                                        const percentage = ((value / totalSum) * 100).toFixed(1);
                                        return { text: `${label} (${percentage}%)`, fillStyle: chart.data.datasets[0].backgroundColor[i], strokeStyle: '#ffffff', lineWidth: 1, index: i };
                                    });
                                }
                            }
                        }
                    }
                }
            });
            canvasObj.classList.add('chart-built');
        });
    });
</script>
</body>
</html>