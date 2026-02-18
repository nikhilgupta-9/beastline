<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/connect.php';
require_once __DIR__ . '/../util/mail-services.php';

$mailService = new EmailService($conn, $site);

$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($orderId <= 0) {
    exit('Invalid order');
}

$conn->begin_transaction();

// Lock order row
$orderRes = $conn->query("
    SELECT * FROM orders 
    WHERE order_id = {$orderId} 
    AND email_status = 'pending'
    FOR UPDATE
");

$order = $orderRes->fetch_assoc();

if (!$order) {
    $conn->commit();
    exit('No pending email');
}

// ✅ Decode billing_address JSON
$billing = json_decode($order['billing_address'], true);

if (!$billing || empty($billing['email'])) {
    $conn->query("
        UPDATE orders 
        SET email_status='failed' 
        WHERE order_id={$orderId}
    ");
    $conn->commit();
    exit('Email missing');
}

$email = $billing['email'];
$name  = $billing['name'] ?? 'Customer';

// Build email data
$emailData = buildEmailData($orderId, $conn);

// Send email
$mailService->sendOrderConfirmation($email, $name, $emailData);

// Mark as sent
$stmt = $conn->prepare("
    UPDATE orders 
    SET email_status='sent', email_sent_at=NOW()
    WHERE order_id=?
");
$stmt->bind_param("i", $orderId);
$stmt->execute();

$conn->commit();

echo "Mail sent";

function buildEmailData($orderId, $conn)
{
    $order = $conn->query("
        SELECT * FROM orders WHERE order_id = {$orderId}
    ")->fetch_assoc();

    $itemsRes = $conn->query("
        SELECT * FROM order_items WHERE order_id = {$orderId}
    ");

    $items = [];
    while ($item = $itemsRes->fetch_assoc()) {
        $attr = json_decode($item['attributes'], true) ?? [];

        $items[] = [
            'product_name' => $item['product_name'],
            'quantity'     => $item['quantity'],
            'size'         => $attr['size'] ?? '-',
            'color'        => $attr['color'] ?? '-',
            'image'        => $item['product_image'] ?? null
        ];
    }

    // Decode shipping address JSON
    $shipping = json_decode($order['shipping_address'], true);

    return [
        'order_id'      => $order['order_id'],
        'order_number'  => $order['order_number'],
        'order_date'    => date('d M Y', strtotime($order['created_at'])),
        'order_total'   => number_format($order['final_amount'], 2),
        'payment_method'=> $order['payment_method'],
        'tracking_no'   => $order['tracking_number'] ?? null,
        'items'         => $items,

        // 👇 IMPORTANT
        'shipping_address' => $shipping
    ];
}
