<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/connect.php';

$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($orderId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

// Set JSON header for AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
}

// Start output buffering
ob_start();

try {
    // Begin transaction
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
        $response = ['success' => false, 'message' => 'No pending email for this order'];
        ob_end_clean();
        echo json_encode($response);
        exit;
    }

    // Decode billing_address JSON
    $billing = json_decode($order['billing_address'], true);

    if (!$billing || empty($billing['email'])) {
        $conn->query("
            UPDATE orders 
            SET email_status='failed' 
            WHERE order_id={$orderId}
        ");
        $conn->commit();
        $response = ['success' => false, 'message' => 'Email missing in billing address'];
        ob_end_clean();
        echo json_encode($response);
        exit;
    }

    $email = $billing['email'];
    $name = $billing['name'] ?? 'Customer';

    // Build email data
    $emailData = buildEmailData($orderId, $conn);

    // Send email using mail() function
    $result = sendMail($email, $name, $emailData);

    if ($result['success']) {
        // Mark as sent
        $stmt = $conn->prepare("
            UPDATE orders 
            SET email_status='sent', email_sent_at=NOW()
            WHERE order_id=?
        ");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        
        $conn->commit();
        
        // Save copy for debugging (optional)
        saveEmailCopy($orderId, $email, $emailData);
        
        $response = [
            'success' => true,
            'message' => "Email sent successfully to: $email"
        ];
    } else {
        // Mark as failed
        $stmt = $conn->prepare("
            UPDATE orders 
            SET email_status='failed'
            WHERE order_id=?
        ");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        
        $conn->commit();
        
        $response = [
            'success' => false,
            'message' => $result['message'],
            'error' => $result['error']
        ];
    }

    ob_end_clean();
    echo json_encode($response);

} catch (Exception $e) {
    $conn->rollback();
    
    // Update status on error
    if (isset($orderId)) {
        $conn->query("
            UPDATE orders 
            SET email_status='failed' 
            WHERE order_id={$orderId}
        ");
        $conn->commit();
    }
    
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

/**
 * Send email using PHP's mail() function only
 */
function sendMail($to, $name, $data) {
    $result = [
        'success' => false,
        'message' => '',
        'error' => null
    ];
    
    try {
        // Subject
        $subject = "Order Confirmation - #{$data['order_number']} from Beastline";
        
        // Generate HTML email body
        $message = generateEmailTemplate($name, $data);
        
        // Headers
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Beastline <noreply@beastline.in>" . "\r\n";
        $headers .= "Reply-To: support@beastline.in" . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        
        // Additional headers for better delivery
        $headers .= "X-Priority: 3" . "\r\n";
        $headers .= "X-MSMail-Priority: Normal" . "\r\n";
        
        // Return-Path for bounces
        $headers .= "Return-Path: noreply@beastline.in" . "\r\n";
        
        // Parameters for sendmail (if on Linux)
        $params = "-f noreply@beastline.in";
        
        // Log attempt
        error_log("Attempting to send email to: $to for order #{$data['order_number']}");
        
        // Send email
        if (function_exists('mail')) {
            $mailResult = mail($to, $subject, $message, $headers, $params);
            
            if ($mailResult) {
                $result['success'] = true;
                $result['message'] = "Email sent successfully to: $to";
                error_log("✅ Email sent to: $to for order #{$data['order_number']}");
            } else {
                $error = error_get_last();
                $result['error'] = $error['message'] ?? 'Unknown error';
                $result['message'] = "Failed to send email";
                error_log("❌ mail() failed for: $to - " . $result['error']);
            }
        } else {
            $result['error'] = "mail() function does not exist";
            $result['message'] = "PHP mail function not available";
            error_log("❌ mail() function not available");
        }
        
    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
        $result['message'] = "Exception occurred";
        error_log("❌ Exception in sendMail: " . $e->getMessage());
    }
    
    return $result;
}

/**
 * Generate HTML email template
 */
function generateEmailTemplate($name, $order) {
    $itemsHtml = '';
    
    foreach ($order['items'] as $item) {
        $productName = htmlspecialchars($item['product_name'] ?? 'Product');
        $color = htmlspecialchars($item['color'] ?? '-');
        $size = htmlspecialchars($item['size'] ?? '-');
        $quantity = intval($item['quantity'] ?? 1);
        
        // Image URL
        $img = !empty($item['image']) 
            ? "https://beastline.in/admin/assets/img/uploads/" . $item['image']
            : "https://beastline.in/assets/img/logo/logo.png";
        
        $itemsHtml .= "
        <tr>
            <td style='padding:15px 0; border-bottom:1px solid #eaeaea;'>
                <table width='100%' cellpadding='0' cellspacing='0'>
                    <tr>
                        <td width='90'>
                            <img src='{$img}' width='80' style='border-radius:8px; border:1px solid #eee;' />
                        </td>
                        <td style='padding-left:15px;'>
                            <div style='font-size:14px; font-weight:600; color:#222;'>
                                {$productName}
                            </div>
                            <div style='font-size:12px; color:#777; margin-top:4px;'>
                                Size: {$size}
                            </div>
                            <div style='font-size:12px; color:#555; margin-top:6px;'>
                                Quantity: {$quantity}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>";
    }
    
    $shipping = $order['shipping_address'] ?? [];
    $shippingName = htmlspecialchars($shipping['name'] ?? $name);
    $shippingAddress = htmlspecialchars($shipping['address'] ?? 'N/A');
    $shippingCity = htmlspecialchars($shipping['city'] ?? 'N/A');
    $shippingState = htmlspecialchars($shipping['state'] ?? 'N/A');
    $shippingPostcode = htmlspecialchars($shipping['postcode'] ?? 'N/A');
    $shippingPhone = htmlspecialchars($shipping['phone'] ?? 'N/A');
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Order Confirmation</title>
        <style>
            body { font-family: Arial, Helvetica, sans-serif; background: #f7f8fa; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 10px; overflow: hidden; }
            .header { padding: 22px 25px; border-bottom: 1px solid #eee; }
            .content { padding: 30px 25px; }
            .address { background: #fafafa; padding: 20px 25px; border-top: 1px solid #eee; }
            .button { display: inline-block; background: #ffffff; color: #000; padding: 8px 19px; 
                      text-decoration: none; font-size: 14px; border-radius: 6px; border: 1px solid black; }
            .footer { padding: 20px 25px; border-top: 1px solid #eee; font-size: 12px; color: #777; text-align: center; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <table width="100%">
                    <tr>
                        <td>
                            <img src="https://beastline.in/assets/img/logo/beastline-logo.png" 
                                 height="55" alt="Beastline">
                        </td>
                        <td align="right" style="font-size:12px; color:#888;">
                            <b>Order #' . $order['order_number'] . '</b>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="content">
                <h2 style="margin:0; font-size:22px; color:#111;">Order Confirmed 🎉</h2>
                <p style="margin-top:8px; font-size:14px; color:#555;">
                    Hi ' . htmlspecialchars($name) . ', thank you for shopping with Beastline.<br>
                    Your order has been successfully placed.
                </p>
                
                <table style="margin:20px 0;">
                    <tr>
                        <td style="padding-right:20px;">
                            <div style="font-size:12px; color:#888;">Order Date</div>
                            <div style="font-size:14px; color:#222;">' . $order['order_date'] . '</div>
                        </td>
                        <td>
                            <div style="font-size:12px; color:#888;">Payment Method</div>
                            <div style="font-size:14px; color:#222;">' . strtoupper($order['payment_method']) . '</div>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="address">
                <h3 style="margin:0 0 8px; font-size:15px; color:#222;">Shipping Address</h3>
                <p style="margin:0; font-size:13px; color:#555; line-height:1.6;">
                    ' . $shippingName . '<br>
                    ' . $shippingAddress . '<br>
                    ' . $shippingCity . ', ' . $shippingState . ' - ' . $shippingPostcode . '<br>
                    Phone: ' . $shippingPhone . '
                </p>
            </div>
            
            <div style="padding:25px;">
                <a href="https://beastline.in/track-order.php?order_number=' . $order['order_number'] . '" 
                   class="button">Track Your Order</a>
            </div>
            
            <div style="padding:10px 25px;">
                <h3 style="font-size:16px; color:#222;">Order Items</h3>
            </div>
            
            <div style="padding:0 25px 20px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    ' . $itemsHtml . '
                </table>
            </div>
            
            <div style="padding:0 25px 20px;">
                <table width="100%" style="border-top:2px solid #f0f0f0; padding-top:15px;">
                    <tr>
                        <td align="right" style="font-size:14px;">
                            <strong>Total Amount: ₹' . $order['order_total'] . '</strong>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="footer">
                Need help? Email us at <a href="mailto:support@beastline.in" style="color:#000;">support@beastline.in</a><br><br>
                © ' . date('Y') . ' Beastline. All rights reserved.
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Build email data from order with correct product images
 */
function buildEmailData($orderId, $conn) {
    $order = $conn->query("
        SELECT * FROM orders WHERE order_id = {$orderId}
    ")->fetch_assoc();

    $itemsRes = $conn->query("
        SELECT oi.*, p.pro_id, p.pro_name 
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.pro_id
        WHERE oi.order_id = {$orderId}
    ");

    $items = [];
    while ($item = $itemsRes->fetch_assoc()) {
        $attr = json_decode($item['attributes'], true) ?? [];
        
        // Get main product image from product_images table
        $image = null;
        $imgQuery = $conn->query("
            SELECT image_url FROM product_images 
            WHERE product_id = {$item['product_id']} 
            AND is_main = 1 
            LIMIT 1
        ");
        
        if ($imgQuery && $imgQuery->num_rows > 0) {
            $imgRow = $imgQuery->fetch_assoc();
            $image = $imgRow['image_url'];
        } else {
            // Fallback to order_items product_image if available
            $image = $item['product_image'] ?? null;
        }

        $items[] = [
            'product_id'   => $item['product_id'],
            'product_name' => $item['product_name'],
            'quantity'     => $item['quantity'],
            'size'         => $attr['size'] ?? '-',
            'color'        => $attr['color'] ?? '-',
            'image'        => $image
        ];
    }

    $shipping = json_decode($order['shipping_address'], true);

    return [
        'order_id'       => $order['order_id'],
        'order_number'   => $order['order_number'],
        'order_date'     => date('d M Y, h:i A', strtotime($order['created_at'])),
        'order_total'    => number_format($order['final_amount'], 2),
        'payment_method' => $order['payment_method'],
        'tracking_no'    => $order['tracking_number'] ?? null,
        'items'          => $items,
        'shipping_address' => $shipping
    ];
}

/**
 * Save a copy of the email for debugging
 */
function saveEmailCopy($orderId, $email, $emailData) {
    $logDir = __DIR__ . '/../logs/emails';
    
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $filename = $logDir . '/order_' . $orderId . '_' . date('Ymd_His') . '.html';
    $content = "<!-- Email to: $email -->\n" . generateEmailTemplate('Customer', $emailData);
    
    file_put_contents($filename, $content);
}
?>