<?php
session_start();
include_once __DIR__ . '/../config/connect.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'buy_now') {
        // Get product details
        $product_id = intval($_POST['product_id']);
        $variant_id = intval($_POST['variant_id']);
        $size = $_POST['size'] ?? '';
        $color = $_POST['color'] ?? '';
        $quantity = intval($_POST['quantity']);
        $price = floatval($_POST['price']);
        $product_name = $_POST['product_name'] ?? '';
        
        // Validate quantity
        if ($quantity < 1) {
            $quantity = 1;
        }
        
        // Create buy now session data
        $buyNowItem = [
            'product_id' => $product_id,
            'variant_id' => $variant_id,
            'size' => $size,
            'color' => $color,
            'quantity' => $quantity,
            'price' => $price,
            'product_name' => $product_name,
            'timestamp' => time()
        ];
        
        // Store in session (separate from regular cart)
        $_SESSION['buy_now'] = $buyNowItem;
        
        // Optional: Clear any previous buy now items
        if (isset($_SESSION['buy_now_previous'])) {
            unset($_SESSION['buy_now_previous']);
        }
        
        $response['success'] = true;
        $response['message'] = 'Buy Now item added successfully';
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>