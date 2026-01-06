<?php
session_start();
include_once "../config/connect.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'])) {
    try {
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity'] ?? 1);
        $variant_id = isset($_POST['variant_id']) && !empty($_POST['variant_id']) ? intval($_POST['variant_id']) : null;
        
        // Validate quantity
        if ($quantity < 1) {
            throw new Exception('Quantity must be at least 1');
        }
        
        // Initialize cart if not exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Generate cart item key
        $cart_item_key = $variant_id ? "{$product_id}_{$variant_id}" : "{$product_id}_0";
        
        // Check product existence and stock
        $product = null;
        $available_qty = 0;
        $price = 0;
        
        if ($variant_id) {
            // Check variant
            $sql = "SELECT p.*, pv.id as variant_id, pv.quantity as variant_qty, 
                           pv.price as variant_price, pv.color, pv.size
                    FROM products p
                    INNER JOIN product_variants pv ON pv.product_id = p.pro_id
                    WHERE p.pro_id = ? AND pv.id = ? AND p.status = 1 AND pv.status = 1";
            
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $product_id, $variant_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($result)) {
                $product = $row;
                $available_qty = $row['variant_qty'];
                $price = $row['variant_price'];
            } else {
                throw new Exception('Product variant not found or not available.');
            }
        } else {
            // Check main product - first check if product has variants
            $check_variants_sql = "SELECT COUNT(*) as variant_count FROM product_variants 
                                   WHERE product_id = ? AND status = 1";
            $check_stmt = mysqli_prepare($conn, $check_variants_sql);
            mysqli_stmt_bind_param($check_stmt, "i", $product_id);
            mysqli_stmt_execute($check_stmt);
            $variant_result = mysqli_stmt_get_result($check_stmt);
            $variant_data = mysqli_fetch_assoc($variant_result);
            
            if ($variant_data['variant_count'] > 0) {
                // Product has variants but no variant selected
                throw new Exception('Please select a variant (color/size) before adding to cart.');
            }
            
            // Check main product
            $sql = "SELECT * FROM products 
                    WHERE pro_id = ? AND status = 1";
            
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($result)) {
                $product = $row;
                $available_qty = $row['qty'];
                $price = $row['selling_price'];
            } else {
                throw new Exception('Product not found or not available.');
            }
        }
        
        // Check stock availability
        if ($available_qty < $quantity) {
            throw new Exception('Insufficient stock. Only ' . $available_qty . ' items available.');
        }
        
        // Add to cart or update quantity
        if (isset($_SESSION['cart'][$cart_item_key])) {
            $new_quantity = $_SESSION['cart'][$cart_item_key]['quantity'] + $quantity;
            
            // Check if new quantity exceeds available stock
            if ($new_quantity > $available_qty) {
                throw new Exception('Cannot add more items. Maximum available stock is ' . $available_qty . '.');
            }
            
            $_SESSION['cart'][$cart_item_key]['quantity'] = $new_quantity;
        } else {
            $_SESSION['cart'][$cart_item_key] = [
                'product_id' => $product_id,
                'variant_id' => $variant_id,
                'quantity' => $quantity,
                'added_at' => time()
            ];
        }
        
        $cart_count = count($_SESSION['cart']);
        
        echo json_encode([
            'success' => true,
            'cart_count' => $cart_count,
            'message' => 'Product added to cart successfully!',
            'cart_item' => [
                'key' => $cart_item_key,
                'product_id' => $product_id,
                'variant_id' => $variant_id,
                'quantity' => $_SESSION['cart'][$cart_item_key]['quantity']
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request. Product ID is required.'
    ]);
}
?>