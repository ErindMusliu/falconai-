<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

if (!$input || empty($input['email'])) {
    echo json_encode(["success" => false, "message" => "Të dhënat mungojnë."]);
    exit;
}

$api_key = "VENDOS_API_KEY_I_NOWPAYMENTS_KETU"; 

$data = [
    "price_amount" => (float)$input['price'],
    "price_currency" => "eur",
    "order_id" => "FALCON-" . time(),
    "order_description" => "FalconAI Plan: " . $input['plan'],
    "ipn_callback_url" => "https://falconai-ubo3.onrender.com/billgang-webhook.php",
    "success_url" => "https://falconai-ubo3.onrender.com/success.php",
    "cancel_url" => "https://falconai-ubo3.onrender.com/index.html"
];

$ch = curl_init("https://api.nowpayments.io/v1/invoice");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "x-api-key: $api_key",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo json_encode(["success" => false, "message" => "CURL Error: " . $err]);
} else {
    $resData = json_decode($response, true);
    if ($http_code == 200 || $http_code == 201) {
        echo json_encode(["success" => true, "invoice_url" => $resData['invoice_url']]);
    } else {
        echo json_encode(["success" => false, "message" => "API Error", "details" => $resData]);
    }
}
?>
