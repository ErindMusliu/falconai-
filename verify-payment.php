<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 0);

$host     = "trolley.proxy.rlwy.net"; 
$port     = "22626";
$dbname   = "railway"; 
$user     = "postgres";
$password = "EKfRjcFXFPXNADxvjfuNQdkcZZxlGhEy"; 

$db = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require");

if (!$db) {
    die(json_encode(["success" => false, "message" => "Database Connection Failed"]));
}

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Serveri Online. Duke pritur POST nga Netlify."]);
    exit;
}

try {
    $email = $data['email'] ?? 'test@test.com';
    $plan  = $data['plan'] ?? 'Basic';
    $order = $data['orderID'] ?? 'ID-TEST';
    
    $activation_code = "FALCON-" . strtoupper(bin2hex(random_bytes(3))) . "-" . rand(1000,9999);
    
    pg_query_params($db, "INSERT INTO activation_code (code, package_id, used) VALUES ($1, 1, false)", [$activation_code]);
    pg_query_params($db, "INSERT INTO payments (email, plan, order_id, status) VALUES ($1, $2, $3, 'paid')", [$email, $plan, $order]);

    echo json_encode([
        "success" => true,
        "activation_code" => $activation_code
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
