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
    $cart_items = json_decode($_POST['cart_items'], true);
    $form_data = $_POST['form_data'] ?? '';

    if (empty($cart_items)) {
        throw new Exception('Cart is empty');
    }

    // --- 2. Calculate Totals and Build Line Items ---
    $subtotal = 0;
    $line_items = [];

    foreach ($cart_items as $item_data) {
        // Handle your cart structure
        $product = $item_data['product'];
        $cart_item = $item_data['cart_item'];

        $product_id = $product['pro_id'];
        $quantity = $cart_item['quantity'];
        $price = $cart_item['price'];
        $variant_id = $cart_item['variant_id'] ?? 0;
        $size = $cart_item['size'] ?? '';
        $color = $cart_item['color'] ?? '';

        $item_total = $price * $quantity;
        $subtotal += $item_total;

        // Get product image
        $image_sql = "SELECT image_url FROM product_images WHERE product_id = ? AND is_main = 1 LIMIT 1";
        $image_stmt = $conn->prepare($image_sql);
        $image_stmt->bind_param("i", $product_id);
        $image_stmt->execute();
        $image_result = $image_stmt->get_result();
        $image = $image_result->fetch_assoc();
        $image_url = $image ? $site . 'admin/assets/img/uploads/' . $image['image_url'] : $site . 'assets/img/product/default.jpg';

        // Build line item
        $line_items[] = [
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
        ];
    }

    // Calculate shipping and total
    $shipping_fee = ($subtotal >= 1000) ? 0 : 0; // ₹1 in paise
    $total = $subtotal + ($shipping_fee / 100);

    // --- 3. Create Order in Razorpay ---
    $paymentSetting = new PaymentSmtpSetting($conn);
    $api = new Api($paymentSetting->getSetting('razorpay', 'api_key'), $paymentSetting->getSetting('razorpay', 'api_secret'));

    $razorpay_order = $api->order->create([
        'amount' => (int)($total * 100),
        'currency' => 'INR',
        'receipt' => 'cart_' . time(),
        'line_items_total' => (int)($subtotal * 100),
        'line_items' => $line_items
    ]);

    // --- 4. Store in Session ---
   // After creating Razorpay order, store more details
$_SESSION['pending_magic_order'][$razorpay_order['id']] = [
    'razorpay_order_id' => $razorpay_order['id'],
    'cart_items' => $cart_items,
    'total' => $total,
    'subtotal' => $subtotal,
    'shipping_fee' => $shipping_fee / 100,
    'user_id' => $_SESSION['user_id'] ?? null,
    'user_name' => $_SESSION['user_name'] ?? $_POST['name'] ?? '',
    'user_email' => $_SESSION['user_email'] ?? $_POST['email'] ?? '',
    'user_phone' => $_SESSION['user_phone'] ?? $_POST['phone'] ?? '',
    'address' => $_POST['address'] ?? '',
    'city' => $_POST['city'] ?? '',
    'state' => $_POST['state'] ?? '',
    'postcode' => $_POST['pincode'] ?? '',
    'created_at' => time()
];

    // --- 5. Return Success ---
    echo json_encode([
        'success' => true,
        'razorpay_order_id' => $razorpay_order['id'],
        'key_id' => $api->getKey(),
        'amount' => $total
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    error_log("Create Magic Order Error: " . $e->getMessage());
}
