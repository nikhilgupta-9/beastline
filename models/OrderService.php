<?php
include_once(__DIR__ . "/../config/ithink-api.php");

class OrderService
{
    private $conn;
    private $site;

    public function __construct($connection, $baseUrl)
    {
        $this->conn = $connection;
        $this->site = $baseUrl;
    }

    /**
     * Generate unique order number
     */
    public function generateOrderNumber()
    {
        return 'ORD' . strtoupper(uniqid());
    }

    /**
     * Create or get user from session data
     */
    public function getOrCreateUser($userData, $password = null)
    {
        // Check if user exists by email
        $email = $userData['email'];

        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Existing user
            $user = $result->fetch_assoc();
            return $user['id'];
        } else {
            // Create new user
            if (!$password) {
                $password = bin2hex(random_bytes(8)); // Generate random password
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (
                name, first_name, last_name, mobile, email, password,
                address, city, state, zip_code, user_type, status,
                email_verified, newsletter_subscribed, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'customer', 'active', 1, 0, NOW())";

            $fullName = $userData['first_name'] . ' ' . $userData['last_name'];

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                "ssssssssss",
                $fullName,
                $userData['first_name'],
                $userData['last_name'],
                $userData['phone'],
                $userData['email'],
                $hashedPassword,
                $userData['address_1'],
                $userData['city'],
                $userData['state'],
                $userData['postcode']
            );

            if ($stmt->execute()) {
                return $stmt->insert_id;
            }
        }

