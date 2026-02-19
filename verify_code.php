<?php
header("Content-Type: application/json");
error_reporting(0);

$connection_string = "host=127.0.0.1 dbname=falconai_db user=postgres password=FalconAi123!) sslmode=require";

$db = pg_connect($connection_string);

if (!$db) {
    echo json_encode(["valid" => false, "message" => "Server connection error"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$license = trim($data['license'] ?? '');

if (empty($license)) {
    echo json_encode(["valid" => false, "message" => "Ju lutem vendosni kodin."]);
    exit;
}

$query = "SELECT id, email, plan, status FROM payments WHERE license_key = $1 LIMIT 1";
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
        "message" => "Kodi është i pasaktë ose nuk ekziston."
    ]);
}

pg_close($db);
?>