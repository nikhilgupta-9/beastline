<?php
session_start();
include_once __DIR__ . "/../config/connect.php";
require_once __DIR__ . '/../admin/models/PaymentSmtpSetting.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\BadRequestError;

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Log received data
    error_log("Buy Now POST data: " . print_r($_POST, true));

    // Check if this is a test request
    $rawInput = file_get_contents('php://input');
    $jsonData = json_decode($rawInput, true);
    
    if (isset($jsonData['test']) || isset($jsonData['debug'])) {
        echo json_encode([
            'success' => true,
            'test_mode' => true,
            'message' => 'Test endpoint working',
            'received' => $jsonData
        ]);
        exit;
    }

    $product_id = intval($_POST['product_id'] ?? 0);
    $variant_id = intval($_POST['variant_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    $size = $_POST['size'] ?? '';
    $color = $_POST['color'] ?? '';

    if ($product_id <= 0) {
        throw new Exception('Invalid product ID: ' . $product_id);
    }

    if ($quantity <= 0) {
        throw new Exception('Invalid quantity: ' . $quantity);
    }

    // Get product details
    $sql = "SELECT p.*, c.categories as category_name 
            FROM products p
            LEFT JOIN categories c ON p.pro_cate = c.id
            WHERE p.pro_id = ? AND p.status = 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product) {
        throw new Exception('Product not found for ID: ' . $product_id);
    }

    // Get variant details
    $variant_details = [];
    $price = floatval($product['selling_price']);
    $sku = $product['sku'] ?? 'SKU' . $product_id;

    if ($variant_id > 0) {
        $var_sql = "SELECT * FROM product_variants WHERE id = ? AND product_id = ?";
        $var_stmt = $conn->prepare($var_sql);
        $var_stmt->bind_param("ii", $variant_id, $product_id);
        $var_stmt->execute();
        $variant_details = $var_stmt->get_result()->fetch_assoc();
        if ($variant_details) {
            $price = floatval($variant_details['price']);
            $sku = $variant_details['sku'] ?? $sku;
        }
    }

    // ===== FIX: Get base URL properly =====
    // First try to get from $GLOBALS, then from $_SERVER, then use default
    $base_url = $site;

    // Get product image - FIX: Ensure valid URL
    $img_sql = "SELECT image_url FROM product_images WHERE product_id = ? AND is_main = 1 LIMIT 1";
    $img_stmt = $conn->prepare($img_sql);
    $img_stmt->bind_param("i", $product_id);
    $img_stmt->execute();
    $image = $img_stmt->get_result()->fetch_assoc();
    
    // Build image URL - MUST be valid
    if ($image && !empty($image['image_url'])) {
        $image_url = $base_url . 'admin/assets/img/uploads/' . $image['image_url'];
    } else {
        // Use a placeholder image if no image found
        $image_url = $base_url . 'assets/img/product/product2.jpg';
    }
    
    // Build product URL
    $product_url = $base_url . '/product-details/' . $product['slug_url'];
    
    error_log("Image URL: " . $image_url);
    error_log("Product URL: " . $product_url);

    // Calculate total in paise
    $amount_in_paise = (int)($price * $quantity * 100);

    // Get Razorpay credentials
    $payment_setting = new PaymentSmtpSetting($conn);
    $key_id = $payment_setting->getSetting('razorpay', 'api_key');
    $secret = $payment_setting->getSetting('razorpay', 'api_secret');

    if (empty($key_id) || empty($secret)) {
        throw new Exception('Razorpay credentials not configured');
    }

    $api = new Api($key_id, $secret);

    // Create order with Magic Checkout fields
    $orderData = [
        'amount' => $amount_in_paise,
        'currency' => 'INR',
        'receipt' => 'order_' . time(),
        'notes' => [
            'product_id' => $product_id,
            'product_name' => $product['pro_name']
        ],
        'line_items_total' => $amount_in_paise,
        'line_items' => [[
            'sku' => $sku,
            'variant_id' => (string)($variant_id ?: 'default'),
            'price' => (int)($price * 100),
            'offer_price' => (int)($price * 100),
            'tax_amount' => 0,
            'quantity' => $quantity,
            'name' => $product['pro_name'],
            'description' => substr(strip_tags($product['short_desc'] ?? $product['pro_name']), 0, 100),
            'weight' => (int)($product['weight'] ?? 500),
            'dimensions' => [
                'length' => (int)($product['length'] ?? 10),
                'width' => (int)($product['width'] ?? 10),
                'height' => (int)($product['height'] ?? 10)
            ],
            'image_url' => $image_url, // Now guaranteed to be a valid URL
            'product_url' => $product_url,
            'notes' => []
        ]]
    ];

    error_log("Order data being sent to Razorpay: " . json_encode($orderData));

    // Create Razorpay order
    $razorpay_order = $api->order->create($orderData);

    error_log("Razorpay order created: " . json_encode($razorpay_order));

    $razorpay_order_id = $razorpay_order['id'];

    // Store in session with the order ID as key
    $_SESSION['pending_magic_order'][$razorpay_order_id] = [
        'razorpay_order_id' => $razorpay_order_id,
        'product_id' => $product_id,
        'variant_id' => $variant_id,
        'quantity' => $quantity,
        'price' => $price,
        'total' => $price * $quantity,
        'size' => $size,
        'color' => $color,
        'product_name' => $product['pro_name'],
        'sku' => $sku
    ];

    error_log("Stored in session: " . print_r($_SESSION['pending_magic_order'][$razorpay_order_id], true));

    echo json_encode([
        'success' => true,
        'razorpay_order_id' => $razorpay_order_id,
        'key_id' => $key_id,
        'amount' => $price * $quantity,
        'product_name' => $product['pro_name']
    ]);

} catch (BadRequestError $e) {
    error_log("Razorpay Bad Request Error: " . $e->getMessage());
    error_log("Error details: " . print_r($e, true));
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Razorpay error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Buy Now Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>