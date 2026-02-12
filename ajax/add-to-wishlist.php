<?php
session_start();
include_once "../config/connect.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'])) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Please login to add items to wishlist'
        ]);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);
    
    // Check if product exists
    $check_product = mysqli_query($conn, "SELECT * FROM products WHERE pro_id = $product_id AND status = 1");
    
    if (mysqli_num_rows($check_product) == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Product not found'
        ]);
        exit;
    }
    
    // Check if already in wishlist
    $check_wishlist = mysqli_query($conn, "SELECT * FROM wishlist WHERE user_id = $user_id AND product_id = $product_id");
    
    if (mysqli_num_rows($check_wishlist) == 0) {
        $sql = "INSERT INTO wishlist (user_id, product_id, created_at) 
                VALUES ($user_id, $product_id, NOW())";
        
        if (mysqli_query($conn, $sql)) {
            echo json_encode([
                'success' => true,
                'message' => 'Product added to wishlist!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error adding to wishlist'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Product already in wishlist'
        ]);
    }
}
?>