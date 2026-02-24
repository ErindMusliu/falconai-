<?php
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
    echo json_encode(["success" => false, "message" => "Mungon kodi."]);
    exit;
}

$db_url = getenv('DATABASE_URL');

if ($db_url) {
    $dbopts = parse_url($db_url);
    $host = $dbopts["host"];
    $port = $dbopts["port"];
    $user = $dbopts["user"];
    $pass = $dbopts["pass"];
    $dbname = ltrim($dbopts["path"], '/');
} else {
    $host     = getenv('DB_HOST') ?: "HOSTI_YT_I_RI"; 
    $port     = getenv('DB_PORT') ?: "5432";
    $dbname   = getenv('DB_NAME') ?: "EMRI_I_DB"; 
    $user     = getenv('DB_USER') ?: "USERI_I_RI";
    $password = getenv('DB_PASS') ?: "PASSWORD_I_RI"; 
}

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $pdo->prepare("SELECT ac.*, p.duration_days, p.name AS package_name 
                           FROM activation_codes ac 
                           JOIN packages p ON ac.package_id = p.id 
                           WHERE ac.code = :code LIMIT 1");
    $stmt->execute(['code' => $license_key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $is_used = ($row['used'] === 't' || $row['used'] == 1 || $row['used'] === true);
        
        if ($is_used) {
            if (!empty($device_id) && trim($row['device_id']) !== $device_id) {
                echo json_encode(["success" => false, "message" => "Ky kod eshte ne nje pajisje tjeter."]);
            } else {
                echo json_encode([
                    "success" => true,
                    "expires_at" => $row['expires_at'],
                    "package_name" => $row['package_name']
                ]);
            }
        } else {
            $expiry_date = date('Y-m-d H:i:s', strtotime("+" . $row['duration_days'] . " days"));
            $update = $pdo->prepare("UPDATE activation_codes SET used = true, device_id = :dev, expires_at = :exp WHERE code = :code");
            $update->execute(['dev' => $device_id, 'exp' => $expiry_date, 'code' => $license_key]);

            echo json_encode([
                "success" => true,
                "message" => "U aktivizua!",
                "expires_at" => $expiry_date,
                "package_name" => $row['package_name']
            ]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Kodi nuk u gjet."]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Gabim DB: " . $e->getMessage()]);
}
?>
