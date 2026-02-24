<?php
ini_set('display_errors', 0);
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
    echo json_encode(["success" => false, "message" => "Mungon kodi i aktivizimit."]);
    exit;
}

$db_host = "dpg-d6bllv2li9vc73dkbbhg-a";
$db_user = "falconai_db_k76d_user";
$db_pass = "sYYhitKQLAMwkELMc5V6SdKRWvFBjOZC";
$db_name = "falconai_db_k76d";

try {
    $dsn = "pgsql:host=$db_host;port=5432;dbname=$db_name;sslmode=disable";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $sql = "SELECT ac.*, p.duration_days, p.name AS package_name 
            FROM activation_codes ac 
            JOIN packages p ON ac.package_id = p.id 
            WHERE ac.code = :code LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['code' => $license_key]);
    $row = $stmt->fetch();

    if ($row) {
        $is_used = ($row['used'] === 't' || $row['used'] == 1 || $row['used'] === true);

        if ($is_used) {
            if (!empty($device_id) && trim($row['device_id']) !== $device_id) {
                echo json_encode(["success" => false, "message" => "Ky kod është aktiv në një pajisje tjetër."]);
            } else if (time() > strtotime($row['expires_at'])) {
                echo json_encode(["success" => false, "message" => "Licenca ka skaduar."]);
            } else {
                echo json_encode([
                    "success" => true,
                    "message" => "Mirëseerdhët!",
                    "expires_at" => $row['expires_at'],
                    "package_name" => $row['package_name']
                ]);
            }
        } else {
            if (empty($device_id)) {
                echo json_encode(["success" => false, "message" => "Kodi është i vlefshëm. Shkruaj ID-në e pajisjes për aktivizim."]);
                exit;
            }

            $expiry_date = date('Y-m-d H:i:s', strtotime("+" . $row['duration_days'] . " days"));
            
            $update = $pdo->prepare("UPDATE activation_codes SET used = true, device_id = :dev, expires_at = :exp WHERE code = :code");
            $update->execute([
                'dev' => $device_id,
                'exp' => $expiry_date,
                'code' => $license_key
            ]);

            echo json_encode([
                "success" => true,
                "message" => "Aktivizimi u krye me sukses!",
                "expires_at" => $expiry_date,
                "package_name" => $row['package_name']
            ]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Kodi nuk u gjet në sistem."]);
    }

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Gabim DB: " . $e->getMessage()]);
}
?>
