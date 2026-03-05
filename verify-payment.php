<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

include 'db.php';

$clientId = "AR2C2yrKcxbHCN1QZAAheawDRTh9m661qV754gPvvKwhupeEdsyeKTi4Mt-70J7Kuq4zVEYKZK-6KUIF";
$secret = "ENz2vAgGK0K5yWwGC2FkRxTdD6NZa29xMoU5wjcAup5qhIKBBIYsomurTEaW3191wx2RWW1Zh3ZYtRm";

$input = file_get_contents("php://input");
$data = json_decode($input, true);

$orderID = $data['orderID'] ?? '';
$email = $data['email'] ?? '';
$plan = $data['plan'] ?? 'Basic';

if (empty($orderID) || empty($email)) {
    echo json_encode(["success" => false, "message" => "Te dhenat mungojne"]);
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api-m.paypal.com/v1/oauth2/token");
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $clientId . ":" . $secret);
curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
$result = curl_exec($ch);
$tokenData = json_decode($result);
$accessToken = $tokenData->access_token;

curl_setopt($ch, CURLOPT_URL, "https://api-m.paypal.com/v2/checkout/orders/" . $orderID);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer " . $accessToken]);
curl_setopt($ch, CURLOPT_POSTFIELDS, null);
$orderStatusResponse = curl_exec($ch);
$orderDetail = json_decode($orderStatusResponse);
curl_close($ch);

if ($orderDetail->status !== 'COMPLETED') {
    echo json_encode(["success" => false, "message" => "Pagesa nuk u verifikua nga PayPal"]);
    exit;
}

$license = "FALCON-" . strtoupper(bin2hex(random_bytes(3))) . "-" . rand(1000,9999);

try {
    $pdo->beginTransaction();

    $stmtCust = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
    $stmtCust->execute([$email]);
    $customer = $stmtCust->fetch();
    $customer_id = $customer ? $customer['id'] : null;

    if (!$customer_id) {
        $stmtNewCust = $pdo->prepare("INSERT INTO customers (full_name, email) VALUES (?, ?) RETURNING id");
        $stmtNewCust->execute(['Klient Falcon AI', $email]);
        $customer_id = $stmtNewCust->fetchColumn();
    }

    $stmtPkg = $pdo->prepare("SELECT id, duration_days FROM packages WHERE name ILIKE ? LIMIT 1");
    $stmtPkg->execute([$plan]);
    $pkg = $stmtPkg->fetch();
    $package_id = $pkg ? $pkg['id'] : 1; 
    $days = $pkg ? $pkg['duration_days'] : 30;

    $paySql = "INSERT INTO payments (email, plan, order_id, status, license_key) VALUES (?, ?, ?, 'paid', ?)";
    $pdo->prepare($paySql)->execute([$email, $plan, $orderID, $license]);

    $expires_at = date('Y-m-d H:i:s', strtotime("+$days days"));
    $actSql = "INSERT INTO activation_codes (code, package_id, customer_id, used, expires_at) VALUES (?, ?, ?, false, ?)";
    $pdo->prepare($actSql)->execute([$license, $package_id, $customer_id, $expires_at]);

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "activation_code" => $license
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    echo json_encode(["success" => false, "message" => "Gabim ne database: " . $e->getMessage()]);
}
?>
