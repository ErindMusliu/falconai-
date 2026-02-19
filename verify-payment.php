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

$db_url = getenv("DATABASE_URL");
if (!$db_url) {
    echo json_encode(["success" => false, "message" => "Variabla DATABASE_URL mungon."]);
    exit;
}

$db = pg_connect($db_url . "?sslmode=require");

if (!$db) {
    echo json_encode(["success" => false, "message" => "Nuk u mundësua lidhja me serverin e databazës në Render."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["success" => false, "message" => "Të dhëna të pavlefshme (JSON null)."]);
    exit;
}

$orderID    = $data['orderID'] ?? 'NO_ID';
$planName   = $data['plan'] ?? 'Basic';
$email      = $data['email'] ?? 'no@email.com';
$full_name  = $data['full_name'] ?? 'Përdorues FalconAI';
$device_uid = $data['device_uid'] ?? null;

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

    $pkgRes = pg_query_params($db, "SELECT id, max_devices, duration_days FROM packages WHERE name=$1", array($planName));
    if(pg_num_rows($pkgRes) == 0){
        throw new Exception("Plani '$planName' nuk u gjet në sistem. Sigurohu që INSERT-et te save.php u kryen.");
    }
    $pkg = pg_fetch_assoc($pkgRes);
    $package_id    = $pkg['id'];
    $max_devices   = intval($pkg['max_devices']);
    $duration_days = intval($pkg['duration_days']);

    $license_key     = "FALCON-" . strtoupper(bin2hex(random_bytes(3))) . "-" . rand(1000,9999);
    $activation_code = "ACT-" . strtoupper(bin2hex(random_bytes(3))) . "-" . rand(1000,9999);

    pg_query_params($db, 
        "INSERT INTO activation_codes (code, package_id, customer_id, used) VALUES ($1, $2, $3, false)",
        array($activation_code, $package_id, $customer_id)
    );

    pg_query_params($db, 
        "INSERT INTO payments (email, plan, order_id, status, license_key, created_at) VALUES ($1, $2, $3, 'paid', $4, NOW())",
        array($email, $planName, $orderID, $license_key)
    );

    if ($device_uid) {
        $devRes = pg_query_params($db, "SELECT id FROM devices WHERE device_uid=$1", array($device_uid));
        if(pg_num_rows($devRes) > 0){
            $devRow = pg_fetch_assoc($devRes);
            $device_id = $devRow['id'];
        } else {
            $insertDev = pg_query_params($db, "INSERT INTO devices (device_uid) VALUES ($1) RETURNING id", array($device_uid));
            $devRow = pg_fetch_assoc($insertDev);
            $device_id = $devRow['id'];
        }

        pg_query_params($db, 
            "INSERT INTO subscriptions (device_id, package_id, customer_id, starts_at, expires_at, active) 
             VALUES ($1, $2, $3, NOW(), NOW() + ($4 || ' days')::interval, true)",
            array($device_id, $package_id, $customer_id, $duration_days)
        );
    }

    pg_query($db, "COMMIT");

    echo json_encode([
        "success" => true,
        "message" => "Pagesa u procesua! Ruajeni kodin e aktivizimit.",
        "license_key" => $license_key,
        "activation_code" => $activation_code
    ]);

} catch (Exception $e) {
    pg_query($db, "ROLLBACK");
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

pg_close($db);
?>