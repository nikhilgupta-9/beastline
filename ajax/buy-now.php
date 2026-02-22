<?php
session_start();
require_once __DIR__ . '/../config/connect.php';
require_once __DIR__ . '/../admin/models/PaymentSmtpSetting.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Razorpay\Api\Api;

header('Content-Type: application/json');

$site = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . '/';

try {
    // --- 1. Get and Validate Input ---
    $product_id = intval($_POST['product_id'] ?? 0);
    $variant_id = intval($_POST['variant_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    $size = $_POST['size'] ?? '';
    $color = $_POST['color'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    
    if ($product_id <= 0 || $quantity <= 0) throw new Exception('Invalid product data');

    // --- 2. Fetch Product Details ---
    $sql = "SELECT p.* FROM products p WHERE p.pro_id = ? AND p.status = 1";
    $stmt = $conn->prepare($sql); 
    $stmt->bind_param("i", $product_id); 
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    if (!$product) throw new Exception('Product not found');

    // --- 3. Get Product Image ---
    $image_sql = "SELECT image_url FROM product_images WHERE product_id = ? AND is_main = 1 LIMIT 1";
    $image_stmt = $conn->prepare($image_sql); 
    $image_stmt->bind_param("i", $product_id); 
    $image_stmt->execute();
    $image = $image_stmt->get_result()->fetch_assoc();
    $image_url = $image ? $site . 'admin/assets/img/uploads/' . $image['image_url'] : $site . 'assets/img/product/default.jpg';

    // --- 4. Build Line Items ---
    $line_items = [[
        'sku' => $product['sku'] ?? 'SKU' . $product_id,
        'variant_id' => (string)($variant_id ?: 'default'),
        'price' => (int)($price * 100),
        'offer_price' => (int)($price * 100),
        'tax_amount' => 0,
        'quantity' => (int)$quantity,
        'name' => $product['pro_name'],
        'description' => substr(strip_tags($product['short_desc'] ?? ''), 0, 100),
        'weight' => (int)($product['weight'] ?? 500),
        'dimensions' => [
            'length' => (int)($product['length'] ?? 10),
            'width' => (int)($product['width'] ?? 10),
            'height' => (int)($product['height'] ?? 10)
        ],
        'image_url' => $image_url,
        'product_url' => $site . 'product-details/' . $product['slug_url'],
    ]];

    // --- 5. Create Order in Razorpay ---
    $paymentSetting = new PaymentSmtpSetting($conn);
    $api = new Api($paymentSetting->getSetting('razorpay', 'api_key'), $paymentSetting->getSetting('razorpay', 'api_secret'));

    $razorpay_order = $api->order->create([
        'amount' => (int)(($price * $quantity) * 100),
        'currency' => 'INR',
        'receipt' => 'bn_' . time(),
        'line_items_total' => (int)(($price * $quantity) * 100),
        'line_items' => $line_items
    ]);

    // --- 6. Store in Session with ALL Data ---
    $_SESSION['pending_magic_order'][$razorpay_order['id']] = [
        'razorpay_order_id' => $razorpay_order['id'],
        'product_id' => $product_id,
        'variant_id' => $variant_id,
        'quantity' => $quantity,
        'price' => $price,
        'product_name' => $product['pro_name'],
        'size' => $size,
        'color' => $color
    ];

    // --- 7. Return Success ---
    echo json_encode([
        'success' => true,
        'razorpay_order_id' => $razorpay_order['id'],
        'key_id' => $api->getKey(),
        'amount' => $price * $quantity,
        'product_name' => $product['pro_name']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    error_log("Buy Now Error: " . $e->getMessage());
}
?>