<?php
session_start();
require_once __DIR__ . '/../config/connect.php';
require_once __DIR__ . '/../util/mail-services.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../admin/models/PaymentSmtpSetting.php';

use Razorpay\Api\Api;
use Razorpay\Api\Client;
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Log incoming request for debugging
file_put_contents('payment_debug.log', "[" . date('Y-m-d H:i:s') . "] Request received\n", FILE_APPEND);
file_put_contents('payment_debug.log', "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    file_put_contents('payment_debug.log', "Action: $action\n", FILE_APPEND);
    
    switch($action) {
        case 'complete_payment':
            try {
                // Get and decode payment response
                if(isset($_POST['payment_response'])) {
                    $payment_response = json_decode($_POST['payment_response'], true);
                    
                    if(json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception('Invalid payment response JSON: ' . json_last_error_msg());
                    }
                    
                    file_put_contents('payment_debug.log', "Payment response decoded: " . print_r($payment_response, true) . "\n", FILE_APPEND);
                    
                    $order_id = intval($_POST['order_id']);
                    
                    // Get Razorpay settings
                    $payment_setting = new PaymentSmtpSetting($conn);
                    $razorpay_key_id = $payment_setting->getSetting('razorpay', 'api_key');
                    $razorpay_secret = $payment_setting->getSetting('razorpay', 'api_secret');
                    
                    file_put_contents('payment_debug.log', "Razorpay Key: $razorpay_key_id\n", FILE_APPEND);
                    
                    $client = new Api($razorpay_key_id, $razorpay_secret);
                    
                    // Verify payment signature
                    $attributes = [
                        'razorpay_order_id' => $payment_response['razorpay_order_id'],
                        'razorpay_payment_id' => $payment_response['razorpay_payment_id'],
                        'razorpay_signature' => $payment_response['razorpay_signature']
                    ];
                    
                    file_put_contents('payment_debug.log', "Verifying signature with attributes: " . print_r($attributes, true) . "\n", FILE_APPEND);
                    
                    $client->utility->verifyPaymentSignature($attributes);
                    
                    // Update order status
                    $update_sql = "UPDATE orders SET 
                        payment_status = 'paid', 
                        order_status = 'confirmed',
                        razorpay_payment_id = ?,
                        razorpay_signature = ?,
                        updated_at = NOW()
                        WHERE order_id = ?";
                    
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param(
                        "ssi",
                        $payment_response['razorpay_payment_id'],
                        $payment_response['razorpay_signature'],
                        $order_id
                    );
                    
                    if($update_stmt->execute()) {
                        // Record payment
                        $payment_sql = "INSERT INTO payments (
                            user_id, order_id, payment_id, amount, status, created_at
                        ) VALUES (?, ?, ?, ?, 'completed', NOW())";
                        
                        $payment_stmt = $conn->prepare($payment_sql);
                        
                        // Get order details
                        $order_sql = "SELECT o.*, u.id as user_id 
                                     FROM orders o 
                                     LEFT JOIN users u ON o.user_id = u.id 
                                     WHERE o.order_id = ?";
                        $order_stmt = $conn->prepare($order_sql);
                        $order_stmt->bind_param("i", $order_id);
                        $order_stmt->execute();
                        $order_result = $order_stmt->get_result();
                        $order = $order_result->fetch_assoc();
                        
                        $user_id = $order['user_id'] ?? NULL;
                        
                        $payment_stmt->bind_param(
                            "iisd",
                            $user_id,
                            $order_id,
                            $payment_response['razorpay_payment_id'],
                            $order['final_amount']
                        );
                        $payment_stmt->execute();
                        
                        // Send order confirmation email if email service exists
                        if(class_exists('EmailService')) {
                            try {
                                $email_service = new EmailService($conn, $site);
                                
                                // Decode addresses
                                $billing_address = json_decode($order['billing_address'], true);
                                
                                $email_service->sendOrderConfirmation(
                                    $billing_address['email'],
                                    $billing_address['first_name'] . ' ' . $billing_address['last_name'],
                                    [
                                        'order_number' => $order['order_number'],
                                        'order_id' => $order_id,
                                        'total' => $order['final_amount']
                                    ]
                                );
                            } catch(Exception $e) {
                                file_put_contents('payment_debug.log', "Email error: " . $e->getMessage() . "\n", FILE_APPEND);
                            }
                        }
                        
                        // Clear cart
                        unset($_SESSION['cart']);
                        unset($_SESSION['promotion_code']);
                        
                        echo json_encode([
                            'success' => true,
                            'order_id' => $order_id,
                            'order_number' => $order['order_number'],
                            'message' => 'Payment successful! Order confirmed.'
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Payment response not received']);
                }
            } catch (Exception $e) {
                file_put_contents('payment_debug.log', "Payment error: " . $e->getMessage() . "\n", FILE_APPEND);
                echo json_encode(['success' => false, 'message' => 'Payment verification failed: ' . $e->getMessage()]);
            }
            break;
            
        case 'complete_cod_advance':
            try {
                // Get and decode payment response
                if(isset($_POST['payment_response'])) {
                    $payment_response = json_decode($_POST['payment_response'], true);
                    
                    if(json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception('Invalid payment response JSON: ' . json_last_error_msg());
                    }
                    
                    $order_id = intval($_POST['order_id']);
                    $cod_advance = floatval($_POST['cod_advance']);
                    
                    // Get Razorpay settings
                    $payment_setting = new PaymentSmtpSetting($conn);
                    $razorpay_key_id = $payment_setting->getSetting('razorpay', 'api_key');
                    $razorpay_secret = $payment_setting->getSetting('razorpay', 'api_secret');
                    
                    $client = new Api($razorpay_key_id, $razorpay_secret);
                    
                    // Verify payment signature
                    $attributes = [
                        'razorpay_order_id' => $payment_response['razorpay_order_id'],
                        'razorpay_payment_id' => $payment_response['razorpay_payment_id'],
                        'razorpay_signature' => $payment_response['razorpay_signature']
                    ];
                    
                    $client->utility->verifyPaymentSignature($attributes);
                    
                    // Update order status
                    $update_sql = "UPDATE orders SET 
                        payment_status = 'cod_advance_paid', 
                        order_status = 'pending',
                        razorpay_payment_id = ?,
                        razorpay_signature = ?,
                        updated_at = NOW()
                        WHERE order_id = ?";
                    
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param(
                        "ssi",
                        $payment_response['razorpay_payment_id'],
                        $payment_response['razorpay_signature'],
                        $order_id
                    );
                    
                    if($update_stmt->execute()) {
                        // Record advance payment
                        $payment_sql = "INSERT INTO payments (
                            user_id, order_id, payment_id, amount, status, created_at
                        ) VALUES (?, ?, ?, ?, 'completed', NOW())";
                        
                        $payment_stmt = $conn->prepare($payment_sql);
                        
                        // Get order details
                        $order_sql = "SELECT o.*, u.id as user_id 
                                     FROM orders o 
                                     LEFT JOIN users u ON o.user_id = u.id 
                                     WHERE o.order_id = ?";
                        $order_stmt = $conn->prepare($order_sql);
                        $order_stmt->bind_param("i", $order_id);
                        $order_stmt->execute();
                        $order_result = $order_stmt->get_result();
                        $order = $order_result->fetch_assoc();
                        
                        $user_id = $order['user_id'] ?? NULL;
                        
                        $payment_stmt->bind_param(
                            "iisd",
                            $user_id,
                            $order_id,
                            $payment_response['razorpay_payment_id'],
                            $cod_advance
                        );
                        $payment_stmt->execute();
                        
                        // Send order confirmation email if email service exists
                        if(class_exists('EmailService')) {
                            try {
                                $email_service = new EmailService($conn, $site);
                                
                                // Decode addresses
                                $billing_address = json_decode($order['billing_address'], true);
                                
                                // Parse notes to get COD details
                                $notes = $order['notes'];
                                $cod_remaining = 0;
                                if(preg_match('/\|cod_remaining:(\d+(\.\d+)?)/', $notes, $matches)) {
                                    $cod_remaining = floatval($matches[1]);
                                }
                                
                                $email_service->sendOrderConfirmation(
                                    $billing_address['email'],
                                    $billing_address['first_name'] . ' ' . $billing_address['last_name'],
                                    [
                                        'order_number' => $order['order_number'],
                                        'order_id' => $order_id,
                                        'total' => $order['final_amount'],
                                        'cod_advance' => $cod_advance,
                                        'cod_remaining' => $cod_remaining
                                    ]
                                );
                            } catch(Exception $e) {
                                file_put_contents('payment_debug.log', "Email error: " . $e->getMessage() . "\n", FILE_APPEND);
                            }
                        }
                        
                        // Clear cart
                        unset($_SESSION['cart']);
                        unset($_SESSION['promotion_code']);
                        
                        echo json_encode([
                            'success' => true,
                            'order_id' => $order_id,
                            'order_number' => $order['order_number'],
                            'message' => 'COD advance payment successful! Order placed.'
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Payment response not received']);
                }
            } catch (Exception $e) {
                file_put_contents('payment_debug.log', "COD Advance error: " . $e->getMessage() . "\n", FILE_APPEND);
                echo json_encode(['success' => false, 'message' => 'Payment verification failed: ' . $e->getMessage()]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method',
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'post_data' => $_POST
    ]);
}
?>