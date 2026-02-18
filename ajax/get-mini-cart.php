<?php
session_start();
include_once "../config/connect.php";

$cart_items = [];
$total = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $key => $item) {
        $product_id = intval($item['product_id']);
        $variant_id = $item['variant_id'] ? intval($item['variant_id']) : null;
        
        $sql = "SELECT p.pro_id, p.pro_name, p.pro_img, p.selling_price,
                       pv.price as variant_price, pv.color, pv.size
                FROM products p
                LEFT JOIN product_variants pv ON pv.id = $variant_id
                WHERE p.pro_id = $product_id";
        $result = mysqli_query($conn, $sql);
        
        if ($product = mysqli_fetch_assoc($result)) {
            $price = $variant_id ? $product['variant_price'] : $product['selling_price'];
            $subtotal = $price * $item['quantity'];
            $total += $subtotal;
            
            $cart_items[] = [
                'name' => $product['pro_name'],
                'price' => $price,
                'quantity' => $item['quantity'],
                'image' => $product['pro_img'],
                'subtotal' => $subtotal
            ];
        }
    }
}

echo json_encode([
    'items' => $cart_items,
    'total' => number_format($total, 2)
]);
?>