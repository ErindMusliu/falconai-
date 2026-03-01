<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
$plan = htmlspecialchars($input['plan']);
$price = floatval($input['price']);

if (!$email || !$plan || !$price) {
    echo json_encode(['success' => false, 'message' => 'Të dhëna të pavlefshme']);
    exit;
}

$apiKey = '7M5ec8CPQ35ittnGTp4gaH7x0dwtoF';

$ch = curl_init('https://api.payrexx.com/v1/Transaction/');

$data = [
    "amount" => intval($price * 100),
    "currency" => "EUR",
    "payer" => ["email" => $email],
    "description" => "FalconAI - $plan Plan",
    "redirectUrl" => "https://yourdomain.com/payment-success.html",
];

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if (isset($result['id']) && isset($result['paymentUrl'])) {
    $transaction_id = $result['id'];
    echo json_encode([
        'success' => true,
        'checkout_url' => $result['paymentUrl'],
        'transaction_id' => $transaction_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gabim gjatë krijimit të pagesës']);
}
