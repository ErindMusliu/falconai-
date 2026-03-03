<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$secret_key = '8RlAj7F9p0iN1A2A3kDQ3n53CAtSPHUw6ijvjb-o1TFa94bqUCaVIQY-KzZvIhdp';

$input = json_decode(file_get_contents("php://input"), true);
$email = $input['email'] ?? '';

if (empty($email)) {
    echo json_encode(["success" => false, "message" => "Ju lutem shkruani email-in"]);
    exit;
}

$params = [
    'amount' => '4.99',
    'currency' => 'USD',
    'precision' => 2,
    'order_number' => 'FAI-' . bin2hex(random_bytes(4)),
    'order_name' => 'FalconAI Premium Subscription',
    'email' => $email,
    'callback_url' => 'https://falconai-ubo3.onrender.com/billgang-webhook.php',
    'success_url' => 'https://falconai-ubo3.onrender.com/dashboard.php',
    'api_key' => $secret_key
];

$url = 'https://plisio.net/api/v1/queries/number?' . http_build_query($params);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);

if (isset($result['status']) && $result['status'] == 'success') {
    echo json_encode([
        "success" => true,
        "url" => $result['data']['invoice_url']
    ]);
} else {
    echo json_encode([
        "success" => false, 
        "message" => "Plisio Error: " . ($result['data']['message'] ?? 'Connection failed')
    ]);
}
