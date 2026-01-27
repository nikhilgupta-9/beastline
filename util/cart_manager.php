<?php
class CartManager {
    private $conn;
    private $visitorTracker;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->visitorTracker = new VisitorTracker($conn);
        $this->initializeCart();
    }
    
    private function initializeCart() {
        if (!isset($_SESSION['cart'])) {
            // Try to load from database
            $savedCart = $this->visitorTracker->getCartSession();
            $_SESSION['cart'] = $savedCart ?: [];
        }
    }
    
    public function addToCart($productId, $quantity = 1, $attributes = []) {
        // Validate product
        $product = $this->getProductDetails($productId);
        
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }
        
        // Check stock
        if ($product['stock_quantity'] < $quantity) {
            return ['success' => false, 'message' => 'Insufficient stock'];
        }
        
        // Check quantity limits
        if ($quantity < $product['min_order_qty']) {
            $quantity = $product['min_order_qty'];
        }
        
        if ($quantity > $product['max_order_qty']) {
            return ['success' => false, 'message' => 'Maximum order quantity is ' . $product['max_order_qty']];
        }
        
        $cartItemId = $productId . '_' . md5(serialize($attributes));
        
        if (isset($_SESSION['cart'][$cartItemId])) {
            // Update existing item
            $newQty = $_SESSION['cart'][$cartItemId]['quantity'] + $quantity;
            
            if ($newQty > $product['max_order_qty']) {
                return ['success' => false, 'message' => 'Cannot add more than maximum quantity'];
            }
            
            $_SESSION['cart'][$cartItemId]['quantity'] = $newQty;
        } else {
            // Add new item
            $_SESSION['cart'][$cartItemId] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $product['sale_price'] > 0 ? $product['sale_price'] : $product['price'],
                'attributes' => $attributes,
                'added_at' => time(),
                'product_name' => $product['pro_title'],
                'product_image' => $product['pro_image'],
                'sku' => $product['sku'],
                'stock' => $product['stock_quantity']
            ];
        }
        
        // Save to database
        $this->saveCartToDatabase();
        
        // Check for abandoned cart
        $this->checkAbandonedCart();
        
        return [
            'success' => true,
            'message' => 'Product added to cart',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal()
        ];
    }
    
    public function updateCartItem($cartItemId, $quantity) {
        if (!isset($_SESSION['cart'][$cartItemId])) {
            return ['success' => false, 'message' => 'Item not found in cart'];
        }
        
        $productId = $_SESSION['cart'][$cartItemId]['product_id'];
        $product = $this->getProductDetails($productId);
        
        if ($quantity < $product['min_order_qty']) {
            $quantity = $product['min_order_qty'];
        }
        
        if ($quantity > $product['max_order_qty']) {
            return ['success' => false, 'message' => 'Maximum order quantity is ' . $product['max_order_qty']];
        }
        
        if ($quantity > $product['stock_quantity']) {
            return ['success' => false, 'message' => 'Insufficient stock'];
        }
        
        $_SESSION['cart'][$cartItemId]['quantity'] = $quantity;
        
        // Save to database
        $this->saveCartToDatabase();
        
        return [
            'success' => true,
            'message' => 'Cart updated',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal()
        ];
    }
    
    public function removeFromCart($cartItemId) {
        if (isset($_SESSION['cart'][$cartItemId])) {
            unset($_SESSION['cart'][$cartItemId]);
            
            // Save to database
            $this->saveCartToDatabase();
            
            return [
                'success' => true,
                'message' => 'Item removed from cart',
                'cart_count' => $this->getCartCount(),
                'cart_total' => $this->getCartTotal()
            ];
        }
        
        return ['success' => false, 'message' => 'Item not found in cart'];
    }
    
    public function clearCart() {
        $_SESSION['cart'] = [];
        $this->saveCartToDatabase();
    }
    
    private function saveCartToDatabase() {
        $this->visitorTracker->saveCartSession($_SESSION['cart']);
        
        // Also save to cart_items table if user is logged in
        if (isset($_SESSION['user_id'])) {
            $this->saveCartItemsToDatabase();
        }
    }
    
    private function saveCartItemsToDatabase() {
        $userId = $_SESSION['user_id'];
        
        // Clear existing cart items for this user
        $sql = "DELETE FROM cart_items WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        // Insert new cart items
        foreach ($_SESSION['cart'] as $item) {
            $sql = "INSERT INTO cart_items (user_id, product_id, quantity, price, attributes) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $attributesJson = json_encode($item['attributes']);
            $stmt->bind_param(
                "iiids",
                $userId,
                $item['product_id'],
                $item['quantity'],
                $item['price'],
                $attributesJson
            );
            $stmt->execute();
        }
    }
    
    public function getCartCount() {
        $count = 0;
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $count += $item['quantity'];
            }
        }
        return $count;
    }
    
    public function getCartTotal() {
        $total = 0;
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $total += $item['price'] * $item['quantity'];
            }
        }
        return $total;
    }
    
    public function getCartItems() {
        return isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
    }
    
    private function getProductDetails($productId) {
        $sql = "SELECT p.*, 
                       COALESCE(NULLIF(p.sale_price, 0), p.price) as final_price,
                       c.categories as category_name,
                       b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.pro_cate = c.id
                LEFT JOIN pro_brands b ON p.brand_name = b.id
                WHERE p.pro_id = ? AND p.status = 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    private function checkAbandonedCart() {
        $cartCount = $this->getCartCount();
        
        if ($cartCount > 0) {
            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $visitorId = $this->visitorTracker->getVisitorId();
            $cartTotal = $this->getCartTotal();
            $cartData = json_encode($_SESSION['cart']);
            
            $sql = "INSERT INTO abandoned_carts 
                    (visitor_id, user_id, cart_data, total_items, total_value)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    cart_data = VALUES(cart_data),
                    total_items = VALUES(total_items),
                    total_value = VALUES(total_value),
                    updated_at = NOW(),
                    status = 'active'";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                "sisid",
                $visitorId,
                $userId,
                $cartData,
                $cartCount,
                $cartTotal
            );
            $stmt->execute();
        }
    }
    
    public function migrateGuestCartToUser($userId) {
        // Load guest cart
        $guestCart = $this->visitorTracker->getCartSession();
        
        if (!empty($guestCart)) {
            // Merge with existing user cart if any
            $userCart = $_SESSION['cart'] ?? [];
            
            foreach ($guestCart as $key => $item) {
                if (isset($userCart[$key])) {
                    // Merge quantities for same item
                    $userCart[$key]['quantity'] += $item['quantity'];
                } else {
                    $userCart[$key] = $item;
                }
            }
            
            $_SESSION['cart'] = $userCart;
            $this->saveCartToDatabase();
            
            // Link visitor to user
            $this->visitorTracker->linkVisitorToUser($userId);
            
            // Update abandoned carts
            $sql = "UPDATE abandoned_carts SET user_id = ? WHERE visitor_id = ?";
            $stmt = $this->conn->prepare($sql);
            $visitorId = $this->visitorTracker->getVisitorId();
            $stmt->bind_param("is", $userId, $visitorId);
            $stmt->execute();
        }
    }
}
?>