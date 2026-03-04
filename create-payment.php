<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, x-api-key");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$api_key = "VENDOS_API_KEY_TEND_KETU"; 

$inputData = file_get_contents("php://input");
$input = json_decode($inputData, true);

if (!$input || empty($input['price'])) {
    echo json_encode([
        "success" => false, 
        "message" => "Të dhënat e kërkesës mungojnë (Price/Plan)."
    ]);
    exit;
}

$data = [
    "price_amount"      => (float)$input['price'],
    "price_currency"    => "eur",
    "ipn_callback_url"  => "https://falconai-ubo3.onrender.com/billgang-webhook.php",
    "order_id"          => "FAI-" . time() . "-" . rand(100, 999),
    "order_description" => "FalconAI Subscription: " . ($input['plan'] ?? 'Premium'),
    "success_url"       => "https://falconai-ubo3.onrender.com/success.php",
    "cancel_url"        => "https://falconai-ubo3.onrender.com/index.html"
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
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    echo json_encode([
        "success" => false, 
        "message" => "CURL Error: " . $curl_error
    ]);
    exit;
}

$resData = json_decode($response, true);

if ($status == 200 || $status == 201) {
    echo json_encode([
        "success" => true, 
        "invoice_url" => $resData['invoice_url']
    ]);
} else {
    $errorMessage = isset($resData['message']) ? $resData['message'] : "Unknown API Error";
    echo json_encode([
        "success" => false, 
        "message" => "NOWPayments Error: " . $errorMessage,
        "debug_status" => $status
    ]);
}
?>
