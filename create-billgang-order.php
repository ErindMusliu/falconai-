<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$secret_key = '8RlAj7F9p0iN1A2A3kDQ3n53CAtSPHUw6ijvjb-o1TFa94bqUCaVIQY-KzZvIhdp';

$input = json_decode(file_get_contents("php://input"), true);
$email = $input['email'] ?? '';
$plan_name = $input['plan'] ?? 'Basic';

$prices = ['Basic' => '4.99', 'Standard' => '7.99', 'Pro' => '9.99', 'Premium' => '14.99'];
$amount = $prices[$plan_name] ?? '4.99';

if (empty($email)) {
    echo json_encode(["success" => false, "message" => "Email is required"]);
    exit;
}

$params = [
    'amount'        => $amount,
    'currency'      => 'USD',
    'precision'     => '2',
    'order_number'  => 'FAI' . time(),
    'order_name'    => 'FalconAI ' . $plan_name,
    'email'         => $email,
    'callback_url'  => 'https://falconai-ubo3.onrender.com/billgang-webhook.php',
    'success_url'   => 'https://falconai-ubo3.onrender.com/dashboard.php',
    'api_key'       => $secret_key
];

$url = 'https://plisio.net/api/v1/queries/number';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$result = json_decode($response, true);

if ($http_code == 200 && isset($result['status']) && $result['status'] == 'success') {
    echo json_encode([
        "success" => true,
        "url" => $result['data']['invoice_url']
    ]);
} else {
    $msg = "Gabim i panjohur";
    if ($error) $msg = "CURL Error: " . $error;
    elseif (isset($result['data']['message'])) $msg = $result['data']['message'];
    elseif (isset($result['message'])) $msg = $result['message'];

    echo json_encode([
        "success" => false, 
        "message" => "Plisio ($http_code): " . $msg,
        "raw" => $result
    ]);
}
