<style>
    /* 導覽列微調：增加毛玻璃質感與呼吸感 */
    .custom-navbar {
        padding: 0.8rem 1rem;
        background: rgba(33, 37, 41, 0.95) !important; /* 深色背景微透明 */
        backdrop-filter: blur(10px); /* 輕微毛玻璃效果 */
    }

    /* 讓漢堡選單展開時，選項置中並有適當間距 */
    @media (max-width: 991.98px) {
        .navbar-collapse {
            text-align: center;
            padding: 1.5rem 0;
        }
        .navbar-nav .nav-item {
            margin: 0.5rem 0;
        }
        .admin-info-zone {
            justify-content: center;
            flex-direction: column;
            gap: 1rem;
            margin-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.1) !important;
            padding-top: 1.5rem !important;
        }
    }

    /* 選項懸浮效果 */
    .nav-link {
        transition: all 0.3s ease;
        font-weight: 500;
        letter-spacing: 0.5px;
    }
    .nav-link:hover {
        color: #ffc107 !important; /* 懸浮變成黃色 */
        transform: translateY(-1px);
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-5 shadow custom-navbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center fw-bold fs-4" href="dashboard.php">
            <i class="bi bi-shop me-2 text-warning"></i> 
            <span style="letter-spacing: 1px;">管理後台</span>
        </a>

        <button class="navbar-toggler border-0" type="button" 
                data-bs-toggle="collapse" 
                data-bs-target="#adminNav" 
                aria-controls="adminNav" 
                aria-expanded="false" 
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link px-lg-3 text-info" href="../index.php">
                        <i class="bi bi-house-door-fill me-1"></i>前台首頁
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-lg-3" href="products.php">
                        <i class="bi bi-egg-fried me-1"></i>商品管理
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-lg-3" href="orders.php">
                        <i class="bi bi-cart-check me-1"></i>訂單管理
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-lg-3" href="manage_users.php">
                        <i class="bi bi-people me-1"></i>帳號管理
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center admin-info-zone border-0">
                <span class="text-light me-lg-4 mb-3 mb-lg-0 small">
                    <i class="bi bi-person-circle me-1 text-secondary"></i>
                    <?php echo htmlspecialchars($_SESSION['admin'] ?? '管理者'); ?>
                </span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-box-arrow-right me-1"></i>登出
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(event) {
        const adminNav = document.getElementById('adminNav');
        const toggler = document.querySelector('.navbar-toggler');
        
        // 如果選單是展開的狀態
        if (adminNav && adminNav.classList.contains('show')) {
            // 確認點擊的位置「不是」在選單內部，也「不是」漢堡按鈕本身
            if (!adminNav.contains(event.target) && !toggler.contains(event.target)) {
                // 使用 Bootstrap 的 API 把它收起來
                const bsCollapse = bootstrap.Collapse.getInstance(adminNav);
                if (bsCollapse) {
                    bsCollapse.hide();
                }
            }
        }
    });
});
</script>