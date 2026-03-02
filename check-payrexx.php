<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

$transaction_id = isset($_GET['transaction_id']) ? $_GET['transaction_id'] : '';

if (!$transaction_id) {
    echo json_encode(['paid' => false, 'message' => 'Transaction ID mungon']);
    exit;
}

$instanceName = 'erind';
$apiKey = '7M5ec8CPQ35ittnGTp4gaH7x0dwtoF';

$signature = hash_hmac('sha256', '', $apiKey);

$url = "https://api.payrexx.com/v1/Transaction/$transaction_id/?instance=$instanceName&ApiSignature=$signature";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['data'][0]['status'])) {
    $status = $result['data'][0]['status'];

    if ($status === 'confirmed') {
        $activation_code = 'FalconAI-' . strtoupper(bin2hex(random_bytes(3))) . '-' . rand(1000, 9999);

        echo json_encode([
            'paid' => true,
            'status' => $status,
            'activation_code' => $activation_code
        ]);
    } else {
        echo json_encode([
            'paid' => false,
            'status' => $status,
            'message' => 'Pagesa ende nuk eshte konfirmuar.'
        ]);
    }
} else {
    echo json_encode([
        'paid' => false,
        'message' => 'Gabim gjate komunikimit me Payrexx.',
        'debug' => $result
    ]);
}
?>
