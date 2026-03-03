<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

header("Content-Type: application/json");

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
    echo json_encode(["success" => false, "message" => "Email is required"]);
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
curl_setopt($ch, CURLOPT_TIMEOUT, 20);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    echo json_encode(["success" => false, "message" => "CURL Error: " . $curl_error]);
    exit;
}

$result = json_decode($response, true);

if (isset($result['status']) && $result['status'] == 'success') {
    echo json_encode([
        "success" => true,
        "url" => $result['data']['invoice_url']
    ]);
} else {
    $error_msg = $result['data']['message'] ?? ($result['message'] ?? 'Unknown API Error');
    echo json_encode([
        "success" => false, 
        "message" => "Plisio ($http_code): " . $error_msg
    ]);
}
