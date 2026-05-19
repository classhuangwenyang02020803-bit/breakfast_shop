<?php

require_once 'auth.php';
require_once '../api/db.php';

$id = intval($_GET['id']);

$stmt = $conn->prepare(
    "DELETE FROM products
    WHERE id=?"
);

$stmt->bind_param("i", $id);

$stmt->execute();

header('Location: products.php');
exit;
?>