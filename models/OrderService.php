<?php
include_once (__DIR__ . "/../config/ithink-api.php");

class OrderService {
    private $conn;
    private $site;
    
    public function __construct($connection, $baseUrl) {
        $this->conn = $connection;
        $this->site = $baseUrl;
    }
    
    /**
     * Generate unique order number
     */
    public function generateOrderNumber() {
        return 'ORD' . date('Ymd') . strtoupper(uniqid());
    }
    
    /**
     * Create or get user from session data
     */
      public function getOrCreateUser($userData, $password = null) {
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
    public function createOrder($orderData, $userId, $paymentMethod, $razorpayOrderId = null) {
    $orderNumber = $this->generateOrderNumber();
    
    // Check if tax amount exists in orderData, otherwise set to 0
    $taxAmount = $orderData['tax'] ?? 0;
    
    $sql = "INSERT INTO orders (
        user_id, order_number, total_amount, discount_amount,
        shipping_amount, tax_amount, final_amount, razorpay_order_id,
        payment_method, payment_status, order_status, shipping_address,
        billing_address, notes, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, ?, ?, NOW())";
    
    $shippingAddress = json_encode([
        'name' => $orderData['billing_first_name'] . ' ' . $orderData['billing_last_name'],
        'phone' => $orderData['billing_phone'],
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
    
    // Convert empty string to NULL for Razorpay order ID
    $razorpayOrderId = empty($razorpayOrderId) ? null : $razorpayOrderId;
    
    // Debug: Check what we're binding
    error_log("User ID: $userId");
    error_log("Order Number: $orderNumber");
    error_log("Subtotal: $subtotal");
    error_log("Discount: $discount");
    error_log("Shipping Fee: $shippingFee");
    error_log("Tax Amount: $taxAmount");
    error_log("Total: $finalTotal");
    error_log("Razorpay Order ID: " . ($razorpayOrderId ?: 'NULL'));
    error_log("Payment Method: $paymentMethod");
    error_log("Notes: $notes");
    
    // Fix: Bind parameters by reference using variables
    // Count the ? in SQL: we have 13 placeholders
    // Parameter types: i (user_id), s (order_number), ddddd (amounts), sssss (strings)
    $stmt->bind_param(
        "isddddssssss", // 13 placeholders: 1 integer, 1 string, 5 doubles, 6 strings
        $userId,                    // i
        $orderNumber,               // s
        $subtotal,                  // d
        $discount,                  // d
        $shippingFee,               // d
        $taxAmount,                 // d
        $finalTotal,                // d
        $razorpayOrderId,           // s (could be null)
        $paymentMethod,             // s
        $shippingAddress,           // s
        $billingAddress,            // s
        $notes                      // s
    );
    
    if ($stmt->execute()) {
        $orderId = $stmt->insert_id;
        error_log("Order created successfully. Order ID: $orderId");
        return [
            'order_id' => $orderId,
            'order_number' => $orderNumber
        ];
    } else {
        error_log("SQL Error: " . $stmt->error);
        error_log("Full SQL: " . $sql);
        return null;
    }
}
    
    /**
     * Add items to order
     */
     public function addOrderItems($orderId, $items) {
        $sql = "INSERT INTO order_items (
            order_id, product_id, product_name, quantity,
            unit_price, total_price, attributes
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        
        foreach ($items as $item) {
            $attributes = json_encode([
                'color' => $item['color'] ?? '',
                'size' => $item['size'] ?? '',
                'variant_id' => $item['variant_id'] ?? 0
            ]);
            
            $stmt->bind_param(
                "iisidds",
                $orderId,
                $item['product_id'],
                $item['product_name'],
                $item['quantity'],
                $item['unit_price'],
                $item['total_price'],
                $attributes
            );
            
            if (!$stmt->execute()) {
                error_log("Failed to add order item: " . $stmt->error);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Update order payment status
     */
    public function updatePaymentStatus($orderId, $status, $paymentId = null, $signature = null) {
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
    public function processCODAdvance($orderId) {
        return $this->updatePaymentStatus($orderId, 'cod_advance_paid');
    }
    
    /**
     * Complete full payment
     */
    public function completePayment($orderId, $paymentId = null, $signature = null) {
        return $this->updatePaymentStatus($orderId, 'paid', $paymentId, $signature);
    }
    
    /**
     * Create iThink Logistics shipment
     */
    public function createShipment($orderId) {
        require_once __DIR__ . '/../config/ithink-api.php';
        
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
            'access_token' => ACCESS_TOKEN,
            'secret_key' => SECRET_KEY,
            'pickup_address_id' => PICKUP_ADDRESS_ID,
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
        $ch = curl_init(API_URL . 'order/create.json');
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

     public function getOrderById($orderId) {
        $sql = "SELECT * FROM orders WHERE order_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getOrderProgress($orderId) {
        $order = $this->getOrderById($orderId);
        if (!$order) return null;
        
        // Define all possible statuses in order
        $allStatuses = [
            'ordered' => ['title' => 'Ordered', 'icon' => 'fa-shopping-cart'],
            'confirmed' => ['title' => 'Confirmed', 'icon' => 'fa-check-circle'],
            'processing' => ['title' => 'Processing', 'icon' => 'fa-cogs'],
            'shipped' => ['title' => 'Shipped', 'icon' => 'fa-shipping-fast'],
            'out_for_delivery' => ['title' => 'Out for Delivery', 'icon' => 'fa-truck'],
            'delivered' => ['title' => 'Delivered', 'icon' => 'fa-home'],
            'cancelled' => ['title' => 'Cancelled', 'icon' => 'fa-times-circle']
        ];
        
        // Get current order status
        $currentStatus = $order['order_status'];
        
        // Get shipment tracking data if exists
        $shipmentStatus = null;
        $trackingData = $this->getLatestTrackingData($orderId);
        
        if ($trackingData && isset($trackingData['status'])) {
            $shipmentStatus = strtolower($trackingData['status']);
        }
        
        // Determine progress statuses based on order and shipment
        $progress = [];
        $currentStep = 0;
        $stepCount = 0;
        
        foreach ($allStatuses as $status => $details) {
            $stepCount++;
            
            $isActive = false;
            $isCompleted = false;
            
            // Check if this step is completed or active
            if ($status == $currentStatus) {
                $isActive = true;
                $currentStep = $stepCount;
            } elseif ($this->isStatusCompleted($status, $currentStatus)) {
                $isCompleted = true;
                $currentStep = $stepCount + 1;
            }
            
            // Special handling for shipment statuses
            if ($status == 'shipped' && $shipmentStatus && $shipmentStatus == 'out_for_delivery') {
                $isCompleted = true;
            }
            if ($status == 'out_for_delivery' && $shipmentStatus && $shipmentStatus == 'delivered') {
                $isCompleted = true;
            }
            
            // Get status date/time
            $statusDate = $this->getStatusDate($orderId, $status);
            
            $progress[] = [
                'status' => $status,
                'title' => $details['title'],
                'icon' => $details['icon'],
                'is_active' => $isActive,
                'is_completed' => $isCompleted,
                'date' => $statusDate,
                'description' => $this->getStatusDescription($status, $order)
            ];
            
            // If current status is cancelled, break
            if ($status == 'cancelled' && $currentStatus == 'cancelled') {
                break;
            }
        }
        
        return [
            'progress' => $progress,
            'current_step' => $currentStep,
            'total_steps' => count($progress),
            'percentage' => $this->calculateProgressPercentage($currentStep, count($progress)),
            'current_status' => $currentStatus,
            'shipment_status' => $shipmentStatus,
            'tracking_info' => $trackingData
        ];
    }
    
    /**
     * Get latest tracking data from iThink or database
     */
    private function getLatestTrackingData($orderId) {
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
    private function fetchTrackingFromIThink($orderId) {
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
            'access_token' => ITHINK_ACCESS_TOKEN,
            'secret_key' => ITHINK_SECRET_KEY,
            'tracking_id' => $order['tracking_number']
        ];
        
        $ch = curl_init(ITHINK_API_URL . 'order/track.json');
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
    private function storeTrackingData($orderId, $trackingData) {
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
    private function storeShipmentHistory($orderId, $historyData) {
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
     * Check if status is completed
     */
    private function isStatusCompleted($status, $currentStatus) {
        $statusOrder = [
            'ordered' => 1,
            'confirmed' => 2,
            'processing' => 3,
            'shipped' => 4,
            'out_for_delivery' => 5,
            'delivered' => 6,
            'cancelled' => 7
        ];
        
        $currentOrder = $statusOrder[$currentStatus] ?? 0;
        $checkOrder = $statusOrder[$status] ?? 0;
        
        return $checkOrder < $currentOrder;
    }
    
    /**
     * Get date for specific status
     */
    private function getStatusDate($orderId, $status) {
        // Check shipment_history first
        if (in_array($status, ['shipped', 'out_for_delivery', 'delivered'])) {
            $sql = "SELECT MIN(date_time) as date FROM shipment_history 
                    WHERE order_id = ? AND status = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("is", $orderId, $status);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result && $result['date']) {
                return $result['date'];
            }
        }
        
        // Check order updated_at for other statuses
        $sql = "SELECT updated_at FROM orders 
                WHERE order_id = ? AND order_status = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $orderId, $status);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result['updated_at'] ?? null;
    }
    
    /**
     * Get status description
     */
    private function getStatusDescription($status, $order) {
        $descriptions = [
            'ordered' => 'Your order has been placed successfully.',
            'confirmed' => 'We have received your order.',
            'processing' => 'Your order is being processed.',
            'shipped' => 'Your order has been shipped.',
            'out_for_delivery' => 'Your order is out for delivery.',
            'delivered' => 'Your order has been delivered.',
            'cancelled' => 'Your order has been cancelled.'
        ];
        
        $description = $descriptions[$status] ?? '';
        
        // Add tracking info for shipped status
        if ($status == 'shipped' && !empty($order['tracking_number'])) {
            $description .= " Tracking #: " . $order['tracking_number'];
        }
        
        // Add courier info
        if ($status == 'shipped' && !empty($order['courier_name'])) {
            $description .= " via " . $order['courier_name'];
        }
        
        return $description;
    }
    
    /**
     * Calculate progress percentage
     */
    private function calculateProgressPercentage($currentStep, $totalSteps) {
        if ($totalSteps == 0) return 0;
        return min(100, round(($currentStep / $totalSteps) * 100));
    }
    
    /**
     * Get shipment history timeline
     */
    public function getShipmentTimeline($orderId) {
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
    public function clearSessions() {
        if (isset($_SESSION['cart'])) unset($_SESSION['cart']);
        if (isset($_SESSION['promotion_code'])) unset($_SESSION['promotion_code']);
        if (isset($_SESSION['buy_now'])) unset($_SESSION['buy_now']);
    }
}
?>