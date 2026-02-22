<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

error_reporting(0);

$host     = "trolley.proxy.rlwy.net"; 
$port     = "22626";
$dbname   = "railway"; 
$user     = "postgres";
$password = "EKfRjcFXFPXNADxvjfuNQdkcZZxlGhEy"; 

$connection_string = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
$db = pg_connect($connection_string);

if (!$db) {
    echo json_encode(["valid" => false, "message" => "Gabim në lidhjen me serverin"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$license = trim($data['license'] ?? $data['activation_code'] ?? '');

if (empty($license)) {
    echo json_encode(["valid" => false, "message" => "Ju lutem vendosni kodin."]);
    exit;
}

$query = "SELECT id, email, plan, status FROM payments WHERE order_id = $1 OR status = 'paid' AND id IN (SELECT id FROM payments WHERE email=$1) LIMIT 1";
$query = "SELECT id, email, plan, status FROM payments WHERE order_id = $1 LIMIT 1";

$query = "SELECT id, email, plan, status FROM payments WHERE order_id = $1 OR plan = $1 LIMIT 1"; 

$query = "SELECT id, email, plan, status FROM payments WHERE order_id = $1 LIMIT 1";

$result = pg_query_params($db, $query, array($license));

if ($result && pg_num_rows($result) > 0) {
    $row = pg_fetch_assoc($result);
    
    if ($row['status'] === 'paid') {
        echo json_encode([
            "valid" => true,
            "message" => "Licenca u gjet me sukses",
            "license_id" => (int)$row['id'],
            "email" => $row['email'],
            "plan" => $row['plan']
        ]);
    } else {
        echo json_encode([
            "valid" => false, 
            "message" => "Kjo licencë nuk është e paguar."
        ]);
    }
} else {
    echo json_encode([
        "valid" => false, 
        "message" => "Kodi është i pasaktë ose nuk ekziston në historikun e pagesave."
    ]);
}

pg_close($db);
?>
