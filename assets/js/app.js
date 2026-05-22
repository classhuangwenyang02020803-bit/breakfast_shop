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

        // 🌟 【邏輯大腦修正】：精準控制哪些飲料才需要選糖度
        needsSugar(name) {
            // 指定只有這些需要調配糖度的關鍵字，才會回傳 true
            const sweetDrinks = ['紅茶', '豆漿']; 
            
            // 只要飲料名字包含上面任何一個關鍵字，就會顯示糖度選單
            return sweetDrinks.some(k => name.includes(k));
        },

        // 判斷哪些餐點需要挑選辣度
        needsSpicy(name) {
            return !['皮蛋瘦肉粥', '甜燒餅', '荷包蛋(顆)'].some(k => name.includes(k));
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
                options = this.needsSpicy(p.name) ? `${p.selectedEgg} / ${p.selectedSpicy}` : p.selectedEgg;
            } else if (p.price_m || this.filteredProducts('drink').some(d => d.name === p.name)) { 
                // 飲料部分的邏輯：根據最新的 needsSugar 判斷是否要組合糖度文字
                options = this.needsSugar(p.name) ? `${p.selectedSize} / ${p.selectedSugar}` : p.selectedSize;
            } else {
                options = this.needsSpicy(p.name) ? p.selectedSpicy : "";
            }

            this.cart.push({
                id: p.id,
                name: p.name,
                price: currentPrice,
                qty: p.qty,
                subtotal: p.qty * currentPrice,
                options: options,
                note: p.note
            });

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
                    const rawPhone = this.custInfo.phone || "";
                    const maskedPhone = rawPhone.length > 3 
                        ? "*".repeat(rawPhone.length - 3) + rawPhone.slice(-3) 
                        : rawPhone;

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
                        confirmButtonColor: '#ff9800',
                        allowOutsideClick: false
                    }).then(() => {
                        location.reload();
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

        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        
        this.todayDate = `${yyyy}-${mm}-${dd}`;
        this.custInfo.date = this.todayDate;
        
        document.addEventListener('click', (e) => {
            const nav = document.getElementById('mainNav');
            const toggler = document.querySelector('.navbar-toggler');
            if (nav && toggler && nav.classList.contains('show') && !nav.contains(e.target) && !toggler.contains(e.target)) {
                this.closeNavbar();
            }
        });
    }
}).mount('#app');