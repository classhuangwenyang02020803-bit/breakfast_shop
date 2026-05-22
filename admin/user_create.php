<?php
session_start();
require_once '../api/db.php';

// 🔒 權限核心鎖 1：如果根本沒登入，先踢回登入頁
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// 🔒 權限核心鎖 2：雖然登入了，但如果「身份不是 admin」，立刻彈出警告並踢回儀表板！
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>
        alert('【安全警告】您的權限不足！只有最高管理員(Admin)才能建立新帳號。');
        window.location.href = 'dashboard.php';
    </script>";
    exit; // 強制切斷，不讓一般員工看到下方的表單，也不讓他們提交資料
}

$message = '';

if (isset($_POST['submit'])) {
    $user = trim($_POST['username']);
    $pass = $_POST['password'];
    $pass_confirm = $_POST['password_confirm'];

    if (empty($user) || empty($pass)) {
        $message = '<div class="alert alert-warning">請填寫所有欄位！</div>';
    } 
    else if ($pass !== $pass_confirm) {
        $message = '<div class="alert alert-danger">兩次密碼輸入不一致！</div>';
    } 
    else {
        // --- 修正點 1：檢查 users 表而不是 admins ---
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $user);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $message = '<div class="alert alert-danger">帳號名稱「' . htmlspecialchars($user) . '」已存在！</div>';
        } else {
            // 安全加密
            $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
            
            // --- 修正點 2：確保存入 users 表 ---
            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $user, $hashed_password);
            
            if ($stmt->execute()) {
                header("Location: manage_users.php?msg=success");
                exit;
            } else {
                $message = '<div class="alert alert-danger">系統錯誤：' . $conn->error . '</div>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-tw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新增帳號 - 早餐店後台</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">

<?php include 'navbar.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <h3 class="mb-4 text-center"><i class="bi bi-person-plus-fill"></i> 新增一般帳號</h3>
                    
                    <?php echo $message; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">帳號名稱</label>
                            <input type="text" name="username" class="form-control" required placeholder="請輸入帳號">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">設定密碼</label>
                            <input type="password" name="password" class="form-control" required placeholder="請輸入密碼">
                        </div>
                        <div class="mb-4">
                            <label class="form-label">再次確認密碼</label>
                            <input type="password" name="password_confirm" class="form-control" required placeholder="請再次輸入密碼">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="submit" class="btn btn-primary">確認建立帳號</button>
                            <a href="manage_users.php" class="btn btn-link text-secondary">取消返回</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>