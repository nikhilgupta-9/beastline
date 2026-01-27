<?php
class iThinkLogistics {
    private $accessToken;
    private $secretKey;
    private $apiUrl;
    
    public function __construct() {
        $this->accessToken = ITHINK_ACCESS_TOKEN;
        $this->secretKey = ITHINK_SECRET_KEY;
        $this->apiUrl = ITHINK_API_URL;
    }
    
    /**
     * Make API request
     */
    private function makeRequest($endpoint, $data = [], $method = 'POST') {
        $url = $this->apiUrl . $endpoint;
        
        $headers = [
            'access-token: ' . $this->accessToken,
            'Content-Type: application/json',
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'status' => ($httpCode >= 200 && $httpCode < 300),
            'data' => json_decode($response, true),
            'http_code' => $httpCode
        ];
    }
    
    /**
     * Create shipment for an order
     */
    public function createShipment($orderData) {
        $shipmentData = [
            "pickup_address_id" => ITHINK_PICKUP_ADDRESS_ID,
            "order_number" => $orderData['order_number'],
            "order_date" => date('Y-m-d H:i:s'),
            "total_order_value" => $orderData['total_amount'],
            "length" => 20,  // in cm
            "breadth" => 15, // in cm
            "height" => 10,  // in cm
            "weight" => 0.5, // in kg
            "product_name" => "Clothing Products",
            "quantity" => $orderData['total_quantity'],
            "invoice_value" => $orderData['final_amount'],
            "payment_mode" => "Prepaid",
            "consignee_details" => [
                "name" => $orderData['shipping_name'],
                "email" => $orderData['email'],
                "mobile" => $orderData['phone'],
                "address" => $orderData['shipping_address'],
                "city" => $orderData['city'],
                "state" => $orderData['state'],
                "pincode" => $orderData['pincode'],
                "country" => "India"
            ]
        ];
        
        return $this->makeRequest('order/create.json', $shipmentData);
    }
    
    /**
     * Get tracking information
     */
    public function getTracking($awbNumber) {
        $data = [
            "data" => [
                "awb_number" => $awbNumber
            ]
        ];
        
        return $this->makeRequest('shipment/track.json', $data);
    }
    
    /**
     * Generate AWB (Airway Bill)
     */
    public function generateAWB($orderId) {
        $data = [
            "order_id" => $orderId
        ];
        
        return $this->makeRequest('order/generateAWB.json', $data);
    }
    
    /**
     * Cancel shipment
     */
    public function cancelShipment($orderId) {
        $data = [
            "order_id" => $orderId
        ];
        
        return $this->makeRequest('order/cancel.json', $data);
    }
    
    /**
     * Get shipping charges
     */
    public function getShippingCharges($pincode, $weight, $declaredValue = 0) {
        $data = [
            "destination_pincode" => $pincode,
            "weight" => $weight,
            "declared_value" => $declaredValue
        ];
        
        return $this->makeRequest('order/getCharges.json', $data);
    }
}
?>