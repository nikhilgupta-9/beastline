<?php
// api/LogisticsApi.php - CORRECTED VERSION
class LogisticsApi
{
    private $apiKey;
    private $apiSecret;
    private $baseUrl;

    public function __construct()
    {
        require_once __DIR__ . '/../config/ithink-api.php';

        $this->apiKey = iThinkConfig::ACCESS_TOKEN;
        $this->apiSecret = iThinkConfig::SECRET_KEY;
        $this->baseUrl = iThinkConfig::API_URL;
    }

    /**
     * Create shipment using sync API - CORRECT FORMAT
     */
    public function createShipment($orderData)
    {
        $endpoint = $this->baseUrl . 'order/sync.json';

        // Prepare products array
        $products = [];
        if (isset($orderData['cart_items']) && is_array($orderData['cart_items'])) {
            foreach ($orderData['cart_items'] as $item) {
                $products[] = [
                    'product_name' => $item['product_name'] ?? 'Product',
                    'product_sku' => $item['sku'] ?? 'SKU001',
                    'product_quantity' => (string)($item['quantity'] ?? 1),
                    'product_price' => (string)($item['price'] ?? 0),
                    'product_tax_rate' => $item['tax_rate'] ?? '',
                    'product_hsn_code' => $item['hsn_code'] ?? '',
                    'product_discount' => (string)($item['discount'] ?? '0')
                ];
            }
        } else {
            // Default product
            $products[] = [
                'product_name' => 'Order #' . $orderData['order_number'],
                'product_sku' => 'ORDER-' . $orderData['order_number'],
                'product_quantity' => '1',
                'product_price' => $orderData['total_amount'],
                'product_tax_rate' => '',
                'product_hsn_code' => '',
                'product_discount' => '0'
            ];
        }

        // Get current date in correct format
        $orderDate = date('d-m-Y H:i:s');

        // Check if COD or Prepaid
        $paymentMode = ($orderData['payment_type'] === 'cod') ? 'COD' : 'Prepaid';

        // Prepare shipment data
        $shipment = [
            'order' => $orderData['order_number'],
            'sub_order' => '',
            'order_date' => date('d-m-Y H:i:s'),
            'total_amount' => (string)$orderData['total_amount'],

            'name' => $orderData['consignee_name'],
            'company_name' => '',
            'add' => $orderData['consignee_address'],
            'add2' => '',
            'add3' => '',
            'pin' => $orderData['consignee_pincode'],
            'city' => $orderData['consignee_city'],
            'state' => $orderData['consignee_state'],
            'country' => 'India',
            'phone' => $orderData['consignee_phone'],
            'alt_phone' => $orderData['consignee_phone'],
            'email' => $orderData['consignee_email'],

            'is_billing_same_as_shipping' => 'yes',

            'products' => $products,

            // ✅ EXACT FIELD NAMES
            'shipment_length' => '15',
            'shipment_width'  => '10',
            'shipment_height' => '5',
            'weight' => '0.5',

            'shipping_charges' => '0',
            'giftwrap_charges' => '0',
            'transaction_charges' => '0',
            'total_discount' => '0',
            'first_attemp_discount' => '0',
            'cod_charges' => '0',
            'advance_amount' => '0',

            'cod_amount' => ($paymentMode === 'COD')
                ? (string)$orderData['total_amount']
                : '0',

            'payment_mode' => $paymentMode,

            // 'reseller_name' => '',
            'eway_bill_number' => '',
            'gst_number' => ''
        ];


        // Prepare full payload
        $payload = [
            'data' => [
                'shipments' => [$shipment],
                'access_token' => $this->apiKey,
                'secret_key' => $this->apiSecret,
                'pickup_address' => iThinkConfig::PICKUP_ADDRESS_ID

            ]
        ];

        // Make API call
        return $this->makeApiCall($endpoint, $payload);
    }

    /**
     * Make API call - SIMPLIFIED AND CORRECTED
     */
    private function makeApiCall($endpoint, $payload)
    {
        // Convert to JSON
        $jsonPayload = json_encode($payload);

        // Log request
        error_log("iThink API Request: " . $endpoint);
        error_log("Payload: " . json_encode($payload, JSON_PRETTY_PRINT));

        // Initialize cURL
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_HTTPHEADER => [
                "cache-control: no-cache",
                "content-type: application/json",
                "accept: application/json"
            ]
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        // Handle response
        if ($error) {
            return [
                'success' => false,
                'error' => 'cURL Error: ' . $error,
                'http_code' => $httpCode
            ];
        }

        // Decode response
        $decodedResponse = json_decode($response, true);

        if (!$decodedResponse) {
            return [
                'success' => false,
                'error' => 'Invalid JSON response',
                'raw_response' => $response,
                'http_code' => $httpCode
            ];
        }

        return $decodedResponse;
    }

    /**
     * Test connection with sample order
     */
    public function testConnection()
    {
        $testOrder = [
            'order_number' => 'TEST-' . time(),
            'total_amount' => '300',
            'consignee_name' => 'Bharat',
            'consignee_address' => '104, Shreeji Sharan',
            'consignee_city' => 'Mumbai',
            'consignee_state' => 'Maharashtra',
            'consignee_pincode' => '400094',
            'consignee_phone' => '9876543210',
            'consignee_email' => 'test@example.com',
            'consignee_country' => 'India',
            'payment_type' => 'cod',
            'cod_amount' => '300',
            'weight' => 0.5,
            'length' => 10,
            'width' => 10,
            'height' => 5,
            'cart_items' => [
                [
                    'product_name' => 'Green color tshirt',
                    'sku' => 'GC001-1',
                    'quantity' => 1,
                    'price' => '100',
                    'tax_rate' => '5',
                    'hsn_code' => '91308',
                    'discount' => '0'
                ],
                [
                    'product_name' => 'Red color tshirt',
                    'sku' => 'GC002-2',
                    'quantity' => 1,
                    'price' => '200',
                    'tax_rate' => '5',
                    'hsn_code' => '91308',
                    'discount' => '0'
                ]
            ]
        ];

        return $this->createShipment($testOrder);
    }

    /**
     * Get tracking information
     */
    public function getTracking($awbNumber)
    {
        $endpoint = $this->baseUrl . 'order/track.json';

        $payload = [
            'data' => [
                'awb_number' => $awbNumber,
                'access_token' => $this->apiKey,
                'secret_key' => $this->apiSecret
            ]
        ];

        return $this->makeApiCall($endpoint, $payload);
    }


    public function assignAwb($refnum)
    {
        $endpoint = $this->baseUrl . 'order/assign_awb.json';

        $payload = [
            'data' => [
                'refnums' => [$refnum],
                'access_token' => $this->apiKey,
                'secret_key' => $this->apiSecret
            ]
        ];

        return $this->makeApiCall($endpoint, $payload);
    }
}
