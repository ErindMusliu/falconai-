<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

error_reporting(E_ALL);
ini_set('display_errors', 0);

include 'db.php';

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Te dhenat mungojne"]);
    exit;
}

$email = $data['email'] ?? '';
$plan  = $data['plan'] ?? 'Basic';
$order = $data['orderID'] ?? 'ORD-' . time();
$name  = $data['full_name'] ?? 'Klient Falcon AI';

if (empty($email)) {
    echo json_encode(["success" => false, "message" => "Email mungon"]);
    exit;
}

$license = "FALCON-" . strtoupper(bin2hex(random_bytes(3))) . "-" . rand(1000,9999);

try {
    $pdo->beginTransaction();

    $stmtCust = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
    $stmtCust->execute([$email]);
    $customer = $stmtCust->fetch();

    if (!$customer) {
        $stmtNewCust = $pdo->prepare("INSERT INTO customers (full_name, email) VALUES (?, ?) RETURNING id");
        $stmtNewCust->execute([$name, $email]);
        $customer_id = $stmtNewCust->fetchColumn();
    } else {
        $customer_id = $customer['id'];
    }

    $stmtPkg = $pdo->prepare("SELECT id, duration_days FROM packages WHERE name ILIKE ? LIMIT 1");
    $stmtPkg->execute([$plan]);
    $pkg = $stmtPkg->fetch();
    
    $package_id = $pkg ? $pkg['id'] : 1; 
    $days = $pkg ? $pkg['duration_days'] : 30;
    
    $paySql = "INSERT INTO payments (email, plan, order_id, status, license_key) VALUES (?, ?, ?, 'paid', ?)";
    $pdo->prepare($paySql)->execute([$email, $plan, $order, $license]);

    $expires_at = date('Y-m-d H:i:s', strtotime("+$days days"));
    $actSql = "INSERT INTO activation_codes (code, package_id, customer_id, used, expires_at) VALUES (?, ?, ?, false, ?)";
    $pdo->prepare($actSql)->execute([$license, $package_id, $customer_id, $expires_at]);

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "activation_code" => $license,
        "customer_id" => $customer_id
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    echo json_encode(["success" => false, "message" => "Gabim: " . $e->getMessage()]);
}
?>
