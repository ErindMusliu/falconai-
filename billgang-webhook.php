<?php
require_once 'db.php';

$ipn_secret = "3+evl99TdoXWlkGb9fhE3oETElI3QPHQ";

$raw_data = file_get_contents('php://input');
$data = json_decode($raw_data, true);

$received_hmac = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ?? '';
$calculated_hmac = hash_hmac('sha512', $raw_data, $ipn_secret);

if (empty($received_hmac) || $received_hmac !== $calculated_hmac) {
    error_log("Tentativë e pasigurt: Nënshkrimi i IPN nuk përputhet.");
    http_response_code(401);
    die("Unauthorized: Invalid Signature");
}

if (isset($data['payment_status']) && $data['payment_status'] === 'finished') {
    
    $email = $data['pay_address'];
    $order_id = $data['order_id'] ?? $data['payment_id'];
    $price_amount = $data['price_amount'];
    
    $plan_name = "Premium"; 

    try {
        $pdo->beginTransaction();

        $stmtPack = $pdo->prepare("SELECT id, duration_days FROM packages WHERE name ILIKE ? LIMIT 1");
        $stmtPack->execute(['%' . $plan_name . '%']);
        $package = $stmtPack->fetch(PDO::FETCH_ASSOC);
        
        $package_id = $package ? $package['id'] : 1; 
        $days = $package ? $package['duration_days'] : 30;

        $stmtCust = $pdo->prepare("
            INSERT INTO customers (full_name, email) 
            VALUES (?, ?) 
            ON CONFLICT (email) DO UPDATE SET email = EXCLUDED.email 
            RETURNING id
        ");
        $stmtCust->execute(['Crypto Customer', $email]);
        $customer_id = $stmtCust->fetchColumn();

        $license = "FAI-" . strtoupper(bin2hex(random_bytes(3))) . "-" . rand(1000, 9999);
        $expires_at = date('Y-m-d H:i:s', strtotime("+$days days"));

        $stmtPay = $pdo->prepare("
            INSERT INTO payments (email, plan, order_id, status, license_key) 
            VALUES (?, ?, ?, 'paid', ?)
            ON CONFLICT (order_id) DO NOTHING
        ");
        $stmtPay->execute([$email, $plan_name, $order_id, $license]);

        $stmtCode = $pdo->prepare("
            INSERT INTO activation_codes (code, package_id, customer_id, used, expires_at) 
            VALUES (?, ?, ?, false, ?)
        ");
        $stmtCode->execute([$license, $package_id, $customer_id, $expires_at]);

        $pdo->commit();
        
        http_response_code(200);
        echo "OK - License Generated: " . $license;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Gabim në Webhook (NOWPayments): " . $e->getMessage());
        http_response_code(500);
        echo "Error Processing Payment";
    }
} else {
    echo "Waiting for 'finished' status. Current: " . ($data['payment_status'] ?? 'unknown');
}
?>
