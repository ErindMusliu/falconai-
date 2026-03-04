<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, x-api-key");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$input = json_decode(file_get_contents("php://input"), true);

$api_key = "BW8XYBY-36JM48Y-PNBEVP7-HFNDB4H"; 

if (!$input) {
    echo json_encode(["success" => false, "message" => "Nuk u mern dot te dhenat nga front-end."]);
    exit;
}

$data = [
    "price_amount" => (float)$input['price'],
    "price_currency" => "eur",
    "pay_currency" => null,
    "ipn_callback_url" => "https://falconai-ubo3.onrender.com/billgang-webhook.php",
    "order_id" => "FALCON-" . time(),
    "order_description" => "FalconAI Plan: " . $input['plan'],
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
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

$resData = json_decode($response, true);

if ($status == 200 || $status == 201) {
    echo json_encode(["success" => true, "invoice_url" => $resData['invoice_url']]);
} else {
    $error_msg = isset($resData['message']) ? $resData['message'] : "Unknown API Error";
    echo json_encode([
        "success" => false, 
        "message" => "NOWPayments Error: " . $error_msg,
        "code" => $status
    ]);
}
?>
