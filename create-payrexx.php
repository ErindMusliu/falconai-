<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
$plan  = htmlspecialchars($input['plan'] ?? 'Basic');
$price = floatval($input['price'] ?? 0);

if (!$email || $price <= 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Te dhena te pavlefshme (Email ose Cmimi).'
    ]);
    exit;
}

$instanceName = 'erind'; 
$apiKey = '7M5ec8CPQ35ittnGTp4gaH7x0dwtoF';
$url = "https://api.payrexx.com/v1/Gateway/";

$data = [
    'amount'             => intval($price * 100),
    'currency'           => 'EUR',
    'vatRate'            => 0,
    'title'              => "FalconAI - $plan Plan",
    'description'        => "Abonim mujor per sherbimin FalconAI",
    'prefilledPayerData' => ['email' => $email],
    'psp'                => [],
    'successRedirectUrl' => "https://yourdomain.com/index.html?status=success",
    'failedRedirectUrl'  => "https://yourdomain.com/index.html?status=failed",
];

$queryString = http_build_query($data);
$signature = hash_hmac('sha256', $queryString, $apiKey);
$data['ApiSignature'] = $signature;

$ch = curl_init($url . "?instance=" . $instanceName);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['status']) && $result['status'] === 'success') {
    echo json_encode([
        'success'      => true,
        'checkout_url' => $result['data'][0]['link'],
        'transaction_id' => $result['data'][0]['id']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Gabim gjate krijimit te pageses.',
        'error_detail' => $result['message'] ?? 'API Error',
        'curl_error' => $error
    ]);
}
?>
