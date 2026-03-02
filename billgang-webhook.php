<?php
require_once 'db.php';

$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (isset($data['event']) && $data['event'] === 'order.completed') {
    $order = $data['payload'];
    $email = $order['customer_email'];
    $plan_name = $order['product_name'];
    $order_id = $order['id'];

    try {
        $pdo->beginTransaction();

        $stmtPack = $pdo->prepare("SELECT id, duration_days FROM packages WHERE name ILIKE ? LIMIT 1");
        $stmtPack->execute([$plan_name]);
        $package = $stmtPack->fetch(PDO::FETCH_ASSOC);
        $package_id = $package ? $package['id'] : 1; 
        $days = $package ? $package['duration_days'] : 30;

        $stmtCust = $pdo->prepare("INSERT INTO customers (full_name, email) VALUES (?, ?) ON CONFLICT (email) DO UPDATE SET email = EXCLUDED.email RETURNING id");
        $stmtCust->execute(['Billgang Customer', $email]);
        $customer_id = $stmtCust->fetchColumn();

        $license = "FAI-" . strtoupper(bin2hex(random_bytes(3)));
        $expires_at = date('Y-m-d H:i:s', strtotime("+$days days"));

        $stmtPay = $pdo->prepare("INSERT INTO payments (email, plan, order_id, status, license_key) VALUES (?, ?, ?, 'paid', ?)");
        $stmtPay->execute([$email, $plan_name, $order_id, $license]);

        $stmtCode = $pdo->prepare("INSERT INTO activation_codes (code, package_id, customer_id, used, expires_at) VALUES (?, ?, ?, false, ?)");
        $stmtCode->execute([$license, $package_id, $customer_id, $expires_at]);

        $pdo->commit();
        http_response_code(200);
        echo json_encode(["success" => true, "code" => $license]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Billgang Error: " . $e->getMessage());
        http_response_code(500);
    }
}
