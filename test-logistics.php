<?php
class IThinkSyncAPI
{
    private string $accessToken = '06ee35c96720585aea7929c67c9d7500';
    private string $secretKey   = '781406cce6e792df7e862581e952f0af';
    private string $pickupId    = '112475';

    private string $endpoint = 'https://my.ithinklogistics.com/api_v3/order/sync.json';

    public function syncOrder(array $order)
    {
        $totalAmount = $this->calculateTotal($order['items']);

        $shipment = [
            "order"        => $order['order_id'],
            "sub_order"    => "",
            "order_date"   => date("d-m-Y H:i:s"),
            "total_amount" => (string)$totalAmount,

            "name" => $order['customer_name'],
            "add"  => $order['address1'],
            "add2" => "",
            "add3" => "",
            "pin"  => $order['pincode'],
            "city" => $order['city'],
            "state" => $order['state'],
            "country" => "India",
            "phone" => $order['phone'],
            "alt_phone" => $order['phone'],
            "email" => $order['email'],

            "is_billing_same_as_shipping" => "yes",

            "billing_name" => $order['customer_name'],
            "billing_add"  => $order['address1'],
            "billing_pin"  => $order['pincode'],
            "billing_city" => $order['city'],
            "billing_state" => $order['state'],
            "billing_country" => "India",
            "billing_phone" => $order['phone'],
            "billing_email" => $order['email'],

            "products" => $this->prepareProducts($order['items']),

            "shipment_length" => (string)$order['length'],
            "shipment_width"  => (string)$order['width'],
            "shipment_height" => (string)$order['height'],
            "weight"          => (string)$order['weight'],

            "shipping_charges"      => "0",
            "giftwrap_charges"      => "0",
            "transaction_charges"   => "0",
            "total_discount"        => "0",
            "first_attemp_discount" => "0",
            "cod_charges"           => "0",
            "advance_amount"        => "0",


            "cod_amount" => $order['payment_mode'] === 'COD'
                ? (string)$totalAmount
                : "0",

            "payment_mode" => $order['payment_mode'],

            "gst_number" => "",
            "eway_bill_number" => "",

            // 🔥 REQUIRED FIX
            "reseller_name" => "NA"
        ];

        $payload = [
            "data" => [
                "shipments"      => [$shipment],
                "access_token"   => $this->accessToken,
                "secret_key"     => $this->secretKey,
                "pickup_address" => $this->pickupId
            ]
        ];

        return $this->sendRequest($payload);
    }


    private function prepareProducts(array $items): array
    {
        $products = [];

        foreach ($items as $item) {
            $products[] = [
                "product_name"     => $item['name'],
                "product_sku"      => $item['sku'],
                "product_quantity" => (string)$item['qty'],
                "product_price"   => (string)$item['price'],
                "product_tax_rate" => (string)($item['tax_rate'] ?? "0"),
                "product_hsn_code" => $item['hsn'] ?? "",
                "product_discount" => "0"
            ];
        }

        return $products;
    }

    private function sendRequest(array $payload)
    {
        $ch = curl_init($this->endpoint);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);

        curl_close($ch);

        return [
            'http_code' => $httpCode,
            'error'     => $error,
            'response'  => json_decode($response, true),
            'raw'       => $response
        ];
    }

    private function calculateTotal(array $items): int
    {
        $total = 0;

        foreach ($items as $item) {
            $total += ($item['price'] * $item['qty']);
        }

        return $total;
    }
}


$ithink = new IThinkSyncAPI();

$result = $ithink->syncOrder([
    "order_id"      => "BEAST-" . time(),
    "total_amount"  => 300,
    "customer_name" => "Bharat",
    "address1"      => "104, Shreeji Sharan",
    "pincode"       => "400094",
    "city"          => "Mumbai",
    "state"         => "Maharashtra",
    "phone"         => "9876543210",
    "email"         => "abc@gmail.com",
    "payment_mode"  => "COD",
    "weight"        => 0.5,
    "length"        => 10,
    "width"         => 10,
    "height"        => 5,
    "items" => [
        [
            "name"  => "Green Tshirt",
            "sku"   => "GC001",
            "qty"   => 1,
            "price" => 300,
            "tax_rate" => 5,
            "hsn" => "91308"
        ]
    ]
]);

echo "<pre>";
print_r($result);
