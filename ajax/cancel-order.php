<?php
// cancel-order.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/connect.php';
require_once __DIR__ . '/../util/function.php';
require_once __DIR__ . '/../admin/models/PaymentSmtpSetting.php';

$paymentSetting = new PaymentSmtpSetting($conn);
$razorpay_key_id = $paymentSetting->getSetting('razorpay', 'api_key');
$razorpay_secret = $paymentSetting->getSetting('razorpay', 'api_secret');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: Please login first'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get order ID from request
$input = json_decode(file_get_contents('php://input'), true);
$order_id = isset($input['order_id']) ? intval($input['order_id']) : 0;

// Alternatively, check for POST data (for form submissions)
if (!$order_id && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
}

if (!$order_id) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Order ID is required'
    ]);
    exit();
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // 1. Verify order exists and belongs to user
    $check_sql = "SELECT order_id, order_number, order_status, final_amount, user_id 
                  FROM orders 
                  WHERE order_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $order_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $order = $check_result->fetch_assoc();

    if (!$order) {
        throw new Exception("Order not found or you don't have permission to cancel this order.");
    }

    // 2. Check if order can be cancelled
    $cancellable_statuses = ['pending', 'processing', 'confirmed'];
    if (!in_array(strtolower($order['order_status']), $cancellable_statuses)) {
        throw new Exception("Order cannot be cancelled. Current status: " . ucfirst($order['order_status']));
    }

    // 3. Update order status
    $update_sql = "UPDATE orders 
                   SET order_status = 'cancelled', 
                       updated_at = NOW() 
                   WHERE order_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $order_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception("Failed to update order status.");
    }

    // 4. Insert into order status history
    $history_sql = "INSERT INTO order_status_history 
                    (order_id, status, notes, created_at) 
                    VALUES (?, 'cancelled', 'Order cancelled by customer', NOW())";
    $history_stmt = $conn->prepare($history_sql);
    $history_stmt->bind_param("i", $order_id);
    $history_stmt->execute();

    // 5. Restore product stock if needed
    restoreProductStock($conn, $order_id);

    // 6. Process refund if payment was made
    if ($order['final_amount'] > 0 && $order['order_status'] != 'pending') {
        processRefund($conn, $order_id, $order['final_amount'], $user_id);
    }

    // 7. Send notification to admin
    sendCancellationNotification($conn, $order_id, $user_id);

    // Commit transaction
    mysqli_commit($conn);

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Order #' . $order['order_number'] . ' has been cancelled successfully.',
        'order_number' => $order['order_number'],
        'refund_status' => ($order['final_amount'] > 0) ? 'pending' : 'not_applicable'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Restore product stock when order is cancelled
 */
function restoreProductStock($conn, $order_id) {
    try {
        // Get order items
        $items_sql = "SELECT oi.product_id, oi.quantity, oi.attributes 
                      FROM order_items oi 
                      WHERE oi.order_id = ?";
        $items_stmt = $conn->prepare($items_sql);
        $items_stmt->bind_param("i", $order_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();

        while ($item = $items_result->fetch_assoc()) {
            $product_id = $item['product_id'];
            $quantity = $item['quantity'];
            $attributes = json_decode($item['attributes'] ?? '{}', true);

            // Restore main product stock
            $update_product_sql = "UPDATE products 
                                   SET qty = qty + ? 
                                   WHERE pro_id = ?";
            $update_product_stmt = $conn->prepare($update_product_sql);
            $update_product_stmt->bind_param("ii", $quantity, $product_id);
            $update_product_stmt->execute();

            // Restore variant stock if attributes exist
            if (!empty($attributes['color']) || !empty($attributes['size'])) {
                $color = $attributes['color'] ?? '';
                $size = $attributes['size'] ?? '';
                
                if ($color || $size) {
                    $variant_sql = "UPDATE product_variants 
                                    SET quantity = quantity + ? 
                                    WHERE product_id = ? 
                                    AND (color = ? OR ? = '')
                                    AND (size = ? OR ? = '')";
                    $variant_stmt = $conn->prepare($variant_sql);
                    $variant_stmt->bind_param("iissss", $quantity, $product_id, $color, $color, $size, $size);
                    $variant_stmt->execute();
                }
            }
        }
    } catch (Exception $e) {
        // Log error but don't fail the whole cancellation
        error_log("Stock restoration error for order $order_id: " . $e->getMessage());
    }
}

/**
 * Process refund for cancelled order
 */
function processRefund($conn, $order_id, $amount, $user_id) {
    try {
        // Check if payment was made via Razorpay
        $payment_sql = "SELECT razorpay_payment_id, razorpay_order_id 
                        FROM orders 
                        WHERE order_id = ? 
                        AND razorpay_payment_id IS NOT NULL";
        $payment_stmt = $conn->prepare($payment_sql);
        $payment_stmt->bind_param("i", $order_id);
        $payment_stmt->execute();
        $payment_result = $payment_stmt->get_result();
        $payment = $payment_result->fetch_assoc();

        if ($payment && !empty($payment['razorpay_payment_id'])) {
            // Initiate Razorpay refund
            $refund_id = initiateRazorpayRefund($payment['razorpay_payment_id'], $amount);
            
            if ($refund_id) {
                // Record refund in database
                $refund_sql = "INSERT INTO refunds 
                              (order_id, user_id, amount, razorpay_refund_id, status, reason, created_at) 
                              VALUES (?, ?, ?, ?, 'initiated', 'Order cancelled by customer', NOW())";
                $refund_stmt = $conn->prepare($refund_sql);
                $refund_stmt->bind_param("iids", $order_id, $user_id, $amount, $refund_id);
                $refund_stmt->execute();
                
                // Send refund notification
                sendRefundNotification($conn, $user_id, $amount, $refund_id);
            }
        }
    } catch (Exception $e) {
        // Log error but don't fail the whole cancellation
        error_log("Refund processing error for order $order_id: " . $e->getMessage());
    }
}

/**
 * Initiate Razorpay refund
 */
function initiateRazorpayRefund($payment_id, $amount) {
    
    if (empty($razorpay_key_id) || empty($razorpay_key_secret)) {
        return null;
    }

    try {
        // Include Razorpay SDK
        require_once __DIR__ . '/../vendor/autoload.php'; // If using Composer
        
        // Or use direct API call
        $url = "https://api.razorpay.com/v1/payments/$payment_id/refund";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'amount' => $amount * 100, // Convert to paise
            'speed' => 'normal'
        ]));
        curl_setopt($ch, CURLOPT_USERPWD, $razorpay_key_id . ':' . $razorpay_key_secret);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            $response_data = json_decode($response, true);
            return $response_data['id'] ?? null;
        }
    } catch (Exception $e) {
        error_log("Razorpay refund error: " . $e->getMessage());
    }
    
    return null;
}

