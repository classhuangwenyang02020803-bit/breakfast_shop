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
                options = `${p.selectedEgg} / ${p.selectedSpicy}`;
            } else if (p.price_m) { // 判斷是否為飲料
                if (this.needsSugar(p.name)) {
                    options = `${p.selectedSize} / ${p.selectedSugar}`;
                } else {
                    options = `${p.selectedSize}`; // 不需要甜度時，只顯示大小杯
                }
            } else {
                options = p.selectedSpicy;
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
                    Swal.fire('🎉 預約成功！', `您的單號：${result.order_no}`, 'success').then(() => {
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