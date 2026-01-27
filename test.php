<?php
// test-ithink.php
require_once 'config/ithink-api.php';
require_once 'includes/ithink-logistics.php';

$ithink = new iThinkLogistics();

// Test 1: Check connectivity
$testData = [
    "destination_pincode" => "400001",
    "weight" => 0.5,
    "declared_value" => 500
];

$response = $ithink->getShippingCharges("400001", 0.5, 500);
echo "<h3>Test API Connection:</h3>";
echo "<pre>";
print_r($response);
echo "</pre>";

// Test 2: Test with your credentials directly
$url = ITHINK_API_URL . 'order/getCharges.json';
$headers = [
    'access-token: ' . ITHINK_ACCESS_TOKEN,
    'Content-Type: application/json',
];

$data = json_encode($testData);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$result = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "<h3>Direct cURL Test:</h3>";
echo "HTTP Code: " . $info['http_code'] . "<br>";
echo "Response: <pre>" . htmlspecialchars($result) . "</pre>";
?>