<?php
// Get raw POST data from Razorpay
$payload = file_get_contents('php://input');

// Save raw payload for debugging (optional)
file_put_contents('razorpay_webhook_log.txt', $payload . PHP_EOL, FILE_APPEND);

// Convert JSON to PHP array
$data = json_decode($payload, true);

// If payload is empty, exit
if (!$data) {
    http_response_code(400);
    exit('Invalid payload');
}

// Identify event type
$event = $data['event'] ?? '';

/*
Common events:
payment.captured
order.paid
checkout.abandoned
*/

if ($event === 'checkout.abandoned') {

    $checkout = $data['payload']['checkout']['entity'] ?? [];

    $email  = $checkout['email'] ?? '';
    $phone  = $checkout['contact'] ?? '';
    $amount = $checkout['amount'] ?? 0;

    // Save to database OR log
    file_put_contents(
        'abandoned_orders.txt',
        json_encode([
            'email' => $email,
            'phone' => $phone,
            'amount' => $amount,
            'time' => date('Y-m-d H:i:s')
        ]) . PHP_EOL,
        FILE_APPEND
    );
}

// Always return 200 OK
http_response_code(200);
echo "Webhook received";
