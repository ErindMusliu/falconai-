<?php
header("Content-Type: application/json");
error_reporting(0);

$db = pg_connect("host=127.0.0.1 dbname=falconai_db user=postgres password=FalconAi123!)");
if (!$db) {
    echo json_encode(["success" => false, "message" => "Nuk u mundësua lidhja me databazën."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$orderID    = $data['orderID'] ?? 'NO_ID';
$planName   = $data['plan'] ?? 'Basic';
$email      = $data['email'] ?? 'no@email.com';
$full_name  = $data['full_name'] ?? 'Unknown';
$device_uid = $data['device_uid'] ?? null;

$result = pg_query_params($db, "SELECT id FROM customers WHERE email=$1", array($email));
if(pg_num_rows($result) > 0){
    $customer = pg_fetch_assoc($result);
    $customer_id = $customer['id'];
} else {
    $insertCustomer = pg_query_params($db, 
        "INSERT INTO customers (full_name, email) VALUES ($1, $2) RETURNING id", 
        array($full_name, $email)
    );
    if(!$insertCustomer){
        echo json_encode(["success"=>false, "message"=>"Gabim gjatë krijimit të customer: ".pg_last_error($db)]);
        exit;
    }
    $row = pg_fetch_assoc($insertCustomer);
    $customer_id = $row['id'];
}

$pkgRes = pg_query_params($db, "SELECT id, max_devices, duration_days FROM packages WHERE name=$1", array($planName));
if(pg_num_rows($pkgRes) == 0){
    echo json_encode(["success" => false, "message" => "Plani nuk u gjet."]);
    exit;
}
$pkg = pg_fetch_assoc($pkgRes);
$package_id   = $pkg['id'];
$max_devices  = intval($pkg['max_devices']);
$duration_days= intval($pkg['duration_days']);

$check = pg_query_params($db, "SELECT COUNT(*) AS device_count FROM subscriptions WHERE customer_id=$1 AND package_id=$2 AND active=true", array($customer_id, $package_id));
$countRow = pg_fetch_assoc($check);
$device_count = intval($countRow['device_count']);
if($device_count >= $max_devices){
    echo json_encode([
        "success" => false,
        "message" => "Maksimumi i pajisjeve për këtë plan është $max_devices."
    ]);
    exit;
}

$device_id = null;
if ($device_uid) {
    $devRes = pg_query_params($db, "SELECT id FROM devices WHERE device_uid=$1", array($device_uid));
    if(pg_num_rows($devRes) > 0){
        $devRow = pg_fetch_assoc($devRes);
        $device_id = $devRow['id'];
    } else {
        $insertDev = pg_query_params($db, "INSERT INTO devices (device_uid) VALUES ($1) RETURNING id", array($device_uid));
        if(!$insertDev){
            echo json_encode(["success"=>false, "message"=>"Gabim gjatë krijimit të pajisjes: ".pg_last_error($db)]);
            exit;
        }
        $devRow = pg_fetch_assoc($insertDev);
        $device_id = $devRow['id'];
    }
}

$license_key     = "FALCON-" . bin2hex(random_bytes(4)) . "-" . rand(1000,9999);
$activation_code = "FALCON-" . bin2hex(random_bytes(4)) . "-" . rand(1000,9999);

$codeResult = pg_query_params(
    $db,
    "INSERT INTO activation_codes (code, package_id, customer_id, used) VALUES ($1, $2, $3, false) RETURNING id",
    array($activation_code, $package_id, $customer_id)
);
if(!$codeResult){
    echo json_encode(["success"=>false, "message"=>"Gabim gjatë krijimit të activation code: ".pg_last_error($db)]);
    exit;
}

$query = "INSERT INTO payments (email, plan, order_id, status, license_key, created_at) VALUES ($1,$2,$3,'paid',$4,NOW())";
$result = pg_query_params($db, $query, array($email, $planName, $orderID, $license_key));
if(!$result){
    echo json_encode(["success"=>false, "message"=>"Gabim gjatë regjistrimit të pagesës: ".pg_last_error($db)]);
    exit;
}

if($device_id){
    $subResult = pg_query_params(
        $db,
        "INSERT INTO subscriptions (device_id, package_id, customer_id, starts_at, expires_at, active) VALUES ($1,$2,$3,NOW(), NOW() + ($4 || ' days')::interval,true)",
        array($device_id, $package_id, $customer_id, $duration_days)
    );
    if(!$subResult){
        echo json_encode(["success"=>false, "message"=>"Gabim gjatë krijimit të subscription: ".pg_last_error($db)]);
        exit;
    }
}

echo json_encode([
    "success" => true,
    "license_key" => $license_key,
    "activation_code" => $activation_code
]);
?>