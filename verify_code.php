<?php
header("Content-Type: application/json");
error_reporting(0);

$db = pg_connect("host=127.0.0.1 dbname=falconai_db user=postgres password=FalconAi123!)");
if (!$db) {
    echo json_encode(["valid" => false, "message" => "Cannot connect to database"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$license = trim($data['license'] ?? '');

if (empty($license)) {
    echo json_encode(["valid" => false, "message" => "No license code provided"]);
    exit;
}

$query = "SELECT id, email, plan FROM payments WHERE license_key = $1 LIMIT 1";
$result = pg_query_params($db, $query, array($license));

if ($result && pg_num_rows($result) > 0) {
    $row = pg_fetch_assoc($result);
    echo json_encode([
        "valid" => true,
        "license_id" => $row['id'],
        "email" => $row['email'],
        "plan" => $row['plan']
    ]);
} else {
    echo json_encode(["valid" => false, "message" => "License not found"]);
}
?>