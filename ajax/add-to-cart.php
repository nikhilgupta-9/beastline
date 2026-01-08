<?php
session_start();
include_once "../config/connect.php";
include_once "../util/cart_manager.php"; // If you have cart manager

// Initialize cart if not exists
if(!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch($action) {
        case 'add_to_cart':
            $product_id = intval($_POST['product_id']);
            $quantity = intval($_POST['quantity'] ?? 1);
            $variants = $_POST['variants'] ?? [];
            
            // Check if product exists and is active
            $sql = "SELECT p.* FROM products p WHERE p.pro_id = ? AND p.status = 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($result->num_rows == 0) {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                exit();
            }
            
            $product = $result->fetch_assoc();
            
            // Check if product has variants
            $variant_sql = "SELECT COUNT(*) as variant_count FROM product_variants WHERE product_id = ?";
            $variant_stmt = $conn->prepare($variant_sql);
            $variant_stmt->bind_param("i", $product_id);
            $variant_stmt->execute();
            $variant_result = $variant_stmt->get_result();
            $variant_count = $variant_result->fetch_assoc()['variant_count'];
            
            // If product has variants but none selected
            if($variant_count > 0 && empty($variants)) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Please select a variant (color/size) before adding to cart.'
                ]);
                exit();
            }
            
            // If variants selected, get variant details
            $variant_id = null;
            $final_price = $product['selling_price'];
            
            if(!empty($variants)) {
                $variant_query = "SELECT * FROM product_variants WHERE product_id = ?";
                
                if(isset($variants['color'])) {
                    $variant_query .= " AND color = '" . mysqli_real_escape_string($conn, $variants['color']) . "'";
                }
                if(isset($variants['size'])) {
                    $variant_query .= " AND size = '" . mysqli_real_escape_string($conn, $variants['size']) . "'";
                }
                if(isset($variants['variant'])) {
                    $variant_query .= " AND id = " . intval($variants['variant']);
                }
                
                $variant_stmt = $conn->prepare($variant_query);
                $variant_stmt->bind_param("i", $product_id);
                $variant_stmt->execute();
                $variant_result = $variant_stmt->get_result();
                
                if($variant_result->num_rows > 0) {
                    $variant = $variant_result->fetch_assoc();
                    $variant_id = $variant['id'];
                    $final_price = $variant['price'] > 0 ? $variant['price'] : $final_price;
                    
                    // Check variant stock
                    if($variant['stock'] < $quantity) {
                        echo json_encode(['success' => false, 'message' => 'Insufficient stock for selected variant']);
                        exit();
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Selected variant not available']);
                    exit();
                }
            } else {
                // Check main product stock
                if($product['stock'] < $quantity) {
                    echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
                    exit();
                }
            }
            
            // Create cart item ID (include variant if exists)
            $cart_item_id = $product_id . ($variant_id ? '_' . $variant_id : '');
            
            // Add to cart
            if(isset($_SESSION['cart'][$cart_item_id])) {
                // Update quantity
                $_SESSION['cart'][$cart_item_id]['quantity'] += $quantity;
            } else {
                // Add new item
                $_SESSION['cart'][$cart_item_id] = [
                    'product_id' => $product_id,
                    'variant_id' => $variant_id,
                    'quantity' => $quantity,
                    'price' => $final_price,
                    'name' => $product['pro_name'],
                    'image' => $product['pro_img'],
                    'variants' => $variants,
                    'added_at' => time()
                ];
            }
            
            // Calculate cart count
            $cart_count = 0;
            foreach($_SESSION['cart'] as $item) {
                $cart_count += $item['quantity'];
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Product added to cart successfully!',
                'cart_count' => $cart_count
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
?>