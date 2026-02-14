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

$user_id = (int)$order['user_id'];

// 1️⃣ Try billing email
$billing = json_decode($order['billing_address'], true);
$email   = $billing['email'] ?? '';
$name    = $billing['name'] ?? 'Customer';

// 2️⃣ Fallback to user table
if (empty($email) && $user_id > 0) {

    $uStmt = $conn->prepare("
            SELECT email, name 
            FROM users 
            WHERE id = ?
            LIMIT 1
        ");
    $uStmt->bind_param("i", $user_id);
    $uStmt->execute();
    $user = $uStmt->get_result()->fetch_assoc();

    if ($user && !empty($user['email'])) {
        $email = $user['email'];
        $name  = $user['name'] ?? $name;
    }
}

// 3️⃣ Still no email → fail
if (empty($email)) {

    $failStmt = $conn->prepare("
            UPDATE orders 
            SET email_status = 'failed'
            WHERE order_id = ?
        ");
    $failStmt->bind_param("i", $orderId);
    $failStmt->execute();

    $conn->commit();
    exit('Email not found');
}


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
    SELECT 
        oi.product_name,
        oi.quantity,
        oi.attributes,
        pi.image_url AS product_image
    FROM order_items oi
    LEFT JOIN products p ON p.id = oi.product_id
    LEFT JOIN product_images pi ON pi.product_id = p.id
    WHERE oi.order_id = {$orderId}
    ORDER BY pi.is_main DESC, pi.display_order ASC
    LIMIT 1
");



    $items = [];

    while ($item = $itemsRes->fetch_assoc()) {
        $attr = json_decode($item['attributes'], true) ?? [];

        $items[] = [
            'product_name' => $item['product_name'],
            'quantity'     => (int)$item['quantity'],
            'size'         => !empty($attr['size']) ? strtoupper($attr['size']) : '-',
            'color'        => !empty($attr['color']) ? ucfirst($attr['color']) : '-',
            'sku'          => !empty($attr['sku']) ? $attr['sku'] : '-',   // ✅ FIX
            'image'        => !empty($item['product_image']) ? $item['product_image'] : null
        ];
    }

    return [
        'order_number'     => $order['order_number'],
        'order_date'       => date('d M Y', strtotime($order['created_at'])),
        'order_total'      => number_format($order['final_amount'], 2),
        'tracking_no'      => $order['tracking_number'] ?? null,
        'items'            => $items,
        'shipping_address' => json_decode($order['shipping_address'], true)
    ];
}