/**
 * Send cancellation notification to admin
 */
function sendCancellationNotification($conn, $order_id, $user_id) {
    try {
        // Get user details
        $user_sql = "SELECT first_name, last_name, email, mobile 
                     FROM users 
                     WHERE id = ?";
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        $user = $user_result->fetch_assoc();

        // Get order details
        $order_sql = "SELECT order_number, final_amount 
                      FROM orders 
                      WHERE order_id = ?";
        $order_stmt = $conn->prepare($order_sql);
        $order_stmt->bind_param("i", $order_id);
        $order_stmt->execute();
        $order_result = $order_stmt->get_result();
        $order = $order_result->fetch_assoc();

        if ($user && $order) {
            // 1. Send email to admin
            $admin_email = "support@beastline.in"; // Change to your admin email
            $subject = "Order #" . $order['order_number'] . " Cancelled";
            $message = "
                <html>
                <head>
                    <title>Order Cancellation Notification</title>
                </head>
                <body>
                    <h2>Order Cancelled</h2>
                    <p><strong>Order Number:</strong> #" . $order['order_number'] . "</p>
                    <p><strong>Customer:</strong> " . $user['first_name'] . " " . $user['last_name'] . "</p>
                    <p><strong>Customer Email:</strong> " . $user['email'] . "</p>
                    <p><strong>Customer Phone:</strong> " . $user['mobile'] . "</p>
                    <p><strong>Order Amount:</strong> ₹" . number_format($order['final_amount'], 2) . "</p>
                    <p><strong>Cancelled At:</strong> " . date('Y-m-d H:i:s') . "</p>
                    <p>Please check the admin panel for more details.</p>
                </body>
                </html>
            ";
            
            // Headers for HTML email
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: Beastline <noreply@beastline.com>" . "\r\n";
            
            @mail($admin_email, $subject, $message, $headers);

            // 2. Log cancellation in database (optional)
            // $log_sql = "INSERT INTO admin_notifications 
            //            (title, message, type, related_id, created_at) 
            //            VALUES ('Order Cancelled', ?, 'order_cancelled', ?, NOW())";
            // $log_stmt = $conn->prepare($log_sql);
            // $log_message = "Order #" . $order['order_number'] . " cancelled by " . $user['first_name'] . " " . $user['last_name'];
            // $log_stmt->bind_param("si", $log_message, $order_id);
            // $log_stmt->execute();

            // 3. Send SMS notification (optional)
            sendSMSNotification($user['mobile'], $order['order_number']);
        }
    } catch (Exception $e) {
        error_log("Notification error: " . $e->getMessage());
    }
}

/**
 * Send SMS notification
 */
function sendSMSNotification($mobile, $order_number) {
    // Implement SMS sending logic here
    // You can use Twilio, MSG91, or any other SMS service
    // Example with MSG91:
    /*
    $authKey = "YOUR_MSG91_AUTH_KEY";
    $senderId = "BEASTLN";
    $route = "4";
    $postData = array(
        'authkey' => $authKey,
        'mobiles' => $mobile,
        'message' => "Your order #$order_number has been cancelled successfully. Refund will be processed within 5-7 business days.",
        'sender' => $senderId,
        'route' => $route
    );
    
    $url = "https://api.msg91.com/api/sendhttp.php";
    
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData
    ));
    curl_exec($ch);
    curl_close($ch);
    */
}

/**
 * Send refund notification to user
 */
function sendRefundNotification($conn, $user_id, $amount, $refund_id = null) {
    try {
        // Get user details
        $user_sql = "SELECT email, mobile 
                     FROM users 
                     WHERE id = ?";
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        $user = $user_result->fetch_assoc();

        if ($user) {
            // Send email
            $subject = "Refund Initiated for Your Cancelled Order";
            $message = "
                <html>
                <head>
                    <title>Refund Initiated</title>
                </head>
                <body>
                    <h2>Refund Initiated</h2>
                    <p>Your refund of ₹" . number_format($amount, 2) . " has been initiated.</p>
                    " . ($refund_id ? "<p><strong>Refund ID:</strong> $refund_id</p>" : "") . "
                    <p>The amount will be credited to your original payment method within 5-7 business days.</p>
                    <p>If you have any questions, please contact our support team.</p>
                    <p>Thank you,<br>Beastline Team</p>
                </body>
                </html>
            ";
            
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: Beastline Support <support@beastline.com>" . "\r\n";
            
            @mail($user['email'], $subject, $message, $headers);
        }
    } catch (Exception $e) {
        error_log("Refund notification error: " . $e->getMessage());
    }
}

// Close database connection
if (isset($conn)) {
    mysqli_close($conn);
}
?>