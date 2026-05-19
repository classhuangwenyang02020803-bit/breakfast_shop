<?php
require_once 'auth.php';
require_once '../api/db.php';

// 處理刪除請求
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    // 關鍵修正：指向 users 表
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header("Location: manage_users.php?msg=deleted");
    exit;
}

// 抓取所有一般使用者 (指向 users 表)
$result = $conn->query("SELECT id, username, created_at FROM users ORDER BY id DESC");

// 將資料存入陣列，方便判斷是否有資料
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
?>

<!DOCTYPE html>
<html lang="zh-tw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>一般帳號管理</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* ----- 手機版表格轉卡片完美適應 CSS ----- */
        @media (max-width: 767.98px) {
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
                margin-bottom: 1rem;
                border: 1px solid #dee2e6 !important;
                border-radius: 0.75rem;
                background-color: #fff;
                box-shadow: 0 4px 6px rgba(0,0,0,0.05);
                overflow: hidden;
            }
            .mobile-card-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                text-align: right;
                padding: 0.8rem 1rem !important;
                border-bottom: 1px solid #f8f9fa !important;
            }
            .mobile-card-table td:last-child {
                border-bottom: none !important;
                background-color: #f8f9fa;
                justify-content: center;
            }
            .mobile-card-table td::before {
                content: attr(data-label);
                font-weight: bold;
                color: #6c757d;
                flex-shrink: 0;
                margin-right: 1rem;
            }
            .mobile-card-table td.action-cell::before {
                display: none;
            }
            .mobile-card-table td.action-cell {
                padding: 1rem !important;
            }
        }
    </style>
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <h2 class="m-0 fs-3"><i class="bi bi-people-fill text-primary me-2"></i>帳號管理</h2>
        <div>
            <a href="user_create.php" class="btn btn-primary shadow-sm rounded-pill px-4 w-100 w-md-auto">
                <i class="bi bi-person-plus me-1"></i> 新增帳號
            </a>
        </div>
    </div>

    <div class="card shadow border-0 w-100">
        <div class="card-body p-md-4">
            
            <?php if (empty($users)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-person-x text-muted" style="font-size: 4rem;"></i>
                    <h5 class="mt-3 text-secondary">目前還沒有一般帳號</h5>
                    <p class="text-muted small">點擊上方的「新增帳號」按鈕來建立第一個使用者吧！</p>
                </div>
            <?php else: ?>
                <div class="table-responsive border-0">
                    <table class="table table-hover align-middle mobile-card-table mb-0 w-100">
                        <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>帳號名稱</th>
                                <th>建立時間</th>
                                <th class="text-center">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $row): ?>
                            <tr>
                                <td data-label="ID"><?php echo $row['id']; ?></td>
                                <td data-label="帳號名稱">
                                    <strong class="text-dark fs-5"><?php echo htmlspecialchars($row['username']); ?></strong>
                                </td>
                                <td data-label="建立時間">
                                    <span class="text-muted small"><?php echo date('Y/m/d H:i', strtotime($row['created_at'])); ?></span>
                                </td>
                                <td class="text-center action-cell">
                                    <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-outline-danger btn-sm rounded-pill px-4 fw-bold w-100 w-md-auto" onclick="return confirm('確定要刪除此帳號嗎？')">
                                        <i class="bi bi-trash"></i> 刪除
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>