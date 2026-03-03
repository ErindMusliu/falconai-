<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$secret_key = '8RlAj7F9p0iN1A2A3kDQ3n53CAtSPHUw6ijvjb-o1TFa94bqUCaVIQY-KzZvIhdp';

$input = json_decode(file_get_contents("php://input"), true);
$email = $input['email'] ?? '';
$plan_name = $input['plan'] ?? 'Basic';

$prices = [
    'Basic'    => '4.99',
    'Standard' => '7.99',
    'Pro'      => '9.99',
    'Premium'  => '14.99'
];

$amount = $prices[$plan_name] ?? '4.99';

if (empty($email)) {
    echo json_encode(["success" => false, "message" => "Ju lutem shkruani email-in"]);
    exit;
}

$params = [
    'api_key'      => $secret_key,
    'amount'       => $amount,
    'currency'     => 'USD',
    'precision'    => '2',
    'order_number' => 'FAI-' . strtoupper($plan_name) . '-' . time(),
    'order_name'   => 'FalconAI ' . $plan_name,
    'email'        => $email,
    'callback_url' => 'https://falconai-ubo3.onrender.com/billgang-webhook.php',
    'success_url'  => 'https://falconai-ubo3.onrender.com/dashboard.php'
];

$url = 'https://plisio.net/api/v1/queries/number?' . http_build_query($params);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);

if (isset($result['status']) && $result['status'] == 'success') {
    echo json_encode([
        "success" => true,
        "url" => $result['data']['invoice_url']
    ]);
} else {
    $error_detail = $result['data']['message'] ?? ($result['message'] ?? 'API Connection Issue');
    echo json_encode([
        "success" => false, 
        "message" => "Plisio Error: " . $error_detail,
        "debug" => $result
    ]);
}
