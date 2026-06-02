# 👑 王媽媽早餐店 — 大數據智慧型點餐與經營分析系統

> **技術核心：** PHP 8.x + MySQL 8.0 + Bootstrap 5 + Chart.js
> **系統定位：** 專為傳統餐飲業打造的商業智慧（BI）決策與客戶關係管理（CRM）後台系統
> **開發模式：** 個人獨立全端開發（Independent Full-Stack Development）

---

# 📖 專案簡介

王媽媽早餐店智慧管理系統是一套結合：

* 線上點餐系統（Ordering System）
* 訂單管理系統（Order Management）
* 商業智慧分析（Business Intelligence）
* 客戶關係管理（CRM）

的完整餐飲資訊系統。

系統透過真實餐飲場景模擬與大量測試資料分析，協助店家掌握：

* 熱銷商品
* 顧客消費習慣
* 尖峰時段分析
* 熟客偏好追蹤
* 經營決策依據

實現傳統早餐店數位轉型。

---

# 📂 專案目錄結構（Project Structure）

```text
breakfast_shop/
│
├── api/
│   └── db.php
│
├── assets/
│
├── css/
│
├── index.php
│
└── admin/
    │
    ├── auth.php
    ├── navbar.php
    ├── dashboard.php
    │
    ├── orders.php
    ├── update_order.php
    │
    ├── analytics.php
    └── seed_data.php
```

---

## 📌 目錄說明

| 目錄 / 檔案          | 功能說明          |
| ---------------- | ------------- |
| api/db.php       | MySQL 資料庫連線模組 |
| assets/          | 共用靜態資源        |
| css/             | 前台樣式表         |
| index.php        | 顧客線上點餐系統      |
| auth.php         | 管理員權限驗證       |
| dashboard.php    | 後台總覽儀表板       |
| orders.php       | 訂單管理中心        |
| update_order.php | 訂單狀態更新模組      |
| analytics.php    | BI 大數據分析系統    |
| seed_data.php    | 測試資料產生器       |

---

# 🗺️ 系統架構圖（System Architecture）

```text
+-----------------------------------------------------------------------------------+
|                            王媽媽早餐店智慧管理系統                                |
+-----------------------------------------------------------------------------------+
                                         |
    +--------------------+---------------+---------------+--------------------+
    |                    |                               |                    |
+-------+            +-------+                       +-------+            +-------+
| 顧客點 |            | 權限驗 |                       | 訂單管 |            | 數據戰 |
| 餐系統 |            | 證系統 |                       | 理系統 |            | 情系統 |
+-------+            +-------+                       +-------+            +-------+

(前台)              (安全防護)                     (CRUD)              (BI / CRM)

• 菜單瀏覽          • Bcrypt 雜湊密碼             • 訂單查詢           • 銷量分析
• 預約時間          • Session 驗證               • 狀態更新           • 80/20 法則
• 建立訂單          • ACL 權限隔離               • 分頁管理           • 顧客偏好分析
• 寫入資料庫        • 路由攔截保護               • 歷史稽核           • 尖峰時段分析
```

---

# 🔄 資料流程圖（Data Flow Diagram）

```text
[ 顧客前台 ]
      │
      │ 點餐送出
      ▼
[ order_master ]
[ order_detail ]
      │
      ▼
[ MySQL Data Warehouse ]
      │
      ├──────────────────────────┐
      │                          │
      ▼                          ▼

[ 訂單管理系統 ]             [ 經營分析系統 ]

SELECT + LIMIT             GROUP BY + SUM

      │                          │
      ▼                          ▼

狀態更新                  圖表分析

      │                          │
      ▼                          ▼

UPDATE STATUS            CRM 決策依據
```

---

# ⚙️ 核心功能模組

## 1️⃣ 顧客點餐系統（Front-End Ordering）

### 功能

* 商品瀏覽
* 加入購物車
* 預約取餐時間
* 訂單送出

### 技術特色

* 前端表單驗證
* 自動生成商用訂單編號
* 防止空值提交

### 訂單格式

```text
BK202605010001
BK202605010002
BK202605010003
```

---

## 2️⃣ 訂單管理系統（Order Management）

### 功能

* 訂單查詢
* 訂單狀態更新
* 歷史紀錄追蹤
* 分頁瀏覽

### 訂單生命週期

```text
待處理
  ↓
已完成

或

待處理
  ↓
已取消
```

### 效能優化

採用 Server-Side Pagination：

```sql
SELECT *
FROM order_master
ORDER BY id DESC
LIMIT 20 OFFSET 0;
```

