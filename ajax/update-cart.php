<?php
session_start();
include_once "../config/connect.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'update_quantity':
            $cart_item_id = $_POST['cart_item_id'] ?? '';
            $quantity = intval($_POST['quantity'] ?? 1);
            
            if (empty($cart_item_id) || !isset($_SESSION['cart'][$cart_item_id])) {
                echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
                exit();
            }
            
            // Get product/variant details for stock check
            $cart_item = $_SESSION['cart'][$cart_item_id];
            $product_id = $cart_item['product_id'];
            $variant_id = $cart_item['variant_id'];
            
            // Check stock availability
            $max_stock = 10; // Default
            
            if ($variant_id) {
                $sql = "SELECT quantity FROM product_variants WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $variant_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $variant = $result->fetch_assoc();
                    $max_stock = $variant['quantity'];
                }
            } else {
                $sql = "SELECT quantity FROM products WHERE pro_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $product = $result->fetch_assoc();
                    $max_stock = $product['quantity'];
                }
            }
            
            if ($quantity < 1) {
                $quantity = 1;
            }
            
            if ($quantity > $max_stock) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Only ' . $max_stock . ' items available in stock'
                ]);
                exit();
            }
            
            // Update quantity
            $_SESSION['cart'][$cart_item_id]['quantity'] = $quantity;
            
            // Calculate updated item total
            $item_price = $_SESSION['cart'][$cart_item_id]['price'];
            $item_total = $item_price * $quantity;
            
            // Calculate cart totals
            $cart_count = 0;
            $subtotal = 0;
            
            foreach ($_SESSION['cart'] as $item) {
                $cart_count += $item['quantity'];
                $subtotal += $item['price'] * $item['quantity'];
            }
            
            echo json_encode([
                'success' => true,
                'cart_count' => $cart_count,
                'item_total' => number_format($item_total, 2),
                'subtotal' => number_format($subtotal, 2)
            ]);
            break;
            
        case 'apply_coupon':
            $coupon_code = trim($_POST['coupon_code'] ?? '');
            
            // Simple coupon validation (customize as needed)
            $valid_coupons = [
                'WELCOME15' => 15,  // 15% off
                'SAVE10' => 10,      // 10% off
                'FREESHIP' => 'free_shipping' // Free shipping
            ];
            
            if (!empty($coupon_code) && isset($valid_coupons[$coupon_code])) {
                $_SESSION['promotion_code'] = $coupon_code;
                echo json_encode(['success' => true, 'message' => 'Coupon applied successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid coupon code']);
            }
            break;
            
        case 'update_cart':
            // Handle bulk update from form
            if (isset($_POST['quantity']) && is_array($_POST['quantity'])) {
                $updated = false;
                
                foreach ($_POST['quantity'] as $cart_item_id => $quantity) {
                    $quantity = intval($quantity);
                    
                    if (isset($_SESSION['cart'][$cart_item_id]) && $quantity > 0) {
                        // Check stock
                        $cart_item = $_SESSION['cart'][$cart_item_id];
                        $product_id = $cart_item['product_id'];
                        $variant_id = $cart_item['variant_id'];
                        
                        $max_stock = 10; // Default
                        
                        if ($variant_id) {
                            $sql = "SELECT quantity FROM product_variants WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $variant_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                $variant = $result->fetch_assoc();
                                $max_stock = $variant['quantity'];
                            }
                        } else {
                            $sql = "SELECT quantity FROM products WHERE pro_id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $product_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                $product = $result->fetch_assoc();
                                $max_stock = $product['quantity'];
                            }
                        }
                        
                        if ($quantity > $max_stock) {
                            $quantity = $max_stock;
                        }
                        
                        $_SESSION['cart'][$cart_item_id]['quantity'] = $quantity;
                        $updated = true;
                    }
                }
                
                if ($updated) {
                    echo json_encode(['success' => true, 'message' => 'Cart updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No items to update']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'No quantities provided']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>