<?php
require_once 'auth.php';
require_once '../api/db.php';

// 處理刪除請求
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    // 安全防護：禁止刪除自己
    if (isset($_SESSION['admin_id']) && $delete_id == $_SESSION['admin_id']) {
        $error = "你不能刪除目前正在使用的管理員帳號！";
    } else {
        // 關鍵修正：指向 admins 表
        $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        header("Location: manage_admins.php?msg=deleted");
        exit;
    }
}

// 抓取所有管理員 (指向 admins 表)
$result = $conn->query("SELECT id, username, created_at FROM admins ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="zh-tw">
<head>
    <meta charset="UTF-8">
    <title>系統管理員設定</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-shield-lock-fill text-danger"></i> 系統管理員設定</h2>
        <div>
            <a href="admin_create.php" class="btn btn-danger"><i class="bi bi-person-plus"></i> 新增管理員</a>
        </div>
    </div>

    <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card shadow border-0">
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>管理員帳號</th>
                        <th>建立時間</th>
                        <th class="text-center">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><span class="badge bg-danger">ADMIN</span> <strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                        <td><?php echo $row['created_at']; ?></td>
                        <td class="text-center">
                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('警告：刪除管理員將使其失去後台權限！確認刪除？')">
                                <i class="bi bi-trash"></i> 刪除
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>