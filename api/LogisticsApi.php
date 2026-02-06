<?php
class LogisticsApi
{
    private $apiKey;
    private $apiSecret;
    private $baseUrl;
    private $pickupAddressId;
    private $storeId;

    public function __construct()
    {
        require_once __DIR__ . '/../config/ithink-api.php';

        $this->apiKey = iThinkConfig::ACCESS_TOKEN;
        $this->apiSecret = iThinkConfig::SECRET_KEY;
        $this->baseUrl = iThinkConfig::API_URL;
        $this->pickupAddressId = iThinkConfig::PICKUP_ADDRESS_ID;
        $this->storeId = defined('iThinkConfig::STORE_ID') ? iThinkConfig::STORE_ID : 1; // Default to 1 if not defined
    }

    /**
     * Validate and format phone number for iThink
     */
    private function formatPhoneNumber($phone)
    {
        // Convert to string
        $phone = (string)$phone;

        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If starts with 91 (India code), remove it
        if (substr($phone, 0, 2) == '91' && strlen($phone) == 12) {
            $phone = substr($phone, 2);
        }

        // If starts with 0, remove it
        if (substr($phone, 0, 1) == '0' && strlen($phone) == 11) {
            $phone = substr($phone, 1);
        }

        // Ensure it's exactly 10 digits
        if (strlen($phone) != 10) {
            // If too long, take last 10 digits
            if (strlen($phone) > 10) {
                $phone = substr($phone, -10);
            } else {
                // If too short, pad with zeros
                $phone = str_pad($phone, 10, '0', STR_PAD_LEFT);
            }
        }

        // Final validation - Indian mobile numbers start with 6-9
        if (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
            // Invalid Indian mobile number, use default
            $phone = '9876543210';
        }

        return $phone;
    }

    public function createShipment($orderData)
    {
        $endpoint = $this->baseUrl . 'order/add.json';

        // Use the formatPhoneNumber method
        $phone = $this->formatPhoneNumber($orderData['consignee_phone']);
        $altPhone = isset($orderData['consignee_alt_phone']) ?
            $this->formatPhoneNumber($orderData['consignee_alt_phone']) :
            $phone;

        // Prepare shipment according to iThink documentation
        $shipment = [
            'waybill' => '',
            'order' => $orderData['order_number'],
            'sub_order' => '',
            'order_date' => date('d-m-Y'),
            'total_amount' => $orderData['total_amount'],
            'name' => $orderData['consignee_name'],
            'company_name' => '',
            'add' => $orderData['consignee_address'],
            'add2' => '',
            'add3' => '',
            'pin' => $orderData['consignee_pincode'],
            'city' => $orderData['consignee_city'],
            'state' => $orderData['consignee_state'],
            'country' => $orderData['consignee_country'] ?? 'India',
            'phone' => (string)$phone,
            'alt_phone' => (string)$altPhone,
            'email' => $orderData['consignee_email'],
            'is_billing_same_as_shipping' => 'yes',

            // Billing details
            'billing_name' => $orderData['consignee_name'],
            'billing_company_name' => '',
            'billing_add' => $orderData['consignee_address'],
            'billing_add2' => '',
            'billing_add3' => '',
            'billing_pin' => $orderData['consignee_pincode'],
            'billing_city' => $orderData['consignee_city'],
            'billing_state' => $orderData['consignee_state'],
            'billing_country' => $orderData['consignee_country'] ?? 'India',

            'billing_phone' => (string)$phone,
            'billing_alt_phone' => (string)$altPhone,
            'billing_email' => $orderData['consignee_email'],

            // Products array
            'products' => [
                [
                    'product_name' => $orderData['product_name'] ?? 'Products',
                    'product_sku' => $orderData['product_sku'] ?? 'SKU001',
                    'product_quantity' => (string)($orderData['quantity'] ?? 1),
                    'product_price' => $orderData['total_amount'],
                    'product_tax_rate' => '0',
                    'product_hsn_code' => '',
                    'product_discount' => '0',
                    'product_img_url' => ''
                ]
            ],

            // Shipment dimensions
            'shipment_length' => (string)($orderData['length'] ?? 15),
            'shipment_width' => (string)($orderData['width'] ?? 10),
            'shipment_height' => (string)($orderData['height'] ?? 5),
            'weight' => (string)($orderData['weight'] ?? 0.5),

            // Charges
            'shipping_charges' => '0',
            'giftwrap_charges' => '0',
            'transaction_charges' => '0',
            'total_discount' => '0',
            'first_attemp_discount' => '0',
            'cod_charges' => '0',
            'advance_amount' => '0',
            'cod_amount' => (string)($orderData['cod_amount'] ?? 0),

            // Payment mode
            'payment_mode' => $orderData['payment_type'] == 'cod' ? 'COD' : 'Prepaid',

            // Additional info
            'reseller_name' => '',
            'eway_bill_number' => '',
            'gst_number' => '',
            'what3words' => '',
            'return_address_id' => (string)$this->pickupAddressId,
            'api_source' => '1',
            'store_id' => (string)$this->storeId
        ];

        // Prepare full payload
        $payload = [
            'data' => [
                'shipments' => [$shipment],
                'pickup_address_id' => (string)$this->pickupAddressId,
                'access_token' => $this->apiKey,
                'secret_key' => $this->apiSecret,
                'logistics' => $orderData['logistics'] ?? 'delhivery',
                's_type' => $orderData['s_type'] ?? '',
                'order_type' => $orderData['order_type'] ?? ''
            ]
        ];

        // Log request
        error_log("iThink API Request for order: " . $orderData['order_number']);

        // Make API call
        $result = $this->makeApiCall($endpoint, $payload);

        return $result;
    }

