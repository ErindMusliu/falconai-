<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$price = floatval($input['price'] ?? 0);
$plan  = $input['plan'] ?? 'Basic';

if (!$email || $price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Te dhena te gabuara.']);
    exit;
}

$instanceName = 'erind'; 
$apiKey = 'aKigUR6gt5cLiLlfZsEFpdsYpv2rLJ';

$url = "https://api.payrexx.com/v1/Gateway/";

$data = [
    'amount' => intval($price * 100),
    'currency' => 'EUR',
    'vatRate' => 0,
    'title' => "FalconAI - $plan Plan",
    'description' => "Abonim per sherbimin FalconAI",
    'prefilledPayerData' => ['email' => $email],
    'successRedirectUrl' => "https://falconai-ubo3.onrender.com/success.html",
    'failedRedirectUrl'  => "https://falconai-ubo3.onrender.com/failed.html",
];

$ch = curl_init($url . "?instance=" . $instanceName);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_USERPWD, $apiKey . ":"); 

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
    $errorMessage = $result['message'] ?? 'API Key mismatch or permissions issue';
    echo json_encode([
        'success' => false, 
        'message' => 'Payrexx Error: ' . $errorMessage,
        'debug' => $result
    ]);
}
?>
