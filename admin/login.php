<?php
session_start();
require_once '../api/db.php';

// 如果已經登入（或有Cookie），直接跳轉
if (isset($_SESSION['admin']) || isset($_COOKIE['admin_cookie'])) {
    if (!isset($_SESSION['admin'])) {
        $_SESSION['admin'] = $_COOKIE['admin_cookie'];
    }
    header('Location: products.php');
    exit;
}

$error = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $remember = isset($_POST['remember']) ? true : false; // 接收是否勾選保持登入

    // 1. 先從管理員表 (admins) 找
    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // 比對密碼
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin'] = $row['username'];
            $_SESSION['role'] = 'admin'; // 標記為管理員
            
            // 處理保持登入 Cookie
            if ($remember) {
                setcookie('admin_cookie', $row['username'], time() + (86400 * 30), "/");
            } else {
                setcookie('admin_cookie', '', time() - 3600, "/");
            }

            header('Location: products.php');
            exit;
        } else {
            $error = '管理員密碼錯誤';
        }
    } else {
        // 2. 如果 admins 找不到，再去一般用戶表 (users) 找
        $stmt_user = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt_user->bind_param("s", $username);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();

        if ($user_row = $result_user->fetch_assoc()) {
            if (password_verify($password, $user_row['password'])) {
                $_SESSION['admin'] = $user_row['username']; // 統一存入 session
                $_SESSION['role'] = 'user'; // 標記為一般用戶
                
                // 處理保持登入 Cookie
                if ($remember) {
                    setcookie('admin_cookie', $user_row['username'], time() + (86400 * 30), "/");
                } else {
                    setcookie('admin_cookie', '', time() - 3600, "/");
                }

                header('Location: products.php');
                exit;
            } else {
                $error = '用戶密碼錯誤';
            }
        } else {
            $error = '帳號不存在';
        }
        $stmt_user->close();
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="zh-tw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>早餐店後台登入</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* 全域垂直水平置中，確保在所有尺寸的螢幕上視覺都完美平衡 */
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }
        
        /* 登入卡片微調 */
        .login-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07) !important;
        }

        /* 返回按鈕在滑鼠懸停時往左移動的動畫 */
        .btn-back {
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            transform: translateX(-3px);
            background-color: #e9ecef;
        }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="row justify-content-center w-100 m-0">
        
        <div class="col-12 col-sm-8 col-md-6 col-lg-4">
            
            <div class="d-flex justify-content-start mb-3">
                <a href="../index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm btn-back fw-bold">
                    <i class="bi bi-arrow-left me-1"></i> 返回點餐
                </a>
            </div>

            <div class="card login-card">
                <div class="card-body p-4 p-sm-5">
                    
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-dark mb-1">管理系統</h2>
                        <small class="text-muted">王媽媽早餐店後台維護</small>
                    </div>
                    
                    <?php if ($error != ''): ?>
                        <div class="alert alert-danger alert-dismissible fade show rounded-3 small">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small">登入帳號</label>
                            <input type="text" name="username" class="form-control form-control-lg fs-6" placeholder="請輸入管理員或員工帳號" required autocomplete="username">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small">登入密碼</label>
                            <input type="password" name="password" class="form-control form-control-lg fs-6" placeholder="請輸入密碼" required autocomplete="current-password">
                        </div>
                        
                        <div class="form-check mb-4 mt-3">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                            <label class="form-check-label text-muted small" for="remember">
                                此裝置保持登入 30 天
                            </label>
                        </div>

                        <button type="submit" name="login" class="btn btn-dark w-100 py-2.5 fw-bold rounded-3 shadow-sm fs-5">
                            確認登入
                        </button>
                    </form>
                </div>
            </div>

            <div class="text-center mt-4 text-secondary">
                <small style="font-size: 0.8rem;"><i class="bi bi-info-circle me-1"></i>系統提示：管理員與一般員工帳號均由此入口驗證</small>
            </div>
            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>