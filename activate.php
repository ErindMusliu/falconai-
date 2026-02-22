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

$host     = "127.0.0.1"; 
$port     = "5432";
$dbname   = "falconai_db";
$user     = "postgres";
$password = "FalconAi123!)";

$connection_string = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
$dbconn = pg_connect($connection_string);

if (!$dbconn) {
    echo json_encode(["success" => false, "message" => "Gabim në lidhjen me DB."]);
    exit;
}

$query = "SELECT ac.*, p.duration_days 
          FROM activation_codes ac 
          JOIN packages p ON ac.package_id = p.id 
          WHERE ac.code = $1 LIMIT 1";
$result = pg_query_params($dbconn, $query, array($license_key));

if ($result && pg_num_rows($result) > 0) {
    $row = pg_fetch_assoc($result);

    if ($row['used'] == 't' || $row['used'] == 1) {
        if ($row['device_id'] !== $device_id) {
            echo json_encode(["success" => false, "message" => "Ky kod është i lidhur me një pajisje tjetër!"]);
            exit;
        }

        $expiry_timestamp = strtotime($row['expires_at']);
        if (time() > $expiry_timestamp) {
            echo json_encode(["success" => false, "message" => "Licenca juaj ka skaduar më " . $row['expires_at']]);
            exit;
        }

        echo json_encode([
            "success" => true,
            "message" => "Mirëseerdhët përsëri!",
            "expires_at" => $row['expires_at']
        ]);

    } else {
        $duration = (int)$row['duration_days'];
        $expiry_date = date('Y-m-d H:i:s', strtotime("+$duration days"));

        $update_query = "UPDATE activation_codes SET used = true, device_id = $1, expires_at = $2 WHERE code = $3";
        $update_result = pg_query_params($dbconn, $update_query, array($device_id, $expiry_date, $license_key));

        if ($update_result) {
            echo json_encode([
                "success" => true,
                "message" => "Aktivizimi u kreu! Vlefshëm deri më $expiry_date",
                "expires_at" => $expiry_date
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Dështoi përditësimi i licencës."]);
        }
    }
} else {
    echo json_encode(["success" => false, "message" => "Kodi i aktivizimit nuk ekziston."]);
}

pg_close($dbconn);
?>