    private function makeApiCall($endpoint, $payload)
    {
        $jsonPayload = json_encode($payload);

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_VERBOSE => true // Enable verbose for debugging
        ]);

        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        fclose($verbose);

        curl_close($ch);

        // Log response
        error_log("iThink API Response ($httpCode) for endpoint: $endpoint");

        // Save verbose log for debugging
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        file_put_contents(
            $logDir . '/ithink-verbose.log',
            date('Y-m-d H:i:s') . "\n" .
                "Endpoint: $endpoint\n" .
                "HTTP Code: $httpCode\n" .
                "Response: $response\n" .
                "Verbose: $verboseLog\n\n",
            FILE_APPEND
        );

        if ($error) {
            return [
                'status' => 'error',
                'message' => 'CURL Error: ' . $error,
                'http_code' => $httpCode
            ];
        }

        $decodedResponse = json_decode($response, true);

        if (!$decodedResponse) {
            return [
                'status' => 'error',
                'message' => 'Invalid JSON response',
                'raw_response' => $response,
                'http_code' => $httpCode
            ];
        }

        return $decodedResponse;
    }

    public function testConnection()
    {
        $testOrder = [
            'order_number' => 'TEST-' . time(),
            'total_amount' => '100.00',
            'consignee_name' => 'Test Customer',
            'consignee_address' => '123 Test Street',
            'consignee_city' => 'Mumbai',
            'consignee_state' => 'Maharashtra',
            'consignee_pincode' => '400001',
            'consignee_phone' => '9876543210',
            'consignee_email' => 'test@example.com',
            'consignee_country' => 'India',
            'payment_type' => 'prepaid',
            'cod_amount' => 0,
            'product_name' => 'Test Product',
            'quantity' => 1,
            'weight' => 0.5,
            'logistics' => 'delhivery'
        ];

        return $this->createShipment($testOrder);
    }

    /**
     * Create shipment with debugging
     */
    public function createShipmentWithDebug($orderData)
    {
        echo "<pre>";
        echo "=== DEBUG MODE ===\n";
        echo "Order Data:\n";
        print_r($orderData);

        echo "\n=== Phone Formatting ===\n";
        $phone = $this->formatPhoneNumber($orderData['consignee_phone']);
        echo "Original: " . $orderData['consignee_phone'] . "\n";
        echo "Formatted: $phone\n";
        echo "Type: " . gettype($phone) . "\n";

        $result = $this->createShipment($orderData);

        echo "\n=== API Result ===\n";
        print_r($result);

        echo "</pre>";

        return $result;
    }
}