有效避免大量資料導致：

* DOM 過載
* 瀏覽器卡頓
* 記憶體暴增

---

## 3️⃣ 商業智慧分析系統（Business Intelligence）

### 功能

* 每日銷量分析
* 商品銷售排行
* 熟客分析
* 尖峰時段分析

### 技術工具

* Chart.js
* MySQL Aggregate Functions
* PHP Data Processing

### 分析圖表

#### 商品銷售占比

```sql
GROUP BY product_name
```

#### 每日營收趨勢

```sql
GROUP BY DATE(created_at)
```

#### 熟客偏好分析

```sql
GROUP BY customer_id
```

---

## 4️⃣ CRM 客戶關係管理系統

### 功能

* 熟客識別
* 購買偏好分析
* 回購率觀察
* 消費模式追蹤

### 商業應用

例如：

* 套餐推薦
* 優惠券推送
* 精準行銷
* 會員活動規劃

---

# 🧠 大數據模擬引擎

## seed_data.php

建立大量真實模擬資料：

```text
5000+
訂單資料
```

---

## 採用 80/20 法則

```text
20% 熱門商品
↓
貢獻 80% 銷售額
```

模擬真實市場行為：

* 熱門品項
* 冷門品項
* 熟客回購
* 新客探索

---

## 15 分鐘商業時鐘演算法

將時間資料降維分析：

```text
06:00
06:15
06:30
06:45
07:00
...
```

可快速找出：

* 早餐尖峰
* 通勤人潮
* 離峰時段

---

# 🛠️ CRUD 功能矩陣

| 模組               | 資料表          | Create | Read | Update | Delete |
| ---------------- | ------------ | ------ | ---- | ------ | ------ |
| index.php        | order_master | ✅      | ❌    | ❌      | ❌      |
| index.php        | order_detail | ✅      | ❌    | ❌      | ❌      |
| orders.php       | order_master | ❌      | ✅    | ❌      | ❌      |
| orders.php       | order_detail | ❌      | ✅    | ❌      | ❌      |
| update_order.php | order_master | ❌      | ❌    | ✅      | ❌      |
| analytics.php    | 全資料表         | ❌      | ✅    | ❌      | ❌      |
| seed_data.php    | 全資料表         | ✅      | ❌    | ❌      | ✅      |

---

# 🔒 系統安全設計

## 密碼保護機制

採用 PHP 官方推薦：

```php
password_hash(
    $password,
    PASSWORD_BCRYPT
);
```

特色：

* 不可逆
* 自動 Salt
* 防彩虹表攻擊
* 防暴力破解

---

## 存取控制（ACL）

資料表隔離：

```text
admins
users
```

權限層級：

```text
訪客
 ↓
會員
 ↓
管理員
```

---

## 路由保護

所有後台頁面必須經過：

```php
auth.php
```

驗證後才能存取：

```text
/admin/*
```

防禦：

* IDOR
* Session Hijacking
* Privilege Escalation

---

# 🚀 Git Flow 開發規範

本專案遵循業界標準 Git Flow。

---

## 分支策略

```text
main
│
├── feature-order-system
├── feature-bi-chart
├── feature-dashboard
└── feature-crm
```

---

## 開發流程

### 建立功能分支

```bash
git checkout -b feature-bi-chart
```

### 開發與測試

```bash
git add .
git commit -m "Add BI chart module"
```

### 推送至 GitHub

```bash
git push origin feature-bi-chart
```

### 發起 Pull Request

```text
feature-bi-chart
↓
Pull Request
↓
Code Review
↓
Squash and Merge
↓
main
```

---

# 📈 專案亮點（Highlights）

✅ PHP + MySQL 全端開發

✅ Bootstrap 響應式介面

✅ Chart.js 商業分析儀表板

✅ Server-Side Pagination 效能優化

✅ ACL 權限隔離架構

✅ Bcrypt 密碼加密機制

✅ 80/20 法則大數據模擬

✅ CRM 客戶行為分析

✅ Git Flow 版本控制流程

✅ 傳統餐飲業數位轉型實戰案例

---

# 👨‍💻 開發者資訊

**Developer:** Yukio Hasegawa

**Role:** Independent Full-Stack Developer

**Tech Stack:**

* PHP 8.x
* MySQL 8.0
* Bootstrap 5
* JavaScript
* Chart.js
* HTML5 / CSS3

---

# 📜 License

This project is developed for educational, portfolio, and restaurant management system demonstration purposes.

Copyright © 2026 All Rights Reserved.
