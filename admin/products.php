<?php
require_once 'auth.php';
require_once '../api/db.php';

$result = $conn->query(
    "SELECT * FROM products ORDER BY id DESC"
);
?>

<!DOCTYPE html>
<html lang="zh-tw">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>商品管理</title>

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    >

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body class="bg-light">
<?php include 'navbar.php'; ?>
<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h2 class="fw-bold">
            商品管理
        </h2>

        <div>

            <a
                href="dashboard.php"
                class="btn btn-secondary"
            >
                返回後台
            </a>

            <a
                href="product_create.php"
                class="btn btn-success"
            >
                新增商品
            </a>

        </div>

    </div>

    <div class="card shadow border-0">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered align-middle">

                    <thead class="table-dark">

                        <tr>

                            <th>ID</th>

                            <th>圖片</th>

                            <th>商品名稱</th>

                            <th>價格</th>

                            <th>狀態</th>

                            <th width="220">
                                操作
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php while($row = $result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php echo $row['id']; ?>
                            </td>

                            <td>

                                <img
                                    src="../<?php echo $row['image']; ?>"
                                    width="80"
                                    class="rounded"
                                >

                            </td>

                            <td>
                                <?php echo $row['name']; ?>
                            </td>

                            <td class="text-danger fw-bold">
                                $<?php echo $row['price']; ?>
                            </td>

                            <td>

                                <?php if($row['status'] == 1): ?>

                                    <span class="badge bg-success">
                                        上架中
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary">
                                        已下架
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <a
                                    href="product_update.php?id=<?php echo $row['id']; ?>"
                                    class="btn btn-primary btn-sm"
                                >
                                    編輯
                                </a>

                                <a
                                    href="product_delete.php?id=<?php echo $row['id']; ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('確定刪除商品？')"
                                >
                                    刪除
                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
