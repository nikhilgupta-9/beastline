<?php
class iThinkService {
    private $conn;
    private $apiUrl;
    private $accessToken;
    private $secretKey;
    private $pickupAddressId;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->apiUrl = iThinkConfig::API_URL;
        $this->accessToken = iThinkConfig::ACCESS_TOKEN;
        $this->secretKey = iThinkConfig::SECRET_KEY;
        $this->pickupAddressId = iThinkConfig::PICKUP_ADDRESS_ID;
    }
    
    /**
     * Create shipment for an order
     */
    public function createShipment($orderId, $preferredCourier = null) {
        // Get order details
        $order = $this->getOrderWithUser($orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }
        
        // Decode shipping address
        $address = json_decode($order['shipping_address'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'Invalid shipping address'];
        }
        
        // Prepare shipment data
        $shipmentData = [
            'access_token' => $this->accessToken,
            'secret_key' => $this->secretKey,
            'pickup_address_id' => $this->pickupAddressId,
            'consignee_name' => $address['name'] ?? '',
            'consignee_address' => ($address['address'] ?? '') . ' ' . ($address['address2'] ?? ''),
            'consignee_city' => $address['city'] ?? '',
            'consignee_state' => $address['state'] ?? '',
            'consignee_country' => $address['country'] ?? 'India',
            'consignee_pincode' => $address['postcode'] ?? '',
            'consignee_phone' => $order['mobile'] ?? '',
            'consignee_email' => $order['email'] ?? '',
            'order_id' => $order['order_number'],
            'invoice_value' => floatval($order['final_amount']),
            'cod_amount' => ($order['payment_method'] === 'cod') ? floatval($order['final_amount']) : 0,
            'payment_type' => ($order['payment_method'] === 'cod') ? 'cod' : 'prepaid',
            'product_name' => 'Products',
            'quantity' => $this->getOrderItemCount($orderId),
            'weight' => $this->calculateOrderWeight($orderId),
            'length' => 10,
            'breadth' => 10,
            'height' => 10,
            'add' => 1 // Create order immediately
        ];
        
        // Add preferred courier if specified
        if ($preferredCourier && array_key_exists($preferredCourier, iThinkConfig::$availableCouriers)) {
            $shipmentData['preferred_courier'] = $preferredCourier;
        }
        
        // Call iThink API to create order
        $response = $this->callAPI('order/create.json', $shipmentData);
        
        if ($response['success']) {
            // Store shipment details in database
            $this->storeShipmentData($orderId, $response['data']);
            
            // Update order with tracking info
            $this->updateOrderWithTracking($orderId, $response['data']);
            
            return [
                'success' => true,
                'message' => 'Shipment created successfully',
                'data' => $response['data']
            ];
        }
        
        return [
            'success' => false,
            'message' => $response['message'] ?? 'Failed to create shipment'
        ];
    }
    
    /**
     * Get real-time tracking updates
     */
    public function getTrackingUpdates($trackingId) {
        $data = [
            'access_token' => $this->accessToken,
            'secret_key' => $this->secretKey,
            'tracking_id' => $trackingId
        ];
        
        $response = $this->callAPI('order/track.json', $data);
        
        if ($response['success']) {
            // Update tracking in database
            $this->updateTrackingData($trackingId, $response['data']);
            
            return [
                'success' => true,
                'data' => $response['data']
            ];
        }
        
        return [
            'success' => false,
            'message' => $response['message'] ?? 'Tracking not found'
        ];
    }
    
    /**
     * Get available couriers for an order
     */
    public function getAvailableCouriers($orderId) {
        $order = $this->getOrderWithUser($orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }
        
        $address = json_decode($order['shipping_address'], true);
        
        $data = [
            'access_token' => $this->accessToken,
            'secret_key' => $this->secretKey,
            'pickup_address_id' => $this->pickupAddressId,
            'consignee_pincode' => $address['postcode'] ?? '',
            'cod_amount' => ($order['payment_method'] === 'cod') ? floatval($order['final_amount']) : 0,
            'invoice_value' => floatval($order['final_amount']),
            'weight' => $this->calculateOrderWeight($orderId)
        ];
        
        $response = $this->callAPI('order/check_serviceability.json', $data);
        
        if ($response['success'] && isset($response['data']['courier_data'])) {
            $availableCouriers = [];
            foreach ($response['data']['courier_data'] as $courier) {
                if ($courier['serviceable'] == 1) {
                    $availableCouriers[] = [
                        'code' => $courier['courier_id'],
                        'name' => $courier['courier_name'],
                        'estimated_days' => $courier['etd'] ?? 'N/A',
                        'rate' => $courier['rate'] ?? 0
                    ];
                }
            }
            
            return [
                'success' => true,
                'couriers' => $availableCouriers
            ];
        }
        
        return [
            'success' => false,
            'message' => 'No couriers available for this location'
        ];
    }
    
    /**
     * Cancel a shipment
     */
    public function cancelShipment($trackingId) {
        $data = [
            'access_token' => $this->accessToken,
            'secret_key' => $this->secretKey,
            'tracking_id' => $trackingId
        ];
        
        $response = $this->callAPI('order/cancel.json', $data);
        
        if ($response['success']) {
            // Update order status
            $this->updateShipmentStatus($trackingId, 'cancelled');
            
            return [
                'success' => true,
                'message' => 'Shipment cancelled successfully'
            ];
        }
        
        return [
            'success' => false,
            'message' => $response['message'] ?? 'Failed to cancel shipment'
        ];
    }
    
    /**
     * Generate shipping label and manifest
     */
    public function generateLabel($trackingId) {
        $data = [
            'access_token' => $this->accessToken,
            'secret_key' => $this->secretKey,
            'tracking_id' => $trackingId,
            'print_type' => 'thermal' // thermal, a4, thermal_2, a4_2
        ];
        
        $response = $this->callAPI('order/print_manifest.json', $data);
        
        if ($response['success'] && isset($response['data']['label_url'])) {
            return [
                'success' => true,
                'label_url' => $response['data']['label_url'],
                'manifest_url' => $response['data']['manifest_url'] ?? null
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to generate label'
        ];
    }
    
    /**
     * Get pickup requests (for admin)
     */
    public function getPickupRequests($date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        $data = [
            'access_token' => $this->accessToken,
            'secret_key' => $this->secretKey,
            'date' => $date
        ];
        
        return $this->callAPI('order/pickup_list.json', $data);
    }
    
    /**
     * Schedule a pickup
     */
    public function schedulePickup($trackingIds) {
        if (!is_array($trackingIds)) {
            $trackingIds = [$trackingIds];
        }
        
        $data = [
            'access_token' => $this->accessToken,
            'secret_key' => $this->secretKey,
            'tracking_id' => implode(',', $trackingIds)
        ];
        
        $response = $this->callAPI('order/pickup.json', $data);
        
        if ($response['success']) {
            return [
                'success' => true,
                'message' => 'Pickup scheduled successfully',
                'pickup_id' => $response['data']['pickup_id'] ?? null
            ];
        }
        
        return [
            'success' => false,
            'message' => $response['message'] ?? 'Failed to schedule pickup'
        ];
    }
    
    /**
     * Get NDR (Non-Delivery Report) orders
     */
    public function getNDROrders() {
        $data = [
            'access_token' => $this->accessToken,
            'secret_key' => $this->secretKey
        ];
        
        $response = $this->callAPI('order/ndr_list.json', $data);
        
        if ($response['success']) {
            return [
                'success' => true,
                'orders' => $response['data'] ?? []
            ];
        }
        
        return [
            'success' => false,
            'message' => 'No NDR orders found'
        ];
    }
    
    /**
     * Update NDR action
     */
    public function updateNDRAction($trackingId, $action, $remarks = '') {
        $data = [
            'access_token' => $this->accessToken,
            'secret_key' => $this->secretKey,
            'tracking_id' => $trackingId,
            'ndr_action' => $action, // redeliver, rto, hold
            'remarks' => $remarks
        ];
        
        $response = $this->callAPI('order/ndr_action.json', $data);
        
        if ($response['success']) {
            return [
                'success' => true,
                'message' => 'NDR action updated successfully'
            ];
        }
        
        return [
            'success' => false,
            'message' => $response['message'] ?? 'Failed to update NDR action'
        ];
    }
    
    /**
     * Calculate estimated delivery date
     */
    public function calculateEDD($orderId) {
        $order = $this->getOrderWithUser($orderId);
        if (!$order || empty($order['tracking_number'])) {
            return null;
        }
        
        $trackingData = $this->getTrackingUpdates($order['tracking_number']);
        if ($trackingData['success'] && isset($trackingData['data']['etd'])) {
            return $trackingData['data']['etd'];
        }
        
        // Default: 5-7 business days
        return date('Y-m-d', strtotime('+5 weekdays'));
    }
    
    /**
     * Get shipping charges for location
     */
    public function getShippingCharges($pincode, $weight = 1, $codAmount = 0) {
        $data = [
            'access_token' => $this->accessToken,
            'secret_key' => $this->secretKey,
            'pickup_address_id' => $this->pickupAddressId,
            'consignee_pincode' => $pincode,
            'cod_amount' => $codAmount,
            'weight' => $weight
        ];
        
        $response = $this->callAPI('order/check_serviceability.json', $data);
        
        if ($response['success'] && isset($response['data']['courier_data'])) {
            $charges = [];
            foreach ($response['data']['courier_data'] as $courier) {
                if ($courier['serviceable'] == 1) {
                    $charges[$courier['courier_id']] = [
                        'name' => $courier['courier_name'],
                        'rate' => $courier['rate'] ?? 0,
                        'etd' => $courier['etd'] ?? 'N/A'
                    ];
                }
            }
            return $charges;
        }
        
        return [];
    }
    
    /**
     * Call iThink API
     */
    private function callAPI($endpoint, $data) {
        $url = $this->apiUrl . $endpoint;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $result;
            }
        }
        
        return [
            'success' => false,
            'message' => 'API request failed',
            'http_code' => $httpCode
        ];
    }
    
    /**
     * Store shipment data in database
     */
    private function storeShipmentData($orderId, $shipmentData) {
        // Store in shipment_tracking table
        $sql = "INSERT INTO shipment_tracking 
                (order_id, awb_number, courier_name, tracking_data, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                awb_number = VALUES(awb_number),
                courier_name = VALUES(courier_name),
                tracking_data = VALUES(tracking_data),
                status = VALUES(status),
                updated_at = NOW()";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "issss",
            $orderId,
            $shipmentData['awb_number'],
            $shipmentData['courier_name'],
            json_encode($shipmentData),
            $shipmentData['status'] ?? 'created'
        );
        $stmt->execute();
        
        // Store tracking history if available
        if (isset($shipmentData['tracking_history']) && is_array($shipmentData['tracking_history'])) {
            foreach ($shipmentData['tracking_history'] as $history) {
                $this->storeTrackingHistory($orderId, $history);
            }
        }
    }
    
    /**
     * Store tracking history
     */
    private function storeTrackingHistory($orderId, $history) {
        $sql = "INSERT INTO shipment_history 
                (order_id, status, location, remarks, date_time, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                location = VALUES(location),
                remarks = VALUES(remarks),
                date_time = VALUES(date_time)";
        
        $stmt = $this->conn->prepare($sql);
        
        $status = $history['status'] ?? '';
        $location = $history['location'] ?? '';
        $remarks = $history['remarks'] ?? '';
        $dateTime = $history['date_time'] ?? date('Y-m-d H:i:s');
        
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
     * Update order with tracking info
     */
    private function updateOrderWithTracking($orderId, $shipmentData) {
        $sql = "UPDATE orders SET 
                tracking_number = ?,
                awb_number = ?,
                courier_name = ?,
                shipment_data = ?,
                ithink_order_id = ?,
                order_status = 'shipped',
                updated_at = NOW()
                WHERE order_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "sssssi",
            $shipmentData['tracking_id'],
            $shipmentData['awb_number'],
            $shipmentData['courier_name'],
            json_encode($shipmentData),
            $shipmentData['order_id'],
            $orderId
        );
        
        return $stmt->execute();
    }
    
    /**
     * Update tracking data
     */
    private function updateTrackingData($trackingId, $trackingData) {
        // Find order by tracking number
        $sql = "SELECT order_id FROM orders WHERE tracking_number = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $trackingId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $order = $result->fetch_assoc();
            $orderId = $order['order_id'];
            
            // Update shipment_tracking
            $updateSql = "UPDATE shipment_tracking SET 
                         tracking_data = ?,
                         status = ?,
                         updated_at = NOW()
                         WHERE order_id = ?";
            
            $stmt = $this->conn->prepare($updateSql);
            $stmt->bind_param(
                "ssi",
                json_encode($trackingData),
                $trackingData['status'] ?? '',
                $orderId
            );
            $stmt->execute();
            
            // Store tracking history
            if (isset($trackingData['tracking_history']) && is_array($trackingData['tracking_history'])) {
                foreach ($trackingData['tracking_history'] as $history) {
                    $this->storeTrackingHistory($orderId, $history);
                }
            }
            
            // Update order status based on shipment status
            $shipmentStatus = strtolower($trackingData['status'] ?? '');
            if ($shipmentStatus === 'delivered') {
                $this->updateOrderStatus($orderId, 'delivered');
            } elseif ($shipmentStatus === 'out_for_delivery') {
                $this->updateOrderStatus($orderId, 'out_for_delivery');
            }
        }
    }
    
    /**
     * Update shipment status
     */
    private function updateShipmentStatus($trackingId, $status) {
        $sql = "UPDATE shipment_tracking SET 
                status = ?,
                updated_at = NOW()
                WHERE tracking_data LIKE ?";
        
        $searchTerm = '%"tracking_id":"' . $trackingId . '"%';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $status, $searchTerm);
        $stmt->execute();
    }
    
    /**
     * Update order status
     */
    private function updateOrderStatus($orderId, $status) {
        $sql = "UPDATE orders SET 
                order_status = ?,
                updated_at = NOW()
                WHERE order_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $status, $orderId);
        $stmt->execute();
    }
    
    /**
     * Get order with user details
     */
    private function getOrderWithUser($orderId) {
        $sql = "SELECT o.*, u.mobile, u.email, u.first_name, u.last_name 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.order_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get order item count
     */
    private function getOrderItemCount($orderId) {
        $sql = "SELECT COUNT(*) as count FROM order_items WHERE order_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'] ?? 1;
    }
    
    /**
     * Calculate order weight (in kg)
     */
    private function calculateOrderWeight($orderId) {
        // Default weight: 1kg per item
        $itemCount = $this->getOrderItemCount($orderId);
        return max(0.5, $itemCount * 0.5); // Minimum 0.5kg, 0.5kg per item
    }
}
?>