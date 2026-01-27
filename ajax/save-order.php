<?php
session_start();
require_once __DIR__ . '/../config/connect.php';
require_once __DIR__ . '/../config/ithink-api.php';
require_once __DIR__ . '/../includes/ithink-logistics.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' || isset($_SESSION['temp_order_data'])) {
    try {
        // Check if temp order data exists
        if (!isset($_SESSION['temp_order_data'])) {
            throw new Exception('No order data found. Please start checkout again.');
        }

        $temp_data = $_SESSION['temp_order_data'];
        $payment_method = $_SESSION['temp_order_payment_method'] ?? 'razorpay';

        // Generate final order number
        $prefix = ($payment_method == 'cod') ? 'COD' : 'ORD';
        $order_number = $prefix . date('YmdHis') . mt_rand(1000, 9999);

        // Extract data from temp storage
        $user_id = $temp_data['user_id'];
        $form_data = $temp_data['form_data'];
        $subtotal = $temp_data['subtotal'];
        $discount_amount = $temp_data['discount_amount'];
        $shipping_amount = $temp_data['shipping_amount'];
        $tax_amount = $temp_data['tax_amount'];
        $final_amount = $temp_data['final_amount'];
        $cart_items_data = $temp_data['cart_items_data'];
        $billing_address = $temp_data['billing_address'];
        $shipping_address = $temp_data['shipping_address'];
        $notes = $temp_data['notes'];

        // For COD, add advance payment info to notes
        if ($payment_method == 'cod') {
            $cod_advance = $_SESSION['cod_advance'] ?? 200;
            $cod_remaining = $_SESSION['cod_remaining'] ?? ($final_amount - $cod_advance);
            $notes .= '|cod_advance:' . $cod_advance . '|cod_remaining:' . $cod_remaining;
        }

        // Determine payment status
        $payment_status = ($payment_method == 'cod') ? 'cod_advance_paid' : 'paid';
        $order_status = 'pending';

        // Create order record
        $sql = "INSERT INTO orders (
            user_id, order_number, total_amount, discount_amount, 
            shipping_amount, tax_amount, final_amount, payment_method, 
            payment_status, order_status, shipping_address, billing_address, 
            notes, razorpay_order_id, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }

        // Get Razorpay order ID from session if exists
        $razorpay_order_id = $_SESSION['temp_razorpay_order_id'] ?? null;

        $stmt->bind_param(
            "isdddddssssss",
            $user_id,
            $order_number,
            $subtotal,
            $discount_amount,
            $shipping_amount,
            $tax_amount,
            $final_amount,
            $payment_method,
            $payment_status,
            $order_status,
            $shipping_address,
            $billing_address,
            $notes,
            $razorpay_order_id
        );

        if ($stmt->execute()) {
            $order_id = $stmt->insert_id;

            // Add order items
            foreach ($cart_items_data as $item_data) {
                $product = $item_data['product'];
                $cart_item = $item_data['cart_item'];

                // Prepare attributes
                $attributes = [];
                if (!empty($cart_item['color'])) $attributes['color'] = $cart_item['color'];
                if (!empty($cart_item['size'])) $attributes['size'] = $cart_item['size'];

                $item_sql = "INSERT INTO order_items (
                    order_id, product_id, product_name, quantity, 
                    unit_price, total_price, attributes
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";

                $item_stmt = $conn->prepare($item_sql);
                $attributes_json = json_encode($attributes);

                $item_stmt->bind_param(
                    "iisidds",
                    $order_id,
                    $product['pro_id'],
                    $product['pro_name'],
                    $cart_item['quantity'],
                    $cart_item['price'],
                    $item_data['item_total'],
                    $attributes_json
                );
                $item_stmt->execute();
            }

            // ============ iTHINK LOGISTICS INTEGRATION ============
            // Prepare shipping data for iThink
            $shipping_info = json_decode($shipping_address, true);
            $billing_info = json_decode($billing_address, true);

            $ithinkData = [
                'order_id' => $order_id,
                'order_number' => $order_number,
                'total_amount' => $final_amount,
                'final_amount' => $final_amount,
                'total_quantity' => array_sum(array_column(array_column($cart_items_data, 'cart_item'), 'quantity')),
                'shipping_name' => $shipping_info['first_name'] . ' ' . $shipping_info['last_name'],
                'email' => $shipping_info['email'] ?? $billing_info['email'],
                'phone' => $shipping_info['phone'] ?? $billing_info['phone'],
                'shipping_address' => $shipping_info['address_1'] . ' ' . ($shipping_info['address_2'] ?? ''),
                'city' => $shipping_info['city'],
                'state' => $shipping_info['state'],
                'pincode' => $shipping_info['postcode'],
                'country' => $shipping_info['country'] ?? 'India'
            ];

            // Only create shipment if payment is successful
            if ($payment_status === 'paid' || $payment_status === 'cod_advance_paid') {
                $ithink = new iThinkLogistics();
                $shipmentResponse = $ithink->createShipment($ithinkData);

                error_log("iThink API Response: " . print_r($shipmentResponse, true));

                if (
                    $shipmentResponse['status'] &&
                    isset($shipmentResponse['data']['success']) &&
                    $shipmentResponse['data']['success']
                ) {

                    // Save shipment details
                    $trackingNumber = $shipmentResponse['data']['data']['order_id'] ?? null;
                    $awbNumber = $shipmentResponse['data']['data']['awb_number'] ?? null;

                    error_log("Tracking: $trackingNumber, AWB: $awbNumber");

                    $updateSql = "UPDATE orders SET 
                        tracking_number = ?,
                        awb_number = ?,
                        ithink_order_id = ?,
                        shipment_data = ?
                        WHERE order_id = ?";

                    $updateStmt = $conn->prepare($updateSql);
                    $shipmentDataJson = json_encode($shipmentResponse['data']);

                    $updateStmt->bind_param(
                        "ssssi",
                        $trackingNumber,
                        $awbNumber,
                        $trackingNumber,
                        $shipmentDataJson,
                        $order_id
                    );
                    $updateStmt->execute();

                    // Save to shipment_tracking table
                    $trackingSql = "INSERT INTO shipment_tracking 
                        (order_id, awb_number, tracking_data, status) 
                        VALUES (?, ?, ?, ?)";

                    $trackingStmt = $conn->prepare($trackingSql);
                    $trackingStatus = 'Shipment Created';
                    $trackingStmt->bind_param(
                        "isss",
                        $order_id,
                        $awbNumber,
                        $shipmentDataJson,
                        $trackingStatus
                    );
                    $trackingStmt->execute();
                } else {
                    // Add this to see what's wrong
                    $errorMsg = "iThink API Error: " .
                        print_r($shipmentResponse['data'] ?? $shipmentResponse, true);
                    error_log($errorMsg);

                    // Optional: Store error in session for debugging
                    $_SESSION['ithink_debug'] = $errorMsg;
                }
            }
            // ============ END iTHINK LOGISTICS ============

            // Clear temp session data
            unset($_SESSION['temp_order_data']);
            unset($_SESSION['temp_order_payment_method']);
            unset($_SESSION['temp_razorpay_order_id']);
            unset($_SESSION['temp_order_number']);
            unset($_SESSION['cod_advance']);
            unset($_SESSION['cod_remaining']);
            unset($_SESSION['cart']); // Clear cart after successful order
            unset($_SESSION['promotion_code']); // Clear promotion code

            $response = [
                'success' => true,
                'order_id' => $order_id,
                'order_number' => $order_number,
                'message' => 'Order saved successfully'
            ];

            // If this is being called directly, output JSON
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                echo json_encode($response);
            } else {
                // Return data for verify-payment.php
                return $response;
            }
        } else {
            throw new Exception('Failed to save order: ' . $stmt->error);
        }
    } catch (Exception $e) {
        $error_response = [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            echo json_encode($error_response);
        } else {
            return $error_response;
        }
    }
} else {
    $response = [
        'success' => false,
        'message' => 'Invalid request method or no order data'
    ];

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        echo json_encode($response);
    } else {
        return $response;
    }
}
