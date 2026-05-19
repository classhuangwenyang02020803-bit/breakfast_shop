<?php

header('Content-Type: application/json');

require_once 'db.php';

$keyword = '';

if(isset($_GET['keyword'])) {

    $keyword = trim($_GET['keyword']);
}

$stmt = $conn->prepare(
    "SELECT *
    FROM products
    WHERE name LIKE ?
    AND status = 1
    ORDER BY id DESC"
);

$search = "%{$keyword}%";

$stmt->bind_param("s", $search);

$stmt->execute();

$result = $stmt->get_result();

$products = [];

while($row = $result->fetch_assoc()) {

    $products[] = $row;
}

echo json_encode([
    "success" => true,
    "products" => $products
]);
?>