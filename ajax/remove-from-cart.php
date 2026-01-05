<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cart_key'])) {
    $cart_key = $_POST['cart_key'];
    
    if (isset($_SESSION['cart'][$cart_key])) {
        unset($_SESSION['cart'][$cart_key]);
        
        $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
        
        echo json_encode([
            'success' => true,
            'cart_count' => $cart_count,
            'message' => 'Item removed from cart'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Item not found in cart'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>