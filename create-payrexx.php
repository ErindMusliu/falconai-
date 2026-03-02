<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$price = floatval($input['price'] ?? 0);
$plan  = $input['plan'] ?? 'Basic';

$instanceName = 'erind'; 
$apiKey = 'aKigUR6gt5cLiLlfZsEFpdsYpv2rLJ';

$data = [
    'amount'             => intval($price * 100),
    'currency'           => 'EUR',
    'description'        => "FalconAI - $plan Plan",
    'prefilledPayerData' => ['email' => $email],
    'successRedirectUrl' => "https://google.com",
    'title'              => "FalconAI Subscription",
];

$queryString = http_build_query($data);
$signature = hash_hmac('sha256', $queryString, $apiKey);
$data['ApiSignature'] = $signature;

$url = "https://api.payrexx.com/v1/Gateway/?instance=" . $instanceName;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['data'][0]['link'])) {
    echo json_encode([
        'success'      => true, 
        'checkout_url' => $result['data'][0]['link'], 
        'transaction_id' => $result['data'][0]['id']
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Payrexx Error: ' . ($result['message'] ?? 'Unknown Error'),
        'debug_info' => $result
    ]);
}
?>
