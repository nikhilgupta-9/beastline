<?php
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['addresses'][0])) {
    echo json_encode(["error" => "Invalid request"]);
    exit;
}

$pincode = $input['addresses'][0]['zipcode'] ?? '';
$address_id = $input['addresses'][0]['id'] ?? 'addr_1';

$response = [
    "addresses" => [
        [
            "id" => $address_id,
            "zipcode" => $pincode,
            "country" => "IN",
            "shipping_methods" => [
                [
                    "id" => "standard",
                    "name" => "Standard Delivery",
                    "description" => "Free delivery in 3-5 business days",
                    "serviceable" => true,
                    "shipping_fee" => 0,
                    "cod" => true,
                    "cod_fee" => 0,
                    "estimated_delivery_days" => 5
                ]
            ]
        ]
    ]
];

echo json_encode($response);
exit;
