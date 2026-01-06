<?php
session_start();
include_once "../config/connect.php";

$cart_items = [];
$subtotal = 0;
$item_count = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $key => $item) {
        $product_id = intval($item['product_id']);
        $variant_id = isset($item['variant_id']) ? intval($item['variant_id']) : null;
        $quantity = intval($item['quantity']);
        
        // Get product details
        $sql = "SELECT p.pro_id, p.pro_name, p.pro_img, p.selling_price,
                       pv.price as variant_price, pv.color, pv.size
                FROM products p
                LEFT JOIN product_variants pv ON pv.id = " . ($variant_id ? $variant_id : 'NULL') . "
                WHERE p.pro_id = $product_id AND p.status = 1";
        
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $product = mysqli_fetch_assoc($result);
            
            $price = $variant_id ? $product['variant_price'] : $product['selling_price'];
            $item_subtotal = $price * $quantity;
            $subtotal += $item_subtotal;
            $item_count++;
            
            $cart_items[] = [
                'key' => $key,
                'product_id' => $product_id,
                'name' => $product['pro_name'],
                'image' => $product['pro_img'],
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $item_subtotal,
                'color' => $product['color'] ?? '',
                'size' => $product['size'] ?? ''
            ];
        }
    }
}

// Calculate shipping (example: free shipping over ₹1000)
$shipping = $subtotal >= 1000 ? 0 : 50;
$total = $subtotal + $shipping;

if ($item_count > 0) {
    $content = '';
    foreach ($cart_items as $item) {
        $content .= '<div class="cart_item">';
        $content .= '<div class="cart_img">';
        $content .= '<a href="' . $site . 'product-details/' . $item['product_id'] . '">';
        $content .= '<img src="' . $site . 'admin/assets/img/uploads/' . $item['image'] . '" alt="' . htmlspecialchars($item['name']) . '">';
        $content .= '</a>';
        $content .= '</div>';
        $content .= '<div class="cart_info">';
        $content .= '<a href="' . $site . 'product-details/' . $item['product_id'] . '">' . htmlspecialchars($item['name']) . '</a>';
        if (!empty($item['color'])) {
            $content .= '<small>Color: ' . htmlspecialchars($item['color']) . '</small>';
        }
        if (!empty($item['size'])) {
            $content .= '<small>Size: ' . htmlspecialchars($item['size']) . '</small>';
        }
        $content .= '<p>' . $item['quantity'] . ' x <span>₹' . number_format($item['price'], 2) . '</span></p>';
        $content .= '</div>';
        $content .= '<div class="cart_remove">';
        $content .= '<a href="#" class="remove-cart-item" data-key="' . $item['key'] . '">';
        $content .= '<i class="ion-ios-close-outline"></i>';
        $content .= '</a>';
        $content .= '</div>';
        $content .= '</div>';
    }
} else {
    $content = '<div class="empty-cart text-center py-5">';
    $content .= '<i class="pe-7s-cart" style="font-size: 60px; color: #ddd;"></i>';
    $content .= '<p class="mt-3">Your cart is empty</p>';
    $content .= '<a href="' . $site . 'shop/" class="btn btn-dark mt-2">Continue Shopping</a>';
    $content .= '</div>';
}

echo json_encode([
    'content' => $content,
    'subtotal' => number_format($subtotal, 2),
    'shipping' => number_format($shipping, 2),
    'total' => number_format($total, 2),
    'item_count' => $item_count
]);
?>