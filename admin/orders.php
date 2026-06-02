<?php
require_once 'auth.php';
require_once '../api/db.php';

// 🌟 【大數據效能優化】：設定每頁顯示筆數，並動態取得當前頁碼
$limit = 20; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit; 

// 📊 撈取當前總訂單筆數，用來精確計算分頁總數
$total_query = $conn->query("SELECT COUNT(*) as total FROM order_master");
$total_row = $total_query->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $limit);

// ⚡ 高效能 SQL 分頁查詢
$result = $conn->query(
    "SELECT m.*, 
    (SELECT GROUP_CONCAT(CONCAT('• <span class=\"fw-bold text-dark fs-6\">', product_name, '</span> <span class=\"text-danger fw-bold fs-6 ms-1\">x', quantity, '</span> <small class=\"text-secondary ms-1\">(', options, ')</small>') SEPARATOR '<br>') 
     FROM order_detail d WHERE d.order_no = m.order_no) as item_details
    FROM order_master m 
    ORDER BY m.id DESC 
    LIMIT $limit OFFSET $offset"
);
?>

<!DOCTYPE html>
<html lang="zh-tw">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>訂單管理</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .order-detail-box {
            background-color: #fff9c4;
            border: 1px solid #ffe082;
            color: #212529;
            font-size: 0.95rem;
            line-height: 1.8;
            letter-spacing: 0.5px;
        }

        /* ----- 平板與手機版表格轉卡片完美適應 CSS (斷點上調至 991.98px) ----- */
        @media (max-width: 991.98px) {
            .table-responsive {
                overflow-x: hidden;
            }
            .mobile-card-table,
            .mobile-card-table tbody,
            .mobile-card-table tr,
            .mobile-card-table td {
                display: block;
                width: 100%;
            }
            .mobile-card-table thead {
                display: none; 
            }
            .mobile-card-table tr {
                margin-bottom: 1.5rem;
                border: 1px solid #e0e0e0 !important;
                border-radius: 0.75rem;
                background-color: #fff;
                box-shadow: 0 4px 10px rgba(0,0,0,0.08);
                overflow: hidden;
            }
            .mobile-card-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                text-align: right;
                padding: 1rem 1.2rem !important; 
                border-bottom: 1px solid #f1f1f1 !important;
            }
            .mobile-card-table td:last-child {
                border-bottom: none !important;
                background-color: #f8f9fa; 
            }
            .mobile-card-table td::before {
                content: attr(data-label);
                font-weight: bold;
                color: #6c757d;
                flex-shrink: 0;
                margin-right: 1rem;
            }
            .mobile-card-table td.full-width-mobile {
                flex-direction: column;
                align-items: stretch;
            }
            .mobile-card-table td.full-width-mobile::before {
                margin-bottom: 0.5rem;
                text-align: left;
            }
            .mobile-card-table td.full-width-mobile .order-detail-box {
                text-align: left !important;
            }
            .action-wrapper {
                display: flex;
                justify-content: space-between;
                align-items: center;
                width: 100%;
            }
        }
    </style>
</head>

<body class="bg-light">

    <?php include 'navbar.php'; ?>

    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex flex-column">
                <h2 class="fw-bold m-0"><i class="bi bi-receipt text-warning me-2"></i>訂單管理</h2>
                <small class="text-muted mt-1 ps-1">系統內累積數據：<?php echo number_format($total_records); ?> 筆 ｜ 當前分頁：第 <?php echo $page; ?> / <?php echo $total_pages; ?> 頁</small>
            </div>
            <button class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm align-self-start" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise me-1"></i>重整訂單
            </button>
        </div>

        <div class="card shadow border-0 mb-4">
            <div class="card-body p-md-4">
                <div class="table-responsive border-0">
                    <table class="table table-hover align-middle mobile-card-table mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>訂單編號</th>
                                <th>客戶姓名</th>
                                <th>電話</th>
                                <th>取餐日期</th>
                                <th>取餐時間</th>
                                <th style="width: 28%;">餐點明細</th>
                                <th>總金額</th>
                                <th>訂單狀態</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="訂單編號" class="fw-bold text-secondary"><?php echo $row['order_no']; ?></td>
                                    <td data-label="客戶姓名"><span class="fw-bold text-dark"><?php echo $row['customer_name']; ?></span></td>
                                    
                                    <td data-label="電話">
                                        <a href="tel:<?php echo $row['phone']; ?>" class="text-decoration-none fw-bold">
                                            <?php echo $row['phone']; ?>
                                        </a>
                                    </td>
                                    
                                    <td data-label="取餐日期"><?php echo $row['pickup_date']; ?></td>
                                    <td data-label="取餐時間"><span class="badge bg-info text-dark fs-6 shadow-sm"><?php echo $row['pickup_time']; ?></span></td>

                                    <td data-label="餐點明細" class="full-width-mobile">
                                        <div class="order-detail-box p-2 rounded shadow-sm text-start">
                                            <?php echo !empty($row['item_details']) ? $row['item_details'] : '<span class="text-muted">無明細資料</span>'; ?>
                                        </div>
                                    </td>

                                    <td data-label="總金額" class="text-danger fw-bold fs-5">$<?php echo number_format($row['total_price']); ?></td>

                                    <td data-label="訂單狀態" class="full-width-mobile">
                                        <div class="action-wrapper">
                                            <?php
                                            if ($row['status'] == '待處理') {
                                                echo '<span class="badge bg-warning text-dark px-3 py-2 fs-6 shadow-sm mb-2 mb-md-0">待處理</span>';
                                                echo '<div>';
                                                echo '<a href="update_order.php?id=' . $row['id'] . '&status=已完成" class="btn btn-sm btn-success me-1 fw-bold shadow-sm">完成</a>';
                                                echo '<a href="update_order.php?id=' . $row['id'] . '&status=已取消" class="btn btn-sm btn-danger fw-bold shadow-sm" onclick="return confirm(\'確定要取消此訂單嗎？\')">取消</a>';
                                                echo '</div>';
                                            } elseif ($row['status'] == '已完成' || $row['status'] == '已取餐') {
                                                echo '<span class="badge bg-success px-3 py-2 fs-6 shadow-sm">已完成</span>';
                                            } elseif ($row['status'] == '死鎖' || $row['status'] == '已取消') {
                                                echo '<span class="badge bg-danger px-3 py-2 fs-6 shadow-sm">已取消</span>';
                                            } else {
                                                echo '<span class="badge bg-secondary px-3 py-2 fs-6">' . $row['status'] . '</span>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; // 🌟 這裡已精準將原先出錯的 endforeach 改回 endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination pagination-sm justify-content-center flex-wrap gap-1">
                <li class="page-item <?php if($page <= 1) echo 'disabled'; ?>">
                    <a class="page-link rounded-3 border-0 px-3 py-2 shadow-sm fw-bold" href="?page=1">&laquo; 首頁</a>
                </li>
                
                <?php 
                $start_loop = max(1, $page - 2);
                $end_loop = min($total_pages, $page + 2);
                for ($i = $start_loop; $i <= $end_loop; $i++): 
                ?>
                    <li class="page-item <?php if($page == $i) echo 'active'; ?>">
                        <a class="page-link rounded-3 border-0 px-3 py-2 shadow-sm fw-bold" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?php if($page >= $total_pages) echo 'disabled'; ?>">
                    <a class="page-link rounded-3 border-0 px-3 py-2 shadow-sm fw-bold" href="?page=<?php echo $total_pages; ?>">末頁 &raquo;</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>

    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>