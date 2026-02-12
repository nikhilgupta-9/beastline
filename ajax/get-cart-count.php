<?php
session_start();

// Initialize cart count
$cart_count = 0;

if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = count($_SESSION['cart']);
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode(['count' => $cart_count]);
?>