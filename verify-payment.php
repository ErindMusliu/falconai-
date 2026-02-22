<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

error_reporting(E_ALL);
ini_set('display_errors', 0);

$host     = "trolley.proxy.rlwy.net"; 
$port     = "22626";
$dbname   = "railway"; 
$user     = "postgres";
$password = "EKfRjcFXFPXNADxvjfuNQdkcZZxlGhEy"; 

$db = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require");

if (!$db) {
    echo json_encode(["success" => false, "message" => "Lidhja me DB deshtoi"]);
    exit;
}

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Serveri Online"]);
    exit;
}

$email = $data['email'] ?? 'test@falconai.com';
$plan  = $data['plan'] ?? 'Basic';
$order = $data['orderID'] ?? 'ORD-' . time();
$license = "FALCON-" . strtoupper(bin2hex(random_bytes(3))) . "-" . rand(1000,9999);

try {
    pg_query($db, "BEGIN");

    $pkgRes = pg_query_params($db, "SELECT id FROM packages WHERE name ILIKE $1", [$plan]);
    $pkg = pg_fetch_assoc($pkgRes);
    $package_id = $pkg ? $pkg['id'] : 1; 

    $paySql = "INSERT INTO payments (email, plan, order_id, status, license_key) VALUES ($1, $2, $3, 'paid', $4)";
    pg_query_params($db, $paySql, [$email, $plan, $order, $license]);

    $actSql = "INSERT INTO activation_code (code, package_id, used) VALUES ($1, $2, false)";
    pg_query_params($db, $actSql, [$license, $package_id]);

    pg_query($db, "COMMIT");

    echo json_encode([
        "success" => true,
        "activation_code" => $license
    ]);

} catch (Exception $e) {
    pg_query($db, "ROLLBACK");
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
pg_close($db);
