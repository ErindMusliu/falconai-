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

$email = $data['email'] ?? 'test@falconai.com';
$plan  = $data['plan'] ?? 'Basic';
$order = $data['orderID'] ?? 'ORD-' . time();
$license = "FALCON-" . strtoupper(bin2hex(random_bytes(3))) . "-" . rand(1000,9999);

try {
    $pdo->beginTransaction();

    $stmtPkg = $pdo->prepare("SELECT id FROM packages WHERE name ILIKE ?");
    $stmtPkg->execute([$plan]);
    $pkg = $stmtPkg->fetch();
    $package_id = $pkg ? $pkg['id'] : 1; 

    $paySql = "INSERT INTO payments (email, plan, order_id, status, license_key) VALUES (?, ?, ?, 'paid', ?)";
    $pdo->prepare($paySql)->execute([$email, $plan, $order, $license]);

    $actSql = "INSERT INTO activation_codes (code, package_id, used) VALUES (?, ?, false)";
    $pdo->prepare($actSql)->execute([$license, $package_id]);

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "activation_code" => $license
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["success" => false, "message" => "Gabim gjate procesimit"]);
}
?>
