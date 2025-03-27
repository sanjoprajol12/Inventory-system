<?php
require_once 'config.php';

if (isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    $result = $conn->query("SELECT price FROM products WHERE id = $product_id");
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        echo json_encode(['unit_price' => $product['price']]);
    } else {
        echo json_encode(['unit_price' => 0]);
    }
}
?> 