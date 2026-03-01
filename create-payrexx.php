<?php
header('Content-Type: application/json');

$transaction_id = isset($_GET['transaction_id']) ? $_GET['transaction_id'] : '';

if (!$transaction_id) {
    echo json_encode(['paid' => false, 'message' => 'Transaction ID mungon']);
    exit;
}

$apiKey = '7M5ec8CPQ35ittnGTp4gaH7x0dwtoF';

$ch = curl_init("https://api.payrexx.com/v1/Transaction/$transaction_id");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if (isset($result['status']) && $result['status'] === 'PAID') {
    $activation_code = 'FalconAI-' . strtoupper(bin2hex(random_bytes(4))) . '-' . rand(100,999);

    echo json_encode([
        'paid' => true,
        'activation_code' => $activation_code
    ]);
} else {
    echo json_encode(['paid' => false]);
}
