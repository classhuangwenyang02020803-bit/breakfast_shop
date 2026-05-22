<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="/breakfast_shop">
    <title>王媽媽早餐店 | 暖心手工早餐</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/shop.css">

    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        html {
            scroll-behavior: smooth;
        }

        [v-cloak] {
            display: none;
        }

        /* 防止 Vue 加載前的閃爍 */

        /* 自定義選項按鈕 (Label/Input) 樣式 */
        .option-group {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .option-item {
            flex: 1;
            min-width: 60px;
        }

        .option-item input {
            display: none;
        }

        .option-item label {
            display: block;
            width: 100%;
            padding: 5px 2px;
            font-size: 0.8rem;
            text-align: center;
            border-radius: 50px;
            background: #f1f3f5;
            color: #495057;
            border: 1px solid #dee2e6;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: bold;
        }

        .option-item input:checked+label {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* 產品卡片微調 */
        .product-card {
            border-radius: 20px;
            overflow: hidden;
            transition: 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .img-container {
            height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }

        .img-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* 浮動按鈕群組 (讓按鈕固定在畫面右下角) */
        .floating-menu {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1060;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .fab-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            text-decoration: none;
        }

        .fab-menu {
            background-color: #ffc107;
            color: #212529;
        }

        .fab-top {
            background-color: #212529;
            color: #fff;
        }

        .fab-btn:hover {
            transform: scale(1.1);
        }
    </style>
</head>

<body id="top" class="bg-transparent">
    <div id="app" v-cloak>
        <div class="bg-overlay"></div>

        <nav class="navbar navbar-expand-lg navbar-dark fixed-top shadow-sm" style="background: rgba(0,0,0,0.8); backdrop-filter: blur(10px); z-index: 1050;">
            <div class="container">
                <a href="admin/login.php" class="btn btn-dark border border-secondary rounded-circle d-flex align-items-center justify-content-center me-2 shadow-sm" style="width: 38px; height: 38px;">
                    <i class="bi bi-person-badge text-light"></i>
                </a>
                <a class="navbar-brand fw-bold me-auto" href="#">王媽媽早餐店</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0 d-lg-none mt-2 border-top border-secondary pt-3">
                        <li class="nav-item" v-for="cat in categories" :key="cat.id">
                            <a class="nav-link text-white fs-5 fw-bold" href="javascript:void(0);" @click="scrollToCategory(cat.id)">{{ cat.title }}</a>
                        </li>
                    </ul>
                    <div class="ms-auto d-flex flex-column flex-lg-row gap-2 align-items-lg-center">
                        <button class="btn btn-warning btn-sm rounded-pill px-4 fw-bold shadow-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#searchOffcanvas" @click="closeNavbar">
                            <i class="bi bi-search me-1"></i> 查詢訂單
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <section class="hero-section d-flex align-items-center justify-content-center text-center text-white">
            <div class="hero-overlay"></div>
            <div class="hero-content mt-5">
                <h5 class="mb-3" style="letter-spacing: 8px; color: #FFE0B2;">GOOD MORNING</h5>
                <h1 class="display-3 fw-bold">王媽媽早餐店</h1>
                <p class="lead">暖心手工預約系統，開啟美好的一天。</p>
            </div>
        </section>

        <div class="container py-4" style="position: relative; z-index: 5;">
            <div class="row justify-content-center mb-5">
                <div class="col-md-6 col-lg-5">
                    <div class="input-group shadow-sm rounded-pill overflow-hidden border border-warning border-2">
                        <input type="text" v-model="keyword" class="form-control border-0 px-4 py-2" placeholder="搜尋餐點...">
                    </div>
                </div>
            </div>

            <div v-for="cat in categories" :key="cat.id" :id="cat.id" class="row g-4 mb-5" style="scroll-margin-top: 100px;">
                <div class="col-12">
                    <h3 class="fw-bold text-white border-start border-warning border-4 ps-3 mb-4">{{ cat.title }}</h3>
                </div>

                <div v-for="p in filteredProducts(cat.type)" :key="p.id" class="col-12 col-md-6 col-lg-3">
                    <div class="card product-card h-100 shadow-sm border-0">
                        <div class="img-container">
                            <img :src="getImagePath(p.image)" :alt="p.name">
                        </div>
                        <div class="card-body d-flex flex-column p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="fw-bold text-dark mb-0">{{ p.name }}</h5>
                                <span class="text-danger fw-bold fs-5">${{ calculateCurrentPrice(p) }}</span>
                            </div>

                            <div class="options-container mt-2">
                                <template v-if="cat.type === 'drink'">
                                    <div class="mb-2">
                                        <div class="option-group">
                                            <div class="option-item">
                                                <input type="radio" :id="'size-m-'+p.id" value="小杯" v-model="p.selectedSize">
                                                <label :for="'size-m-'+p.id">小杯</label>
                                            </div>
                                            <div class="option-item">
                                                <input type="radio" :id="'size-l-'+p.id" value="大杯" v-model="p.selectedSize">
                                                <label :for="'size-l-'+p.id">大杯</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3" v-if="needsSugar(p.name)">
                                        <div class="option-group">
                                            <div class="option-item" v-for="s in ['全糖','半糖','無糖']" :key="s">
                                                <input type="radio" :id="'sugar-'+s+'-'+p.id" :value="s" v-model="p.selectedSugar">
                                                <label :for="'sugar-'+s+'-'+p.id">{{ s }}</label>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template v-else>
                                    <div class="mb-2" v-if="needsEgg(p.name)">
                                        <div class="option-group">
                                            <div class="option-item">
                                                <input type="radio" :id="'egg-no-'+p.id" value="不加蛋" v-model="p.selectedEgg">
                                                <label :for="'egg-no-'+p.id">不加蛋</label>
                                            </div>
                                            <div class="option-item">
                                                <input type="radio" :id="'egg-yes-'+p.id" value="加蛋" v-model="p.selectedEgg">
                                                <label :for="'egg-yes-'+p.id">加蛋(+10)</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3" v-if="needsSpicy(p.name)">
                                        <div class="option-group">
                                            <div class="option-item" v-for="sp in ['不辣','小辣','大辣']" :key="sp">
                                                <input type="radio" :id="'spicy-'+sp+'-'+p.id" :value="sp" v-model="p.selectedSpicy">
                                                <label :for="'spicy-'+sp+'-'+p.id">{{ sp }}</label>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <input type="text" class="form-control form-control-sm rounded-pill mb-3" v-model="p.note" placeholder="備註">
                            </div>

                            <div class="mt-auto d-flex align-items-center gap-2">
                                <div class="input-group input-group-sm w-50 rounded-pill overflow-hidden border">
                                    <button class="btn btn-light border-0" @click="p.qty > 1 ? p.qty-- : 1">-</button>
                                    <input type="text" class="form-control border-0 text-center fw-bold" v-model.number="p.qty">
                                    <button class="btn btn-light border-0" @click="p.qty++">+</button>
                                </div>
                                <button class="btn btn-warning btn-sm w-50 fw-bold rounded-pill" @click="addToCart(p)">
                                    <i class="bi bi-cart-plus me-1"></i>加入
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-lg p-4 mt-5 mb-5 border-0" id="cart-section" style="border-radius: 20px; background: rgba(255,255,255,0.95);">
                <h4 class="fw-bold mb-4 text-dark border-start border-primary border-4 ps-2"><i class="bi bi-cart3 me-2"></i>購物車清單</h4>

                <div v-if="cart.length === 0" class="text-muted text-center py-4 bg-light rounded-3">
                    購物車還是空的，快去選購吧！
                </div>

                <div v-else>
                    <div v-for="(item, index) in cart" :key="index" class="d-flex justify-content-between align-items-center mb-2 p-3 bg-white rounded-3 border shadow-sm">
                        <div style="flex: 1;">
                            <div class="fw-bold">{{ item.name }} <span class="badge bg-warning text-dark ms-1">${{ item.price }}</span></div>
                            <div class="text-muted small">{{ item.options }} {{ item.note ? '| 備註: ' + item.note : '' }}</div>
                        </div>
                        <div class="mx-3 fw-bold text-danger">x{{ item.qty }}</div>
                        <div class="fw-bold mx-2 text-end" style="width: 60px;">${{ item.subtotal }}</div>
                        <button class="btn btn-sm btn-outline-danger border-0 rounded-circle" @click="removeFromCart(index)">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>

                    <div class="bg-light p-4 rounded-4 border my-4">
                        <h6 class="fw-bold mb-3">填寫預約資訊</h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="small text-muted mb-1">姓名</label>
                                <input type="text" v-model="custInfo.name" class="form-control rounded-pill">
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted mb-1">電話</label>
                                <input type="tel" v-model="custInfo.phone" class="form-control rounded-pill">
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted mb-1">取餐日期</label>
                                <input type="date" v-model="custInfo.date" :min="todayDate" class="form-control rounded-pill px-3">
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted mb-1">時間</label>
                                <select v-model="custInfo.time" class="form-select rounded-pill">
                                    <option v-for="t in timeOptions" :value="t">{{ t }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <h4 class="mb-0">總金額：<span class="text-danger fw-bold fs-2">${{ cartTotal }}</span> 元</h4>
                        <button class="btn btn-danger btn-lg rounded-pill px-5 fw-bold shadow" @click="submitOrder">確認送出</button>
                    </div>
                </div>
            </div>
        </div>

        <footer class="bg-dark text-light py-5 mt-5">
            <div class="container">
                <div class="row g-4">
                    <div class="col-lg-4 col-md-6">
                        <h4 class="fw-bold mb-4 text-warning"><i class="bi bi-shop me-2"></i>王媽媽早餐店</h4>
                        <p class="text-secondary">在地經營多年的手工美味，每日凌晨現做，為台中的早晨提供最暖心的溫度。</p>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <h5 class="fw-bold mb-4 border-start border-warning border-3 ps-3">聯絡我們</h5>
                        <ul class="list-unstyled text-secondary">
                            <li class="mb-2"><i class="bi bi-geo-alt-fill me-2 text-warning"></i> 411臺中市太平區中山路二段387號一樓</li>
                            <li class="mb-2"><i class="bi bi-telephone-fill me-2 text-warning"></i> (04) 2392-3299</li>
                            <li class="mb-2"><i class="bi bi-clock-fill me-2 text-warning"></i> 週二至週日 06:00 - 10:00 週一公休</li>
                        </ul>
                    </div>
                    <div class="col-lg-4 col-md-12">
                        <h5 class="fw-bold mb-4 border-start border-warning border-3 ps-3">位置資訊</h5>
                        <div class="rounded overflow-hidden shadow-sm ratio ratio-16x9">
                            <iframe src="https://maps.google.com/maps?q=王媽媽早餐店,臺中市太平區中山路二段387號一樓&z=17&output=embed" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                        </div>
                    </div>
                </div>
                <hr class="my-4 border-secondary">
                <div class="text-center">
                    <small class="text-secondary">© 2026 王媽媽早餐店. All rights reserved.</small>
                </div>
            </div>
        </footer>

        <div class="floating-menu">
            <button class="fab-btn bg-danger text-white position-relative" type="button" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas" title="查看購物車">
                <i class="bi bi-cart3"></i>
                <span v-if="cartCount > 0" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark border border-light" style="font-size: 0.65rem;">{{ cartCount }}</span>
            </button>
            <a href="javascript:void(0);" @click="scrollToCategory('cat-drinks')" class="fab-btn fab-menu" title="去點餐">
                <i class="bi bi-journal-text"></i>
            </a>
            <a href="#top" class="fab-btn fab-top" title="回頂部">
                <i class="bi bi-arrow-up-short"></i>
            </a>
        </div>

        <div class="offcanvas offcanvas-end shadow-lg" tabindex="-1" id="cartOffcanvas">
            <div class="offcanvas-header bg-warning border-bottom border-dark">
                <h5 class="offcanvas-title fw-bold">目前的購物車</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body bg-light">
                <div v-if="cart.length === 0" class="text-center py-5 text-muted">購物車是空的</div>
                <div v-for="(item, index) in cart" :key="index" class="card mb-2 border-0 shadow-sm p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold">{{ item.name }}</div>
                            <div class="small text-muted">{{ item.options }}</div>
                        </div>
                        <div class="fw-bold text-danger">x{{ item.qty }}</div>
                    </div>
                </div>
                <div class="mt-4 border-top pt-3">
                    <h5 class="text-end mb-3">總計：<span class="text-danger">${{ cartTotal }}</span></h5>
                    <button class="btn btn-danger w-100 rounded-pill fw-bold" @click="scrollToCart" data-bs-dismiss="offcanvas">前往結帳</button>
                </div>
            </div>
        </div>

        <div class="offcanvas offcanvas-start shadow-lg" tabindex="-1" id="searchOffcanvas">
            <div class="offcanvas-header bg-primary text-white">
                <h5 class="offcanvas-title fw-bold">查詢預約訂單</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body">
                <div class="mb-3">
                    <input type="text" v-model="searchQuery.name" class="form-control rounded-pill mb-2" placeholder="姓名">
                    <input type="tel" v-model="searchQuery.phone" class="form-control rounded-pill mb-2" placeholder="電話">
                    <button class="btn btn-primary w-100 rounded-pill fw-bold" @click="searchOrders" :disabled="isSearching">
                        {{ isSearching ? '查詢中...' : '開始查詢' }}
                    </button>
                </div>
                <div v-if="searchResults.length > 0">
                    <div v-for="o in searchResults" :key="o.order_no" class="card mb-3 border-start border-primary border-4 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="small text-secondary">單號：{{ o.order_no }}</span>
                                <span class="text-danger fw-bold">${{ o.total_price }}</span>
                            </div>
                            <div class="small fw-bold mb-2"><i class="bi bi-clock me-1"></i>{{ o.pickup_date }} {{ o.pickup_time }}</div>
                            
                            <div class="mb-2">
                                <span v-if="o.status === '待處理'" class="badge bg-warning rounded-pill px-3"><i class="bi bi-hourglass-split me-1"></i>🍳 備餐中，請稍候</span>
                                <span v-else-if="o.status === '已完成'" class="badge bg-success rounded-pill px-3"><i class="bi bi-bag-check-fill me-1"></i>🎉 已完成！請憑單取餐</span>
                                <span v-else-if="o.status === '已取消'" class="badge bg-danger rounded-pill px-3"><i class="bi bi-x-circle-fill me-1"></i>❌ 訂單已取消</span>
                                <span v-else class="badge bg-warning text-dark rounded-pill px-3">{{ o.status }}</span>
                            </div>

                            <div class="bg-light p-2 rounded small">
                                <div v-for="d in o.details">• {{ d.product_name }} x{{ d.quantity }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-else-if="hasSearched" class="text-center text-muted mt-5">找不到相關訂單喔！</p>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js?v=<?php echo time(); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>