<?php
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    exit(0);
}

header("Content-Type: application/json; charset=UTF-8");

error_reporting(E_ALL);
ini_set('display_errors', 0);

$host     = "trolley.proxy.rlwy.net"; 
$port     = "22626";
$dbname   = "railway"; 
$user     = "postgres";
$password = "EKfRjcFXFPXNADxvjfuNQdkcZZxlGhEy"; 

$connection_string = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
$db = pg_connect($connection_string);

if (!$db) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "No data received"]);
    exit;
}

$orderID   = $data['orderID'] ?? 'ID-' . time();
$planName  = $data['plan'] ?? 'Basic';
$email     = $data['email'] ?? 'no-email@falcon.ai';

try {
    pg_query($db, "BEGIN");

    $res = pg_query_params($db, "SELECT id FROM customers WHERE email=$1", [$email]);
    if(pg_num_rows($res) > 0) {
        $customer_id = pg_fetch_assoc($res)['id'];
    } else {
        $ins = pg_query_params($db, "INSERT INTO customers (full_name, email) VALUES ('Klient Falcon', $1) RETURNING id", [$email]);
        $customer_id = pg_fetch_assoc($ins)['id'];
    }

    $pkgRes = pg_query_params($db, "SELECT id FROM packages WHERE name=$1", [$planName]);
    if(pg_num_rows($pkgRes) == 0) {
        $pkgRes = pg_query($db, "SELECT id FROM packages LIMIT 1");
    }
    $package_id = pg_fetch_assoc($pkgRes)['id'];

    $activation_code = "FALCON-" . strtoupper(bin2hex(random_bytes(3))) . "-" . rand(1000,9999);

    pg_query_params($db, "INSERT INTO activation_code (code, package_id, used) VALUES ($1, $2, false)", [$activation_code, $package_id]);
    pg_query_params($db, "INSERT INTO payments (email, plan, order_id, status) VALUES ($1, $2, $3, 'paid')", [$email, $planName, $orderID]);

    pg_query($db, "COMMIT");

    echo json_encode([
        "success" => true,
        "activation_code" => $activation_code
    ]);

} catch (Exception $e) {
    pg_query($db, "ROLLBACK");
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
pg_close($db);
