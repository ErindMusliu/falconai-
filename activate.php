<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$data = json_decode(file_get_contents("php://input"), true);

$license_key = trim($data["activation_code"] ?? $data["license_key"] ?? "");
$device_id = trim($data["device_id"] ?? "");

if (empty($license_key) || empty($device_id)) {
    echo json_encode(["success" => false, "message" => "Kodi ose ID e pajisjes mungon."]);
    exit;
}

$host     = getenv('DB_HOST') ?: "trolley.proxy.rlwy.net"; 
$port     = getenv('DB_PORT') ?: "22626";
$dbname   = getenv('DB_NAME') ?: "railway"; 
$user     = getenv('DB_USER') ?: "postgres";
$password = getenv('DB_PASS') ?: "EKfRjcFXFPXNADxvjfuNQdkcZZxlGhEy"; 

$connection_string = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
$dbconn = pg_connect($connection_string);

if (!$dbconn) {
    echo json_encode(["success" => false, "message" => "Gabim teknik: Nuk u lidh dot me databazën."]);
    exit;
}

try {
    $query = "SELECT ac.*, p.duration_days, p.name AS package_name 
              FROM activation_codes ac 
              JOIN packages p ON ac.package_id = p.id 
              WHERE ac.code = $1 LIMIT 1";

    $result = pg_query_params($dbconn, $query, array($license_key));

    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        
        $is_used = ($row['used'] === 't' || $row['used'] == 1 || $row['used'] === true);

        if ($is_used) {
            if (trim($row['device_id']) !== $device_id) {
                echo json_encode(["success" => false, "message" => "Ky kod është i lidhur me një pajisje tjetër!"]);
                exit;
            }

            $expiry_timestamp = strtotime($row['expires_at']);
            if (time() > $expiry_timestamp) {
                echo json_encode(["success" => false, "message" => "Licenca juaj ka skaduar."]);
                exit;
            }

            echo json_encode([
                "success" => true,
                "message" => "Mirëseerdhët përsëri!",
                "expires_at" => $row['expires_at'],
                "package_name" => $row['package_name']
            ]);

        } else {
            $duration = (int)$row['duration_days'];
            $expiry_date = date('Y-m-d H:i:s', strtotime("+$duration days"));

            $update_query = "UPDATE activation_codes SET used = true, device_id = $1, expires_at = $2 WHERE code = $3";
            $update_result = pg_query_params($dbconn, $update_query, array($device_id, $expiry_date, $license_key));

            if ($update_result) {
                echo json_encode([
                    "success" => true,
                    "message" => "Aktivizimi u krye me sukses!",
                    "expires_at" => $expiry_date,
                    "package_name" => $row['package_name']
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "Gabim gjatë përditësimit të licencës."]);
            }
        }
    } else {
        echo json_encode(["success" => false, "message" => "Kodi i aktivizimit nuk ekziston."]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Gabim i brendshëm i serverit."]);
}

pg_close($dbconn);
?>
