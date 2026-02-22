<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/connect.php';
require_once __DIR__ . '/../config/ithink-api.php'; 

$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$forceSync = isset($_GET['force']) && $_GET['force'] == 1; 
$skipEmail = isset($_GET['skip_email']) && $_GET['skip_email'] == 1; 

if ($orderId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
}

try {
    $conn->begin_transaction();

    $orderRes = $conn->query("
        SELECT * FROM orders 
        WHERE order_id = {$orderId} 
        FOR UPDATE
    ");

    $order = $orderRes->fetch_assoc();

    if (!$order) {
        $conn->commit();
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    // Decode addresses
    $billing = json_decode($order['billing_address'], true);
    $shipping = json_decode($order['shipping_address'], true);

    if (!$billing) {
        $billing = [];
    }
    
    if (!$shipping) {
        $shipping = [];
    }

    $email = $billing['email'] ?? '';
    $name = $billing['name'] ?? 'Customer';

    $emailResult = ['success' => false, 'message' => 'Email skipped'];
    $logisticsResult = ['success' => false, 'message' => '', 'tracking_number' => null, 'courier_name' => null, 'refnum' => null];
    $total = $order['final_amount'];

    if (!$skipEmail && ($order['email_status'] === 'pending' || $forceSync)) {
        // Build email data
        $emailData = buildEmailData($orderId, $conn);

        $emailResult = sendMail($email, $name, $emailData);
        
        if ($emailResult['success']) {
            $conn->query("UPDATE orders SET 
                email_status = 'sent', 
                email_sent_at = NOW() 
                WHERE order_id = {$orderId}");
        } else {
            $conn->query("UPDATE orders SET 
                email_status = 'failed',
                email_error = '" . mysqli_real_escape_string($conn, $emailResult['message']) . "'
                WHERE order_id = {$orderId}");
        }
    } else {
        $emailResult['success'] = true;
        $emailResult['message'] = 'Email already sent or skipped';
    }

    if ($total > 0 && ($order['logistics_sync_status'] !== 'synced' || $forceSync)) {
        $logisticsResult = syncToIthink($orderId, $order, $billing, $shipping, $conn);
        
        error_log("Logistics sync result for order #{$orderId}: " . json_encode($logisticsResult));
        
        if ($logisticsResult['success']) {
            $updateFields = ["logistics_sync_status = 'synced'"];
            
            if (!empty($logisticsResult['refnum'])) {
                $updateFields[] = "ithink_order_id = '" . mysqli_real_escape_string($conn, $logisticsResult['refnum']) . "'";
            }
            
            if (!empty($logisticsResult['tracking_number'])) {
                $updateFields[] = "tracking_number = '" . mysqli_real_escape_string($conn, $logisticsResult['tracking_number']) . "'";
                $updateFields[] = "awb_number = '" . mysqli_real_escape_string($conn, $logisticsResult['tracking_number']) . "'";
            }
            
            if (!empty($logisticsResult['courier_name'])) {
                $updateFields[] = "courier_name = '" . mysqli_real_escape_string($conn, $logisticsResult['courier_name']) . "'";
            }
            
            $updateFields[] = "logistics_sync_error = NULL";
            
            $conn->query("UPDATE orders SET " . implode(', ', $updateFields) . " WHERE order_id = {$orderId}");
        } else {
            $conn->query("UPDATE orders SET 
                logistics_sync_status = 'failed',
                logistics_sync_error = '" . mysqli_real_escape_string($conn, substr($logisticsResult['message'], 0, 500)) . "'
                WHERE order_id = {$orderId}");
        }
    } else {
        if ($total <= 0) {
            $logisticsResult['message'] = 'Logistics sync skipped - zero total';
        } else if ($order['logistics_sync_status'] === 'synced' && !$forceSync) {
            $logisticsResult['success'] = true;
            $logisticsResult['message'] = 'Logistics already synced';
            // Get existing tracking info
            $logisticsResult['tracking_number'] = $order['tracking_number'];
            $logisticsResult['courier_name'] = $order['courier_name'];
            $logisticsResult['refnum'] = $order['ithink_order_id'];
        }
    }
    
    $conn->commit();

    if ($emailResult['success'] && !$skipEmail && isset($emailData)) {
        saveEmailCopy($orderId, $email, $emailData);
    }

    $response = [
        'success' => true,
        'email_status' => $emailResult['success'] ? 'sent' : 'failed',
        'email_message' => $emailResult['message'],
        'logistics_status' => $logisticsResult['success'] ? 'synced' : ($logisticsResult['message'] ? 'failed' : 'skipped')
    ];

    if ($logisticsResult['success']) {
        if (!empty($logisticsResult['refnum'])) {
            $response['refnum'] = $logisticsResult['refnum'];
        }
        if (!empty($logisticsResult['tracking_number'])) {
            $response['tracking_number'] = $logisticsResult['tracking_number'];
            $response['courier_name'] = $logisticsResult['courier_name'];
        }
    }
    
    if (!empty($logisticsResult['message'])) {
        $response['logistics_message'] = $logisticsResult['message'];
    }

    echo json_encode($response);

} catch (Exception $e) {
    $conn->rollback();
    
    if (isset($orderId)) {
        $conn->query("UPDATE orders SET 
            email_status = 'failed', 
            logistics_sync_error = '" . mysqli_real_escape_string($conn, $e->getMessage()) . "' 
            WHERE order_id = {$orderId}");
        $conn->commit();
    }
    
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}


/**
 * Sync order to iThink Logistics API - FIXED VERSION
 */
function syncToIthink($orderId, $order, $billing, $shipping, $conn) {
    $result = ['success' => false, 'message' => '', 'tracking_number' => null, 'courier_name' => null, 'refnum' => null];
    
    try {
        // Get order items with product details from products table
        $itemsRes = $conn->query("
            SELECT oi.*, p.pro_name, p.sku, p.weight
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.pro_id
            WHERE oi.order_id = {$orderId}
        ");

        if (!$itemsRes) {
            throw new Exception("Failed to fetch order items: " . $conn->error);
        }

        $products = [];
        $totalWeight = 0;
        
        while ($item = $itemsRes->fetch_assoc()) {
            $attr = json_decode($item['attributes'], true) ?? [];
            
            // Get SKU from products table or generate one
            $sku = $item['sku']."-".$attr['size']  ?? '';
            if (empty($sku)) {
                $sku = 'PRD-' . str_pad($item['product_id'], 5, '0', STR_PAD_LEFT);
            }
            
            // Get price - use unit_price from order_items
            $price = $item['unit_price'] ?? 0;
            if ($price == 0 && isset($item['total_price']) && $item['quantity'] > 0) {
                $price = $item['total_price'] / $item['quantity'];
            }

            // Get weight from products table or use default
            $itemWeight = floatval($item['weight'] ?? 0.3);
            if ($itemWeight <= 0) {
                $itemWeight = 0.3; // Default 300g per item
            }
            
            $totalWeight += ($item['quantity'] * $itemWeight);

            // Get product name from order_items (already has product_name)
            $productName = $item['product_name'] ?? 'Product';
            
            // Add size/color to product name if available
            $size = $attr['size'] ?? '';
            $color = $attr['color'] ?? '';
            if (!empty($size) || !empty($color)) {
                $productName .= ' (';
                if (!empty($size)) $productName .= "Size: $size";
                if (!empty($size) && !empty($color)) $productName .= ', ';
                if (!empty($color)) $productName .= "Color: $color";
                $productName .= ')';
            }

            $products[] = [
                'product_name' => substr($productName, 0, 100),
                'product_sku' => substr($sku, 0, 50),
                'product_quantity' => (string)($item['quantity'] ?? 1),
                'product_price' => number_format($price, 2, '.', ''),
                'product_tax_rate' => $attr['tax_rate'] ?? '5',
                'product_hsn_code' => $attr['hsn_code'] ?? '91308',
                'product_discount' => '0'
            ];
        }

        // If shipping is empty, use billing
        if (empty($shipping)) {
            $shipping = $billing;
        }

        // Extract shipping address (handle different possible field names)
        $shippingAddress = $shipping['address'] ?? $shipping['address_1'] ?? $shipping['street'] ?? '';
        $shippingAddress2 = $shipping['address2'] ?? $shipping['address_2'] ?? $shipping['landmark'] ?? '';
        $shippingCity = $shipping['city'] ?? '';
        $shippingState = $shipping['state'] ?? '';
        $shippingPostcode = preg_replace('/[^0-9]/', '', $shipping['postcode'] ?? $shipping['pincode'] ?? $shipping['zip'] ?? '');
        
        $consigneeName = $shipping['name'] ?? $shipping['full_name'] ?? $billing['name'] ?? 'Customer';
        $consigneePhone = $shipping['phone'] ?? $shipping['mobile'] ?? $billing['phone'] ?? $billing['mobile'] ?? '';
        $consigneeEmail = $billing['email'] ?? '';

        // Extract billing address
        $billingAddress = $billing['address'] ?? $billing['address_1'] ?? $billing['street'] ?? $shippingAddress;
        $billingAddress2 = $billing['address2'] ?? $billing['address_2'] ?? $billing['landmark'] ?? $shippingAddress2;
        $billingCity = $billing['city'] ?? $shippingCity;
        $billingState = $billing['state'] ?? $shippingState;
        $billingPostcode = preg_replace('/[^0-9]/', '', $billing['postcode'] ?? $billing['pincode'] ?? $billing['zip'] ?? $shippingPostcode);
        
        $billingName = $billing['name'] ?? $billing['full_name'] ?? $consigneeName;
        $billingPhone = $billing['phone'] ?? $billing['mobile'] ?? $consigneePhone;
        $billingEmail = $billing['email'] ?? $consigneeEmail;

        // Validate required fields
        $errors = [];
        if (empty($consigneeName)) $errors[] = "Customer name";
        if (empty($shippingAddress)) $errors[] = "Shipping address";
        if (empty($shippingCity)) $errors[] = "City";
        if (empty($shippingState)) $errors[] = "State";
        if (empty($shippingPostcode)) $errors[] = "Pincode";
        if (strlen($shippingPostcode) !== 6) $errors[] = "Pincode must be 6 digits";
        if (empty($consigneePhone)) $errors[] = "Phone number";
        
        if (!empty($errors)) {
            throw new Exception("Missing required fields: " . implode(', ', $errors));
        }

        // Check if billing is same as shipping
        $isBillingSame = 'yes';
        if (
            $billingAddress != $shippingAddress ||
            $billingCity != $shippingCity ||
            $billingState != $shippingState ||
            $billingPostcode != $shippingPostcode
        ) {
            $isBillingSame = 'no';
        }

        $orderWeight = max(0.5, $totalWeight); // Minimum 0.5kg

        // 1️⃣ Detect COD or Prepaid
        $is_cod = ($order['payment_method'] === 'COD');
        $payment_mode = $is_cod ? 'COD' : 'PREPAID';
        
        // 2️⃣ Amounts (string, no commas – iThink safe)
        $final_amount = number_format((float)($order['final_amount'] ?? 0), 2, '.', '');
        $shipping_amount = number_format((float)($order['shipping_amount'] ?? 0), 2, '.', '');
        $discount_amount = number_format((float)($order['discount_amount'] ?? 0), 2, '.', '');
        
        // 3️⃣ COD vs Prepaid sync (THIS IS THE KEY PART)
        if ($is_cod) {
            // COD order
            $cod_amount = $final_amount;     // 👈 full amount COD
            $advance_amount = '0';           // 👈 nothing prepaid
            $cod_charges = '0';              // 👈 your COD fee
            $total_amount = $cod_amount + $advance_amount;
        } else {
            // Prepaid order
            $cod_amount = '0';               // 👈 no COD
            $advance_amount = $final_amount; // 👈 fully paid
            $cod_charges = '0';
            $total_amount = $cod_amount + $advance_amount;
        }
        
        $orderDate = date('d-m-Y H:i:s', strtotime($order['created_at'] ?? 'now'));

        // Use credentials from config
        $access_token = iThinkConfig::ACCESS_TOKEN;
        $secret_key = iThinkConfig::SECRET_KEY;

        // IMPORTANT: Build the EXACT payload structure as per iThink documentation
        $shipment = [
            'order' => (string)$order['order_number'],
            'sub_order' => '',
            'order_date' => $orderDate,
            'total_amount' => $total_amount,
            'name' => substr($consigneeName, 0, 100),
            'company_name' => '',
            'add' => substr($shippingAddress, 0, 200),
            'add2' => substr($shippingAddress2, 0, 200),
            'add3' => '',
            'pin' => $shippingPostcode,
            'city' => substr($shippingCity, 0, 50),
            'state' => substr($shippingState, 0, 50),
            'country' => $shipping['country'] ?? $billing['country'] ?? 'India',
            'phone' => substr($consigneePhone, 0, 15),
            'alt_phone' => '',
            'email' => substr($consigneeEmail, 0, 100),
            'is_billing_same_as_shipping' => $isBillingSame,
            'billing_name' => substr($billingName, 0, 100),
            'billing_company_name' => '',
            'billing_add' => substr($billingAddress, 0, 200),
            'billing_add2' => substr($billingAddress2, 0, 200),
            'billing_add3' => '',
            'billing_pin' => $billingPostcode,
            'billing_city' => substr($billingCity, 0, 50),
            'billing_state' => substr($billingState, 0, 50),
            'billing_country' => $billing['country'] ?? 'India',
            'billing_phone' => substr($billingPhone, 0, 15),
            'billing_alt_phone' => '',
            'billing_email' => substr($billingEmail, 0, 100),
            'products' => $products,
            'shipment_length' => '15',
            'shipment_width' => '10',
            'shipment_height' => '5',
            'weight' => number_format($orderWeight, 2, '.', ''),
            'shipping_charges' =>  $shipping_amount,
            'giftwrap_charges' => '0',
            'transaction_charges' => '0',
            'total_discount' =>  $discount_amount,
            'first_attemp_discount' => '0',
            'cod_charges'         => $cod_charges,
            'cod_amount'          => $cod_amount,
            'advance_amount'      => $advance_amount,           // ← FIXED: Now has value for COD
            'payment_mode'        => $payment_mode,       // ← FIXED: Now 'COD' or 'Prepaid'
            'reseller_name' => '',
            'eway_bill_number' => '',
            'gst_number' => ''
        ];

       // Final payload structure - MUST match exactly as in docs
        $shipment['pickup_address'] = iThinkConfig::PICKUP_ADDRESS_ID;
        $payload = [
            'data' => [
                'shipments' => [$shipment],
                'access_token' => $access_token,
                'secret_key' => $secret_key,
            ]
        ];

        // Convert to JSON with proper formatting
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        
        if ($jsonPayload === false) {
            throw new Exception("Failed to encode payload: " . json_last_error_msg());
        }

        // Log payload for debugging
        error_log("========== ITHINK PAYLOAD FOR ORDER #{$order['order_number']} ==========");
        error_log($jsonPayload);
        error_log("==========================================");

        // Initialize cURL with proper headers
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => iThinkConfig::API_URL . "order/sync.json",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Accept: application/json",
                "Content-Length: " . strlen($jsonPayload)
            ]
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        // Log response
        error_log("========== ITHINK RESPONSE FOR ORDER #{$order['order_number']} ==========");
        error_log("HTTP Code: " . $httpCode);
        error_log("Response: " . $response);
        error_log("============================================");

        if ($err) {
            throw new Exception("cURL Error: " . $err);
        }

        if (empty($response)) {
            throw new Exception("Empty response from iThink API");
        }

        // Parse response
        $apiResult = json_decode($response, true);
        
        if (!$apiResult) {
            throw new Exception("Invalid JSON response: " . substr($response, 0, 200));
        }

        // Check response based on iThink API format
        // The API might return different response structures
        if (isset($apiResult['status']) && $apiResult['status'] === 'success') {
            // Success response with data
            if (isset($apiResult['data']) && is_array($apiResult['data'])) {
                $shipmentResult = reset($apiResult['data']);
                
                if (!empty($shipmentResult['awb_number'])) {
                    $result['success'] = true;
                    $result['tracking_number'] = $shipmentResult['awb_number'];
                    $result['courier_name'] = $shipmentResult['courier_name'] ?? $shipmentResult['logistic_name'] ?? 'iThink';
                    $result['refnum'] = $shipmentResult['refnum'] ?? '';
                    $result['message'] = 'Order synced successfully';
                } else {
                    $result['success'] = true;
                    $result['refnum'] = $shipmentResult['refnum'] ?? '';
                    $result['message'] = 'Order synced but AWB pending';
                }
            }
        } elseif (isset($apiResult['status_code']) && $apiResult['status_code'] == 200) {
            // Alternative response format
            if (isset($apiResult['data']) && is_array($apiResult['data'])) {
                $shipmentResult = reset($apiResult['data']);
                
                if (isset($shipmentResult['status']) && $shipmentResult['status'] === 'Success') {
                    $result['success'] = true;
                    $result['refnum'] = $shipmentResult['refnum'] ?? '';
                    $result['message'] = $shipmentResult['remark'] ?? 'Order synced successfully';
                } else {
                    $errorMsg = $shipmentResult['remark'] ?? 'Unknown error';
                    throw new Exception($errorMsg);
                }
            } elseif (!empty($apiResult['html_message'])) {
                // Check for html_message which might contain errors
                throw new Exception($apiResult['html_message']);
            } else {
                $result['success'] = true;
                $result['message'] = 'Order synced but no data in response';
            }
        } else {
            // Error response
            $errorMsg = $apiResult['message'] ?? $apiResult['html_message'] ?? 'Unknown error';
            if (isset($apiResult['errors'])) {
                $errorMsg .= ': ' . json_encode($apiResult['errors']);
            }
            throw new Exception($errorMsg);
        }

    } catch (Exception $e) {
        $result['message'] = $e->getMessage();
        error_log("❌ iThink sync error for order #{$order['order_number']}: " . $e->getMessage());
    }

    return $result;
}

/**
 * Send email using PHP's mail() function
 */
function sendMail($to, $name, $data) {
    $result = ['success' => false, 'message' => '', 'error' => null];
    
    try {
        $subject = "Order Confirmation - #{$data['order_number']} from Beastline";
        $message = generateEmailTemplate($name, $data);
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: Beastline <noreply@beastline.in>\r\n";
        $headers .= "Reply-To: support@beastline.in\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers .= "Return-Path: noreply@beastline.in\r\n";
        
        $params = "-f noreply@beastline.in";
        
        error_log("Attempting to send email to: $to for order #{$data['order_number']}");
        
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
                error_log("❌ Email failed for order #{$data['order_number']}: " . ($error['message'] ?? 'Unknown error'));
            }
        } else {
            $result['error'] = "mail() function does not exist";
            $result['message'] = "PHP mail function not available";
        }
        
    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
        $result['message'] = "Exception occurred";
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
        
        // Default image
        $img = "https://beastline.in/assets/img/logo/logo.png";
        
        if (!empty($item['image'])) {
            $img = "https://beastline.in/admin/assets/img/uploads/" . $item['image'];
        }
        
        $itemsHtml .= "
        <tr>
            <td style='padding:15px 0; border-bottom:1px solid #eaeaea;'>
                <table width='100%' cellpadding='0' cellspacing='0'>
                    <tr>
                        <td width='90'>
                            <img src='{$img}' width='80' style='border-radius:8px; border:1px solid #eee;' 
                                 onerror=\"this.onerror=null; this.src='https://beastline.in/assets/img/logo/logo.png';\"
                            />
                        </td>
                        <td style='padding-left:15px;'>
                            <div style='font-size:14px; font-weight:600; color:#222;'>{$productName}</div>
                            <div style='font-size:12px; color:#777; margin-top:4px;'>Size: {$size} | Color: {$color}</div>
                            <div style='font-size:12px; color:#555; margin-top:6px;'>Quantity: {$quantity}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>";
    }
    
    $shipping = $order['shipping_address'] ?? [];
    $shippingName = htmlspecialchars($shipping['name'] ?? $name);
    $shippingAddress = htmlspecialchars($shipping['address'] ?? $shipping['address_1'] ?? 'N/A');
    $shippingCity = htmlspecialchars($shipping['city'] ?? 'N/A');
    $shippingState = htmlspecialchars($shipping['state'] ?? 'N/A');
    $shippingPostcode = htmlspecialchars($shipping['postcode'] ?? $shipping['pincode'] ?? 'N/A');
    $shippingPhone = htmlspecialchars($shipping['phone'] ?? $shipping['mobile'] ?? 'N/A');
    
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
                        <td><img src="https://beastline.in/assets/img/logo/beastline-logo.png" height="55" alt="Beastline"></td>
                        <td align="right" style="font-size:12px; color:#888;"><b>Order #' . $order['order_number'] . '</b></td>
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
                <a href="https://beastline.in/track-order.php?order_number=' . $order['order_number'] . '" class="button">Track Your Order</a>
            </div>
            
            <div style="padding:10px 25px;">
                <h3 style="font-size:16px; color:#222;">Order Items</h3>
            </div>
            
            <div style="padding:0 25px 20px;">
                <table width="100%" cellpadding="0" cellspacing="0">' . $itemsHtml . '</table>
            </div>
            
            <div style="padding:0 25px 20px;">
                <table width="100%" style="border-top:2px solid #f0f0f0; padding-top:15px;">
                    <tr><td align="right" style="font-size:14px;"><strong>Total Amount: ₹' . $order['order_total'] . '</strong></td></tr>
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
 * Build email data from order
 */
function buildEmailData($orderId, $conn) {
    $order = $conn->query("SELECT * FROM orders WHERE order_id = {$orderId}")->fetch_assoc();

    $itemsRes = $conn->query("
        SELECT oi.*, p.pro_id, p.pro_name, p.sku
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.pro_id
        WHERE oi.order_id = {$orderId}
    ");

    $items = [];
    while ($item = $itemsRes->fetch_assoc()) {
        $attr = json_decode($item['attributes'], true) ?? [];
        
        // Get main product image
        $image = null;
        $imgQuery = $conn->query("
            SELECT image_url FROM product_images 
            WHERE product_id = {$item['product_id']} AND is_main = 1 LIMIT 1
        ");
        
        if ($imgQuery && $imgQuery->num_rows > 0) {
            $imgRow = $imgQuery->fetch_assoc();
            $image = $imgRow['image_url'];
        } else {
            $image = $item['product_image'] ?? null;
        }

        $items[] = [
            'product_id'   => $item['product_id'],
            'product_name' => $item['product_name'],
            'quantity'     => $item['quantity'],
            'size'         => $attr['size'] ?? '-',
            'color'        => $attr['color'] ?? '-',
            'image'        => $image,
            'sku'          => $item['sku'] ?? ('SKU-' . $item['product_id'])
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
 * Save email copy
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