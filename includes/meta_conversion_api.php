<?php

function sendMetaEvent($event_name, $value = 0, $currency = "INR", $email = "", $phone = "")
{

$pixel_id = "YOUR_PIXEL_ID";
$access_token = "YOUR_ACCESS_TOKEN";

$url = "https://graph.facebook.com/v18.0/$pixel_id/events?access_token=$access_token";

$userData = [];

if($email){
$userData['em'] = hash('sha256', strtolower(trim($email)));
}

if($phone){
$userData['ph'] = hash('sha256', preg_replace('/[^0-9]/', '', $phone));
}

$userData['client_ip_address'] = $_SERVER['REMOTE_ADDR'];
$userData['client_user_agent'] = $_SERVER['HTTP_USER_AGENT'];

$data = [
"data" => [
[
"event_name" => $event_name,
"event_time" => time(),
"action_source" => "website",
"user_data" => $userData,
"custom_data" => [
"currency" => $currency,
"value" => $value
]
]
]
];

$payload = json_encode($data);

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

return $response;

}

?>