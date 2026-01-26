<?php
class IThinkLogisticsAPI {
    private $access_token = "5a7b40197cd919337501dd6e9a3aad9a";
    private $secret_key = "2b54c373427be180d1899400eeb21aab";
    private $api_url = "https://prealpha.ithinklogistics.com/api_v3";
    private $pickup_address_id = 1293;
    
    // Shipment creation
    public function createShipment($order_data) {
        $endpoint = $this->api_url . "/shipment/create.json";
        
        $data = [
            'access_token' => $this->access_token,
            'secret_key' => $this->secret_key,
            'pickup_address_id' => $this->pickup_address_id,
            'pickup_type' => 'P',
            'order_data' => json_encode([$order_data])
        ];
        
        return $this->makeRequest($endpoint, $data);
    }
    
    // Get shipping charges
    public function getShippingCharges($data) {
        $endpoint = $this->api_url . "/shipment/charges.json";
        
        $request_data = [
            'access_token' => $this->access_token,
            'secret_key' => $this->secret_key,
            'pickup_address_id' => $this->pickup_address_id,
            'pickup_pincode' => $data['pickup_pincode'],
            'delivery_pincode' => $data['delivery_pincode'],
            'order_weight' => $data['weight'],
            'order_invoice_value' => $data['invoice_value'],
            'payment_type' => $data['payment_type'] ?? 'COD',
            'length' => $data['length'] ?? 20,
            'breadth' => $data['breadth'] ?? 20,
            'height' => $data['height'] ?? 10
        ];
        
        return $this->makeRequest($endpoint, $request_data);
    }
    
    // Track shipment
    public function trackShipment($awb_number) {
        $endpoint = $this->api_url . "/order/track.json";
        
        $data = [
            'access_token' => $this->access_token,
            'secret_key' => $this->secret_key,
            'awb' => $awb_number
        ];
        
        return $this->makeRequest($endpoint, $data);
    }
    
    // Generate manifest
    public function generateManifest($order_ids) {
        $endpoint = $this->api_url . "/manifest/print.json";
        
        $data = [
            'access_token' => $this->access_token,
            'secret_key' => $this->secret_key,
            'order_data' => json_encode($order_ids)
        ];
        
        return $this->makeRequest($endpoint, $data);
    }
    
    // Generate label
    public function generateLabel($order_ids) {
        $endpoint = $this->api_url . "/label/print.json";
        
        $data = [
            'access_token' => $this->access_token,
            'secret_key' => $this->secret_key,
            'order_data' => json_encode($order_ids)
        ];
        
        return $this->makeRequest($endpoint, $data);
    }
    
    // Cancel shipment
    public function cancelShipment($order_id, $remark = "Customer requested cancellation") {
        $endpoint = $this->api_url . "/shipment/cancel.json";
        
        $data = [
            'access_token' => $this->access_token,
            'secret_key' => $this->secret_key,
            'order_id' => $order_id,
            'remark' => $remark
        ];
        
        return $this->makeRequest($endpoint, $data);
    }
    
    // Make HTTP request
    private function makeRequest($url, $data) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($error) {
            return [
                'status' => false,
                'message' => 'CURL Error: ' . $error
            ];
        }
        
        return json_decode($response, true);
    }
}
?>