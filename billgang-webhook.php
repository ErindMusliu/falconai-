<?php
require_once 'db.php';

$data = $_POST;

if (isset($data['status']) && ($data['status'] === 'completed' || $data['status'] === 'mismatch')) {
    
    $email = $data['email'];
    $order_id = $data['order_number'];
    $plan_name = "Premium";

    try {
        $pdo->beginTransaction();

        $stmtPack = $pdo->prepare("SELECT id, duration_days FROM packages WHERE name ILIKE ? LIMIT 1");
        $stmtPack->execute(['%Premium%']);
        $package = $stmtPack->fetch(PDO::FETCH_ASSOC);
        $package_id = $package ? $package['id'] : 1; 
        $days = $package ? $package['duration_days'] : 30;

        $stmtCust = $pdo->prepare("INSERT INTO customers (full_name, email) VALUES (?, ?) ON CONFLICT (email) DO UPDATE SET email = EXCLUDED.email RETURNING id");
        $stmtCust->execute(['Plisio Customer', $email]);
        $customer_id = $stmtCust->fetchColumn();

        $license = "FAI-" . strtoupper(bin2hex(random_bytes(3)));
        $expires_at = date('Y-m-d H:i:s', strtotime("+$days days"));

        $stmtPay = $pdo->prepare("INSERT INTO payments (email, plan, order_id, status, license_key) VALUES (?, ?, ?, 'paid', ?)");
        $stmtPay->execute([$email, $plan_name, $order_id, $license]);

        $stmtCode = $pdo->prepare("INSERT INTO activation_codes (code, package_id, customer_id, used, expires_at) VALUES (?, ?, ?, false, ?)");
        $stmtCode->execute([$license, $package_id, $customer_id, $expires_at]);

        $pdo->commit();
        
        http_response_code(200);
        echo "OK";

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Plisio Webhook Error: " . $e->getMessage());
        http_response_code(500);
        echo "Error";
    }
} else {
    echo "Waiting for payment...";
}