        return null;
    }

    /**
     * Create main order record
     */
    public function createOrder($orderData, $userId, $paymentMethod, $razorpayOrderId = null, $isCodAdvance = false)
    {
        $orderNumber = $this->generateOrderNumber();

        // Check if tax amount exists in orderData, otherwise set to 0
        $taxAmount = $orderData['tax'] ?? 0;
        
        // Determine payment status
        $paymentStatus = 'pending';
        if ($paymentMethod === 'razorpay' && !$isCodAdvance) {
            $paymentStatus = 'paid'; // Full payment via Razorpay
        } elseif ($paymentMethod === 'cod' || ($paymentMethod === 'razorpay' && $isCodAdvance)) {
            $paymentStatus = 'partially_paid'; // COD with advance or partial payment
        }

        $sql = "INSERT INTO orders (
            user_id, order_number, total_amount, discount_amount,
            shipping_amount, tax_amount, final_amount, razorpay_order_id,
            payment_method, payment_status, order_status, shipping_address,
            billing_address, notes, advance_paid, cod_remaining, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $shippingAddress = json_encode([
            'name' => $orderData['billing_first_name'] . ' ' . $orderData['billing_last_name'],
            'phone' => $orderData['billing_phone'],
            'email' => $orderData['billing_email'],
            'address' => $orderData['billing_address_1'],
            'address2' => $orderData['billing_address_2'] ?? '',
            'city' => $orderData['billing_city'],
            'state' => $orderData['billing_state'],
            'country' => $orderData['billing_country'],
            'postcode' => $orderData['billing_postcode']
        ]);

        $billingAddress = $shippingAddress; // Same as shipping for now

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return null;
        }

        // Store all values in variables for bind_param
        $subtotal = floatval($orderData['subtotal']);
        $discount = floatval($orderData['discount']);
        $shippingFee = floatval($orderData['shipping_fee']);
        $finalTotal = floatval($orderData['total']);
        $notes = $orderData['order_note'] ?? '';
        
        // COD advance calculation
        $advancePaid = 0;
        $codRemaining = $finalTotal;
        
        if ($paymentMethod === 'cod' || ($paymentMethod === 'razorpay' && $isCodAdvance)) {
            $advancePaid = 200.00; // Fixed advance amount
            $codRemaining = $finalTotal - $advancePaid;
        }

        // Convert empty string to NULL for Razorpay order ID
        $razorpayOrderId = empty($razorpayOrderId) ? null : $razorpayOrderId;

        // Fix: Bind parameters - 17 placeholders now
        $stmt->bind_param(
            "isddddssssssdds", // 17 parameters
            $userId,                    // i
            $orderNumber,               // s
            $subtotal,                  // d
            $discount,                  // d
            $shippingFee,               // d
            $taxAmount,                 // d
            $finalTotal,                // d
            $razorpayOrderId,           // s
            $paymentMethod,             // s
            $paymentStatus,             // s (changed from hardcoded)
            $orderData['order_status'] ?? 'pending', // s
            $shippingAddress,           // s
            $billingAddress,            // s
            $notes,                     // s
            $advancePaid,              // d
            $codRemaining              // d
        );

        if ($stmt->execute()) {
            $orderId = $stmt->insert_id;
            
            // Insert order items with SKU
            if (!empty($orderData['items'])) {
                $this->addOrderItems($orderId, $orderData['items']);
            }
            
            error_log("Order created successfully. Order ID: $orderId, Payment Status: $paymentStatus, Advance Paid: $advancePaid");
            
            return [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'payment_status' => $paymentStatus,
                'advance_paid' => $advancePaid,
                'cod_remaining' => $codRemaining
            ];
        } else {
            error_log("SQL Error: " . $stmt->error);
            return null;
        }
    }
    
    /**
     * Add order items with SKU
     */
    public function addOrderItems($orderId, $items)
    {
        $sql = "INSERT INTO order_items (
            order_id, product_id, variant_id, product_name, 
            quantity, unit_price, total_price, size, color, sku
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare failed for order items: " . $this->conn->error);
            return false;
        }
        
        foreach ($items as $item) {
            // Get SKU from product or variant
            $sku = $this->getProductSku($item['product_id'], $item['variant_id'] ?? 0);
            
            $stmt->bind_param(
                "iiisiddsss",
                $orderId,
                $item['product_id'],
                $item['variant_id'] ?? 0,
                $item['product_name'],
                $item['quantity'],
                $item['unit_price'],
                $item['total_price'],
                $item['size'] ?? '',
                $item['color'] ?? '',
                $sku
            );
            
            if (!$stmt->execute()) {
                error_log("Failed to insert order item: " . $stmt->error);
            }
        }
        
        return true;
    }
    
    /**
     * Get product SKU
     */
    public function getProductSku($productId, $variantId = 0)
    {
        if ($variantId > 0) {
            $sql = "SELECT sku FROM product_variants WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $variantId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                return $row['sku'];
            }
        }
        
        $sql = "SELECT pro_sku FROM products WHERE pro_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return $row['pro_sku'];
        }
        
        return '';
    }
    
    /**
     * Update order payment status
     */
    public function updatePaymentStatus($orderId, $status, $paymentId = null, $signature = null)
    {
        $sql = "UPDATE orders SET 
                payment_status = ?,
                razorpay_payment_id = ?,
                razorpay_signature = ?,
                updated_at = NOW()
                WHERE order_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssi", $status, $paymentId, $signature, $orderId);
        return $stmt->execute();
    }

    /**
     * Process COD advance payment
     */
    public function processCODAdvance($orderId)
    {
        return $this->updatePaymentStatus($orderId, 'cod_advance_paid');
    }

    /**
     * Complete full payment
     */
    public function completePayment($orderId, $paymentId = null, $signature = null)
    {
        return $this->updatePaymentStatus($orderId, 'paid', $paymentId, $signature);
    }

    /**
     * Create iThink Logistics shipment
     */
    public function createShipment($orderId)
    {
        include_once __DIR__ . '/../config/ithink-api.php';

        // Get order details
        $sql = "SELECT o.*, u.mobile, u.email, u.first_name, u.last_name 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.order_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if (!$order) return null;

        // Prepare shipment data
        $address = json_decode($order['shipping_address'], true);

        $shipmentData = [
            'access_token' => iThinkConfig::ACCESS_TOKEN,
            'secret_key' => iThinkConfig::SECRET_KEY,
            'pickup_address_id' => iThinkConfig::PICKUP_ADDRESS_ID,
            'consignee_name' => $address['name'],
            'consignee_address' => $address['address'] . ' ' . $address['address2'],
            'consignee_city' => $address['city'],
            'consignee_state' => $address['state'],
            'consignee_country' => $address['country'],
            'consignee_pincode' => $address['postcode'],
            'consignee_phone' => $order['mobile'],
            'consignee_email' => $order['email'],
            'order_id' => $order['order_number'],
            'invoice_value' => $order['final_amount'],
            'cod_amount' => ($order['payment_method'] === 'cod') ? $order['final_amount'] : 0,
            'payment_type' => ($order['payment_method'] === 'cod') ? 'cod' : 'prepaid',
            'product_name' => 'Products',
            'quantity' => 1,
            'weight' => 1,
            'length' => 10,
            'breadth' => 10,
            'height' => 10
        ];

        // Call iThink API
        $ch = curl_init(iThinkConfig::API_URL . 'order/create.json');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($shipmentData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if (isset($result['success']) && $result['success']) {
            // Update order with tracking info
            $updateSql = "UPDATE orders SET 
                         tracking_number = ?,
                         awb_number = ?,
                         courier_name = ?,
                         shipment_data = ?,
                         ithink_order_id = ?,
                         order_status = 'shipped',
                         updated_at = NOW()
                         WHERE order_id = ?";

            $stmt = $this->conn->prepare($updateSql);
            $stmt->bind_param(
                "sssssi",
                $result['data']['tracking_id'],
                $result['data']['awb_number'],
                $result['data']['courier_name'],
                $response,
                $result['data']['order_id'],
                $orderId
            );

            if ($stmt->execute()) {
                return $result['data'];
            }
        }

        return null;
    }

    public function getOrderById($orderId)
    {
        $sql = "SELECT * FROM orders WHERE order_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }



    /**
     * Get latest tracking data from iThink or database
     */
    private function getLatestTrackingData($orderId)
    {
        // First check shipment_tracking table
        $sql = "SELECT * FROM shipment_tracking 
                WHERE order_id = ? 
                ORDER BY updated_at DESC LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $tracking = $stmt->get_result()->fetch_assoc();

        if ($tracking && !empty($tracking['tracking_data'])) {
            $data = json_decode($tracking['tracking_data'], true);
            if (isset($data['status'])) {
                return $data;
            }
        }

        // If no tracking data, fetch from iThink API
        return $this->fetchTrackingFromIThink($orderId);
    }

    /**
     * Fetch tracking from iThink Logistics
     */
    private function fetchTrackingFromIThink($orderId)
    {
        require_once __DIR__ . '/../config/ithink-api.php';

        // Get tracking number from order
        $sql = "SELECT tracking_number FROM orders WHERE order_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if (!$order || empty($order['tracking_number'])) {
            return null;
        }

        // Call iThink API
        $data = [
            'access_token' => iThinkConfig::ACCESS_TOKEN,
            'secret_key' => iThinkConfig::SECRET_KEY,
            'tracking_id' => $order['tracking_number']
        ];

        $ch = curl_init(iThinkConfig::API_URL . 'order/track.json');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if (isset($result['success']) && $result['success']) {
            // Store in database
            $this->storeTrackingData($orderId, $result['data']);
            return $result['data'];
        }

        return null;
    }

    /**
     * Store tracking data in database
     */
    private function storeTrackingData($orderId, $trackingData)
    {
        // Update shipment_tracking table
        $sql = "INSERT INTO shipment_tracking 
                (order_id, awb_number, courier_name, tracking_data, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                tracking_data = VALUES(tracking_data),
                status = VALUES(status),
                updated_at = NOW()";

        $stmt = $this->conn->prepare($sql);

        $awbNumber = $trackingData['awb_number'] ?? '';
        $courierName = $trackingData['courier_name'] ?? '';
        $status = $trackingData['status'] ?? '';
        $trackingJson = json_encode($trackingData);

        $stmt->bind_param(
            "issss",
            $orderId,
            $awbNumber,
            $courierName,
            $trackingJson,
            $status
        );

        $stmt->execute();

        // Store individual history entries
        if (isset($trackingData['tracking_history']) && is_array($trackingData['tracking_history'])) {
            foreach ($trackingData['tracking_history'] as $history) {
                $this->storeShipmentHistory($orderId, $history);
            }
        }
    }

    /**
     * Store shipment history
     */
    private function storeShipmentHistory($orderId, $historyData)
    {
        $sql = "INSERT INTO shipment_history 
                (order_id, status, location, remarks, date_time, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                location = VALUES(location),
                remarks = VALUES(remarks),
                date_time = VALUES(date_time)";

        $stmt = $this->conn->prepare($sql);

        $status = $historyData['status'] ?? '';
        $location = $historyData['location'] ?? '';
        $remarks = $historyData['remarks'] ?? '';
        $dateTime = $historyData['date_time'] ?? date('Y-m-d H:i:s');

        $stmt->bind_param(
            "issss",
            $orderId,
            $status,
            $location,
            $remarks,
            $dateTime
        );

        $stmt->execute();
    }



    /**
     * Get shipment history timeline
     */
    public function getShipmentTimeline($orderId)
    {
        $sql = "SELECT * FROM shipment_history 
                WHERE order_id = ? 
                ORDER BY date_time DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Clear cart sessions
     */
    public function clearSessions()
    {
        if (isset($_SESSION['cart'])) unset($_SESSION['cart']);
        if (isset($_SESSION['promotion_code'])) unset($_SESSION['promotion_code']);
        if (isset($_SESSION['buy_now'])) unset($_SESSION['buy_now']);
    }

    /**
     * Get order progress with visual steps
     */
    public function getOrderProgress($orderId)
    {
        // Get order details
        $sql = "SELECT * FROM orders WHERE order_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if (!$order) {
            return null;
        }

        $orderStatus = $order['order_status'];

        // Define all possible steps in order
        $allSteps = [
            'ordered' => [
                'title' => 'Order Placed',
                'icon' => 'fa-shopping-cart',
                'description' => 'Your order has been received'
            ],
            'confirmed' => [
                'title' => 'Order Confirmed',
                'icon' => 'fa-check-circle',
                'description' => 'We have confirmed your order'
            ],
            'processing' => [
                'title' => 'Processing',
                'icon' => 'fa-cog',
                'description' => 'Your order is being prepared'
            ],
            'shipped' => [
                'title' => 'Shipped',
                'icon' => 'fa-shipping-fast',
                'description' => 'Your order has been dispatched'
            ],
            'out_for_delivery' => [
                'title' => 'Out for Delivery',
                'icon' => 'fa-truck',
                'description' => 'Your order is on its way'
            ],
            'delivered' => [
                'title' => 'Delivered',
                'icon' => 'fa-home',
                'description' => 'Order delivered successfully'
            ]
        ];

        // Check if order is cancelled
        if ($orderStatus === 'cancelled') {
            $steps = array_slice($allSteps, 0, 2); // Show only ordered and confirmed
            $steps['cancelled'] = [
                'title' => 'Cancelled',
                'icon' => 'fa-times-circle',
                'description' => 'Order has been cancelled'
            ];
        } else {
            $steps = $allSteps;
        }

        // Get status order for progress calculation
        $statusOrder = array_keys($steps);
        $currentStatusIndex = array_search($orderStatus, $statusOrder);

        // If status not found, default to first
        if ($currentStatusIndex === false) {
            $currentStatusIndex = 0;
        }

        // Build progress steps
        $progressSteps = [];
        $i = 0;
        foreach ($steps as $status => $step) {
            $isActive = ($i == $currentStatusIndex);
            $isCompleted = ($i < $currentStatusIndex);

            $progressSteps[] = [
                'status' => $status,
                'title' => $step['title'],
                'icon' => $step['icon'],
                'description' => $step['description'],
                'is_active' => $isActive,
                'is_completed' => $isCompleted,
                'date' => $order['created_at'] // Use order date as default
            ];
            $i++;
        }

        // Calculate percentage
        $percentage = 0;
        if ($orderStatus === 'cancelled') {
            $percentage = 100;
        } elseif ($currentStatusIndex >= 0) {
            $percentage = round(($currentStatusIndex + 1) / count($statusOrder) * 100);
        }

        return [
            'progress' => $progressSteps,
            'current_status' => $orderStatus,
            'percentage' => $percentage
        ];
    }
}
