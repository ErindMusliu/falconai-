<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["license_key"]) || empty(trim($data["license_key"]))) {
    echo json_encode([
        "success" => false,
        "message" => "Ju lutem jepni një kod aktivizimi të vlefshëm."
    ]);
    exit;
}

$license_key = trim($data["license_key"]);

$host     = "127.0.0.1";
$port     = "5432";
$dbname   = "falconai_db";
$user     = "postgres";
$password = "FalconAi123!)";

$connection_string = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
$dbconn = pg_connect($connection_string);

if (!$dbconn) {
    echo json_encode([
        "success" => false, 
        "message" => "Gabim në lidhjen me serverin e databazës."
    ]);
    exit;
}

$query = "SELECT email, plan, created_at FROM payments WHERE license_key = $1 AND status = 'paid' LIMIT 1";
$result = pg_query_params($dbconn, $query, array($license_key));

if ($result && pg_num_rows($result) > 0) {
    $row = pg_fetch_assoc($result);

    echo json_encode([
        "success" => true,
        "message" => "Aktivizimi u krye me sukses!",
        "data" => [
            "email" => $row['email'],
            "plan" => $row['plan'],
            "activated_at" => $row['created_at']
        ]
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Kodi i aktivizimit është i pasaktë, i papaguar ose i pavlefshëm."
    ]);
}

pg_close($dbconn);
?>