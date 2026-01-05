<?php
session_start();
include_once "../config/connect.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity'] ?? 1);
    $variant_id = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : null;
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $cart_item_key = $variant_id ? "{$product_id}_{$variant_id}" : $product_id;
    
    // Check if product exists and is in stock
    if ($variant_id) {
        // With variant
        $sql = "SELECT p.*, pv.quantity as variant_qty, pv.price as variant_price
                FROM products p
                LEFT JOIN product_variants pv ON pv.id = $variant_id AND pv.product_id = p.pro_id
                WHERE p.pro_id = $product_id AND p.status = 1";
    } else {
        // Without variant
        $sql = "SELECT p.*, p.qty as variant_qty
                FROM products p
                WHERE p.pro_id = $product_id AND p.status = 1";
    }
    
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $product = mysqli_fetch_assoc($result);
        
        if ($variant_id) {
            // Check if variant exists
            if (!$product['variant_qty'] && !$product['variant_price']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Product variant not found.'
                ]);
                exit;
            }
            
            $available_qty = $product['variant_qty'];
        } else {
            $available_qty = $product['qty'];
        }
        
        if ($available_qty >= $quantity) {
            if (isset($_SESSION['cart'][$cart_item_key])) {
                $_SESSION['cart'][$cart_item_key]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$cart_item_key] = [
                    'product_id' => $product_id,
                    'variant_id' => $variant_id,
                    'quantity' => $quantity
                ];
            }
            
            $cart_count = count($_SESSION['cart']);
            
            echo json_encode([
                'success' => true,
                'cart_count' => $cart_count,
                'message' => 'Product added to cart successfully!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Insufficient stock available.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Product not found.'
        ]);
    }
}
?>