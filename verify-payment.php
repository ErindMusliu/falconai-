<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

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
    echo json_encode(["success" => false, "message" => "Gabim: Lidhja me Railway dështoi."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["success" => false, "message" => "Të dhëna JSON të pavlefshme."]);
    exit;
}

$orderID    = $data['orderID'] ?? 'NO_ID';
$planName   = $data['plan'] ?? 'Basic';
$email      = $data['email'] ?? 'no@email.com';
$full_name  = $data['full_name'] ?? 'Përdorues FalconAI';

pg_query($db, "BEGIN");

try {
    $result = pg_query_params($db, "SELECT id FROM customers WHERE email=$1", array($email));
    if(pg_num_rows($result) > 0){
        $customer = pg_fetch_assoc($result);
        $customer_id = $customer['id'];
    } else {
        $insertCustomer = pg_query_params($db, 
            "INSERT INTO customers (full_name, email) VALUES ($1, $2) RETURNING id", 
            array($full_name, $email)
        );
        $row = pg_fetch_assoc($insertCustomer);
        $customer_id = $row['id'];
    }

    $pkgRes = pg_query_params($db, "SELECT id FROM packages WHERE name=$1", array($planName));
    if(pg_num_rows($pkgRes) == 0){
        throw new Exception("Plani '$planName' nuk ekziston. Ekzekuto save.php.");
    }
    $pkg = pg_fetch_assoc($pkgRes);
    $package_id = $pkg['id'];

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
        "message" => "Pagesa u krye me sukses!",
        "activation_code" => $activation_code
    ]);

} catch (Exception $e) {
    pg_query($db, "ROLLBACK");
    echo json_encode([
        "success" => false, 
        "message" => "Gabim gjatë ruajtjes: " . $e->getMessage()
    ]);
}

pg_close($db);
?>
