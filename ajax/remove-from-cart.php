<?php
session_start();
header('Content-Type: application/json');

if (isset($_POST['key']) && isset($_SESSION['cart'])) {
    $key = $_POST['key'];
    
    if (isset($_SESSION['cart'][$key])) {
        unset($_SESSION['cart'][$key]);
        // Reindex array to maintain sequential keys
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        
        echo json_encode([
            'success' => true,
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