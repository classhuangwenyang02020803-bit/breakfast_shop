const { createApp } = Vue;

createApp({
    data() {
        return {
            // 基礎資料
            keyword: '',
            products: [],
            categories: [
                { id: 'cat-drinks', title: '🍹 精選飲品', type: 'drink' },
                { id: 'cat-foods', title: '🍔 美味餐點', type: 'food' }
            ],

            // 購物車與客戶資料
            cart: [],
            custInfo: {
                name: '',
                phone: '',
                date: new Date().toISOString().slice(0, 10),
                time: ''
            },
            todayDate: '', // 🌟 新增：用來存放今天日期的限制變數
            timeOptions: [],

            // 訂單查詢資料
            searchQuery: { name: '', phone: '' },
            searchResults: [],
            isSearching: false,
            hasSearched: false
        };
    },
    computed: {
        cartTotal() {
            return this.cart.reduce((sum, item) => sum + item.subtotal, 0);
        },
        cartCount() {
            return this.cart.reduce((sum, item) => sum + item.qty, 0);
        }
    },
    methods: {
        async fetchProducts() {
            try {
                // 補上 ?keyword= 參數，讓 PHP 不會因為找不到變數而報錯
                const res = await fetch('api/get_products.php?keyword=');
                const data = await res.json();

                // 為每個產品加上 Vue 需要的響應式狀態 (預設選項)
                this.products = data.products.map(p => ({
                    ...p,
                    qty: 1,
                    selectedSize: '小杯',
                    selectedSugar: '全糖',
                    selectedEgg: '不加蛋',
                    selectedSpicy: '不辣',
                    note: ''
                }));
            } catch (err) {
                console.error("載入失敗", err);
            }
        },

        filteredProducts(type) {
            const drinkKeywords = ['奶茶', '紅茶', '豆漿', '米漿'];
            return this.products.filter(p => {
                const isDrink = drinkKeywords.some(key => p.name.includes(key));
                const matchType = (type === 'drink') ? isDrink : !isDrink;
                const matchKeyword = p.name.includes(this.keyword);
                return matchType && matchKeyword;
            });
        },

        calculateCurrentPrice(p) {
            let price = parseInt(p.price);
            if (p.selectedSize === '大杯') price = parseInt(p.price_l || p.price);
            if (p.selectedSize === '小杯') price = parseInt(p.price_m || p.price);
            if (p.selectedEgg === '加蛋') price += 10;
            return price;
        },

        getImagePath(img) {
            const meta = document.querySelector('meta[name="base-url"]');
            return (meta ? meta.content : '') + '/' + img;
        },

        needsEgg(name) {
            return ['蔥油餅', '韭菜盒', '大餅', '饅頭'].some(k => name.includes(k));
        },

        needsSugar(name) {
            // 在這裡填入「不需要」甜度選項的飲料關鍵字
            const noSugarItems = ['奶茶', '米漿']; 
            
            // 如果飲料名字「包含」上面的字，就回傳 false (不顯示)
            return !noSugarItems.some(k => name.includes(k));
        },

        // 判斷哪些餐點需要挑選辣度
        // 目前設定：名稱包含「卡拉雞」、「鐵板麵」、「打拋肉」、「炒麵」才需要選辣度
        needsSpicy(name) {
            return !['皮蛋瘦肉粥', '甜燒餅'].some(k => name.includes(k));
            
            // 💡 小提示：您可以自由調整上面的陣列。
            // 如果您希望「只要是主食類，除了吐司以外都要選辣度」，也可以改成排除法：
            // return !['吐司', '厚片', '饅頭'].some(k => name.includes(k));
        },

        initTimeOptions() {
            const times = [];
            for (let h = 6; h <= 10; h++) {
                for (let m = 0; m < 60; m += 15) {
                    if (h === 10 && m > 0) break;
                    times.push(`${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`);
                }
            }
            this.timeOptions = times;
        },

        addToCart(p) {
            const currentPrice = this.calculateCurrentPrice(p);
            let options = "";
            
            if (this.needsEgg(p.name)) {
                // 如果需要加蛋，再看它需不需要選辣度
                options = this.needsSpicy(p.name) ? `${p.selectedEgg} / ${p.selectedSpicy}` : p.selectedEgg;
            } else if (p.price_m) { 
                // 飲料部分的邏輯（維持您剛才改好的設定）
                options = this.needsSugar(p.name) ? `${p.selectedSize} / ${p.selectedSugar}` : p.selectedSize;
            } else {
                // 一般不需要加蛋的主食（例如：漢堡肉餅、薯條等）
                // 如果需要選辣度就給辣度，不需要的話 options 就留空（""）
                options = this.needsSpicy(p.name) ? p.selectedSpicy : "";
            }

            this.cart.push({
                id: p.id,
                name: p.name,
                price: currentPrice,
                qty: p.qty,
                subtotal: p.qty * currentPrice,
                options: options, // 這裡帶入剛剛組合好的乾淨字串
                note: p.note
            });

            // ...（下方原有的重置與 Swal 提示維持不變）

            // 重置該產品狀態
            p.qty = 1;
            p.note = "";

            Swal.fire({
                toast: true, position: 'top-end', icon: 'success',
                title: `已加入 ${p.name}`, showConfirmButton: false, timer: 1500
            });
        },

        removeFromCart(index) {
            this.cart.splice(index, 1);
        },

        scrollToCategory(id) {
            const el = document.getElementById(id);
            if (el) el.scrollIntoView({ behavior: 'smooth' });
            // 收起手機選單
            const nav = document.getElementById('mainNav');
            if (nav.classList.contains('show')) {
                bootstrap.Collapse.getInstance(nav).hide();
            }
        },

        scrollToCart() {
            document.getElementById('cart-section').scrollIntoView({ behavior: 'smooth' });
        },

        closeNavbar() {
            const nav = document.getElementById('mainNav');
            // 如果漢堡選單是展開的狀態，就把它收起來
            if (nav && nav.classList.contains('show')) {
                bootstrap.Collapse.getInstance(nav).hide();
            }
        },

        async submitOrder() {
            if (!this.custInfo.name || !this.custInfo.phone || !this.custInfo.time) {
                return Swal.fire('資料未填', '請填寫完整的預約聯絡資訊', 'warning');
            }

            try {
                const res = await fetch('api/save_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        customer: this.custInfo,
                        items: this.cart
                    })
                });
                const result = await res.json();
                if (result.success) {
                    // 1. 處理電話隱碼邏輯：只顯示後三碼，前面用 * 代替（例如：*******123）
                    const rawPhone = this.custInfo.phone || "";
                    const maskedPhone = rawPhone.length > 3 
                        ? "*".repeat(rawPhone.length - 3) + rawPhone.slice(-3) 
                        : rawPhone;

                    // 2. 組合餐點明細的文字列表（白話條列）
                    let itemsHtml = '<ul class="text-start list-unstyled ps-3 bg-light p-3 rounded-3 border small" style="line-height:1.6; color:#475569;">';
                    this.cart.forEach(item => {
                        itemsHtml += `
                            <li class="mb-1 border-bottom border-white pb-1">
                                🍕 <span class="fw-bold text-dark">${item.name}</span> 
                                <span class="text-danger fw-bold">x${item.qty}</span> 
                                <br><small class="text-muted">(${item.options})</small>
                            </li>`;
                    });
                    itemsHtml += '</ul>';

                    // 3. 彈出超漂亮的完整訂單資訊跳窗
                    Swal.fire({
                        title: '🎉 預約成功！',
                        icon: 'success',
                        html: `
                            <div class="text-start px-2 text-dark">
                                <p class="mb-2 fs-5">謝謝您的訂購！請截圖此畫面至現場取餐：</p>
                                <div class="p-3 mb-3 rounded-3" style="background-color: #fff8f0; border: 1px solid #ffe0b2;">
                                    <div class="mb-1"><strong>📌 取餐單號：</strong> <span class="text-danger fw-bold fs-5">${result.order_no}</span></div>
                                    <div class="mb-1"><strong>👤 客戶姓名：</strong> ${this.custInfo.name}</div>
                                    <div class="mb-1"><strong>📞 聯絡電話：</strong> ${maskedPhone}</div>
                                    <div class="mb-1"><strong>⏰ 取餐時間：</strong> <span class="badge bg-warning text-dark">${this.custInfo.date} ${this.custInfo.time}</span></div>
                                </div>
                                <p class="mb-2 fw-bold"><i class="bi bi-list-check me-1 text-warning"></i>訂單內容明細：</p>
                                ${itemsHtml}
                                <p class="text-end text-danger fw-bold fs-5 mt-2">總計金額：$${this.cartTotal} 元</p>
                            </div>
                        `,
                        confirmButtonText: '確定並關閉',
                        confirmButtonColor: '#ff9800', // 使用王媽媽經典暖橘色按鈕
                        allowOutsideClick: false // 防止客人手滑點到旁邊關掉
                    }).then(() => {
                        location.reload(); // 點擊確定後刷新網頁清空購物車
                    });
                }
            } catch (err) {
                Swal.fire('系統錯誤', '請稍後再試', 'error');
            }
        },

        async searchOrders() {
            if (!this.searchQuery.name || !this.searchQuery.phone) return;
            this.isSearching = true;
            try {
                const res = await fetch(`api/get_my_orders.php?name=${encodeURIComponent(this.searchQuery.name)}&phone=${encodeURIComponent(this.searchQuery.phone)}`);
                const data = await res.json();
                this.searchResults = data.orders || [];
                this.hasSearched = true;
            } catch (err) {
                Swal.fire('查詢失敗', '連線異常', 'error');
            } finally {
                this.isSearching = false;
            }
        }
    },
    mounted() {
        this.fetchProducts();
        this.initTimeOptions();

        // 🌟 新增：獲取本地時間的今天日期 (格式必須為 YYYY-MM-DD 才能被 HTML5 認得)
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0'); // 月份從0開始算要+1
        const dd = String(today.getDate()).padStart(2, '0');
        
        this.todayDate = `${yyyy}-${mm}-${dd}`; // 組合出 "2026-05-22"
        
        // 自動幫客人的預約日期預設為今天，體驗更好
        this.custInfo.date = this.todayDate;
        
        // 新增：點擊網頁空白處，自動收起漢堡選單
        document.addEventListener('click', (e) => {
            const nav = document.getElementById('mainNav');
            const toggler = document.querySelector('.navbar-toggler');
            
            // 確保元素存在，且如果選單是開啟的，但點擊的地方「不是」選單內部，也「不是」漢堡按鈕本身
            if (nav && toggler && nav.classList.contains('show') && !nav.contains(e.target) && !toggler.contains(e.target)) {
                this.closeNavbar();
            }
        });
    }
}).mount('#app');