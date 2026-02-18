<?php
session_start();
include_once "../config/connect.php";

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch($action) {
        case 'update_quantity':
            if(isset($_POST['cart_item_id']) && isset($_POST['quantity'])) {
                $cart_item_id = $_POST['cart_item_id'];
                $quantity = intval($_POST['quantity']);
                
                if(isset($_SESSION['cart'][$cart_item_id]) && $quantity > 0) {
                    $_SESSION['cart'][$cart_item_id]['quantity'] = $quantity;
                    
                    // Recalculate cart count
                    $cart_count = 0;
                    foreach($_SESSION['cart'] as $item) {
                        $cart_count += $item['quantity'];
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Quantity updated',
                        'cart_count' => $cart_count
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid item or quantity']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            }
            break;
            
        case 'remove_item':
            if(isset($_POST['cart_item_id'])) {
                $cart_item_id = $_POST['cart_item_id'];
                
                if(isset($_SESSION['cart'][$cart_item_id])) {
                    unset($_SESSION['cart'][$cart_item_id]);
                    
                    // Recalculate cart count
                    $cart_count = 0;
                    foreach($_SESSION['cart'] as $item) {
                        $cart_count += $item['quantity'];
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Item removed from cart',
                        'cart_count' => $cart_count
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing cart item ID']);
            }
            break;
            
        case 'apply_coupon':
            if(isset($_POST['coupon_code']) && !empty($_POST['coupon_code'])) {
                $coupon_code = trim($_POST['coupon_code']);
                
                // Validate coupon code (you can implement your own coupon logic)
                // For now, we'll accept any non-empty code as valid
                if($coupon_code === 'WEEKEND15') {
                    $_SESSION['promotion_code'] = $coupon_code;
                    echo json_encode(['success' => true, 'message' => 'Coupon applied successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid coupon code']);
                }
            } else {
                // Remove coupon if empty
                unset($_SESSION['promotion_code']);
                echo json_encode(['success' => true, 'message' => 'Coupon removed']);
            }
            break;
            
        case 'update_cart':
            if(isset($_POST['quantity']) && is_array($_POST['quantity'])) {
                foreach($_POST['quantity'] as $cart_item_id => $quantity) {
                    $quantity = intval($quantity);
                    if(isset($_SESSION['cart'][$cart_item_id]) && $quantity > 0) {
                        $_SESSION['cart'][$cart_item_id]['quantity'] = $quantity;
                    }
                }
                
                echo json_encode(['success' => true, 'message' => 'Cart updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No items to update']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>