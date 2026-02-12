<?php
// This must be the VERY FIRST LINE in the file
session_start();

include_once "../config/connect.php";

// Set header for JSON response
header('Content-Type: application/json');

// Debug logging (remove in production)
error_log("Add to cart request received: " . print_r($_POST, true));

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch($action) {
        case 'add_to_cart':
            $product_id = intval($_POST['product_id']);
            $quantity = intval($_POST['quantity'] ?? 1);
            $color = isset($_POST['color']) ? trim($_POST['color']) : null;
            $size = isset($_POST['size']) ? trim($_POST['size']) : null;
            $variant_id = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : null;
            $stock = 10;
            $price = isset($_POST['selling_price']) ? floatval($_POST['selling_price']) : 0;
            
            // Validate required fields
            if($product_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product']);
                exit();
            }
            
            // Check if product exists and is active
            $sql = "SELECT p.*, c.categories, c.slug_url 
                    FROM products p 
                    LEFT JOIN categories c ON p.pro_sub_cate = c.id 
                    WHERE p.pro_id = ? AND p.status = 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($result->num_rows == 0) {
                echo json_encode(['success' => false, 'message' => 'Product not found or inactive']);
                exit();
            }
            
            $product = $result->fetch_assoc();
            
            // Check for variants
            $has_variants = false;
            $variant = null;
            
            if($variant_id) {
                // Get variant by ID
                $variant_sql = "SELECT * FROM product_variants WHERE id = ? AND product_id = ?";
                $variant_stmt = $conn->prepare($variant_sql);
                $variant_stmt->bind_param("ii", $variant_id, $product_id);
                $variant_stmt->execute();
                $variant_result = $variant_stmt->get_result();
                
                if($variant_result->num_rows > 0) {
                    $variant = $variant_result->fetch_assoc();
                    $has_variants = true;
                }
            } elseif($color || $size) {
                // Get variant by color/size
                $variant_sql = "SELECT * FROM product_variants WHERE product_id = ?";
                $params = array($product_id);
                $types = "i";
                
                if($color) {
                    $variant_sql .= " AND color = ?";
                    $params[] = $color;
                    $types .= "s";
                }
                if($size) {
                    $variant_sql .= " AND size = ?";
                    $params[] = $size;
                    $types .= "s";
                }
                
                $variant_stmt = $conn->prepare($variant_sql);
                $variant_stmt->bind_param($types, ...$params);
                $variant_stmt->execute();
                $variant_result = $variant_stmt->get_result();
                
                if($variant_result->num_rows > 0) {
                    $variant = $variant_result->fetch_assoc();
                    $has_variants = true;
                    $variant_id = $variant['id'];
                }
            }
            
            // Check stock
           
            
            // Initialize cart if not exists
            if(!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            // Create cart item ID
            $cart_item_id = $product_id . ($variant_id ? '_' . $variant_id : '');
            
            // Check if item already exists in cart
            $existing_quantity = isset($_SESSION['cart'][$cart_item_id]) ? $_SESSION['cart'][$cart_item_id]['quantity'] : 0;
            
            // Check if total quantity exceeds stock
            if(($existing_quantity + $quantity) > $stock) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Cannot add more items. Only ' . ($stock - $existing_quantity) . ' more available'
                ]);
                exit();
            }
            
            // Add/Update cart item
            if(isset($_SESSION['cart'][$cart_item_id])) {
                $_SESSION['cart'][$cart_item_id]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$cart_item_id] = [
                    'product_id' => $product_id,
                    'variant_id' => $variant_id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'name' => $product['pro_name'],
                    'image' => $product['pro_img'],
                    'color' => $color,
                    'size' => $size,
                    'sku' => $product['sku'],
                    'category_name' => $product['categories'],
                    'category_slug' => $product['slug_url'],
                    'added_at' => time()
                ];
            }
            
            // Calculate total cart count
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
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>