<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
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
    echo json_encode(["success" => false, "message" => "Gabim: Lidhja me databazën dështoi."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$orderID   = $data['orderID'] ?? 'TEST-' . time();
$planName  = $data['plan'] ?? 'Basic';
$email     = $data['email'] ?? 'pa-email@falcon.ai';
$full_name = $data['full_name'] ?? 'Klient Falcon AI';

pg_query($db, "BEGIN");

try {
    $res = pg_query_params($db, "SELECT id FROM customers WHERE email=$1", array($email));
    if(pg_num_rows($res) > 0) {
        $customer_id = pg_fetch_assoc($res)['id'];
    } else {
        $ins = pg_query_params($db, "INSERT INTO customers (full_name, email) VALUES ($1, $2) RETURNING id", array($full_name, $email));
        $customer_id = pg_fetch_assoc($ins)['id'];
    }

    $pkgRes = pg_query_params($db, "SELECT id FROM packages WHERE name=$1", array($planName));
    if(pg_num_rows($pkgRes) == 0) {
        $pkgRes = pg_query($db, "SELECT id FROM packages LIMIT 1");
    }
    $package_row = pg_fetch_assoc($pkgRes);
    if (!$package_row) {
        throw new Exception("Asnjë paketë nuk u gjet. Ju lutem ekzekutoni save.php.");
    }
    $package_id = $package_row['id'];

    $activation_code = "FALCON-" . strtoupper(bin2hex(random_bytes(3))) . "-" . rand(1000,9999);

    pg_query_params($db, 
        "INSERT INTO activation_code (code, package_id, used) VALUES ($1, $2, false)", 
        array($activation_code, $package_id)
    );
    
    pg_query_params($db, 
        "INSERT INTO payments (email, plan, order_id, status, created_at) VALUES ($1, $2, $3, 'paid', NOW())", 
        array($email, $planName, $orderID)
    );

    pg_query($db, "COMMIT");

    echo json_encode([
        "success" => true,
        "activation_code" => $activation_code,
        "message" => "Pagesa u procesua me sukses!"
    ]);

} catch (Exception $e) {
    pg_query($db, "ROLLBACK");
    echo json_encode([
        "success" => false, 
        "message" => "Gabim gjatë procesimit: " . $e->getMessage()
    ]);
}

pg_close($db);
?>
