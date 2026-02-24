<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$input = file_get_contents("php://input");
$data = json_decode($input, true);

$license_key = trim($data["activation_code"] ?? $_GET['code'] ?? "");
$device_id = trim($data["device_id"] ?? $_GET['device'] ?? "");

if (empty($license_key)) {
    echo json_encode([
        "success" => false, 
        "message" => "Mungon kodi! Përdor URL: verify_code.php?code=KODI_KETU&device=ID_PAJISJES"
    ]);
    exit;
}

$host     = getenv('DB_HOST') ?: "trolley.proxy.rlwy.net"; 
$port     = getenv('DB_PORT') ?: "22626";
$dbname   = getenv('DB_NAME') ?: "railway"; 
$user     = getenv('DB_USER') ?: "postgres";
$password = getenv('DB_PASS') ?: "EKfRjcFXFPXNADxvjfuNQdkcZZxlGhEy"; 

if (!function_exists('pg_connect')) {
    echo json_encode(["success" => false, "message" => "Moduli pg_connect mungon në server."]);
    exit;
}

$connection_string = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
$dbconn = @pg_connect($connection_string);

if (!$dbconn) {
    echo json_encode(["success" => false, "message" => "Lidhja me DB dështoi."]);
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
        $is_used = ($row['used'] === 't' || $row['used'] == 1);

        if ($is_used) {
            if (!empty($device_id) && trim($row['device_id']) !== $device_id) {
                echo json_encode(["success" => false, "message" => "Ky kod është aktiv në një pajisje tjetër."]);
            } else {
                if (time() > strtotime($row['expires_at'])) {
                    echo json_encode(["success" => false, "message" => "Licenca ka skaduar më " . $row['expires_at']]);
                } else {
                    echo json_encode([
                        "success" => true,
                        "message" => "Licenca është aktive.",
                        "expires_at" => $row['expires_at'],
                        "package_name" => $row['package_name']
                    ]);
                }
            }
        } else {
            if (empty($device_id)) {
                echo json_encode(["success" => false, "message" => "Kodi është i vlefshëm, por duhet device_id për aktivizim."]);
                exit;
            }

            $expiry_date = date('Y-m-d H:i:s', strtotime("+" . $row['duration_days'] . " days"));
            $update = pg_query_params($dbconn, 
                "UPDATE activation_codes SET used = true, device_id = $1, expires_at = $2 WHERE code = $3",
                array($device_id, $expiry_date, $license_key)
            );

            echo json_encode([
                "success" => true,
                "message" => "Aktivizimi u krye me sukses!",
                "expires_at" => $expiry_date,
                "package_name" => $row['package_name']
            ]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Kodi nuk ekziston."]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}

pg_close($dbconn);
?>
