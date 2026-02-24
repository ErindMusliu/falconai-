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

    if (!$row) {
        echo json_encode(["success" => false, "message" => "Kodi nuk u gjet në sistem."]);
        exit;
    }

    $is_used = ($row['used'] === 't' || $row['used'] == 1 || $row['used'] === true);

    if ($is_used) {
        if (!empty($device_id) && trim($row['device_id']) !== $device_id) {
            echo json_encode(["success" => false, "message" => "Ky kod është aktiv në një pajisje tjetër."]);
        } else if (time() > strtotime($row['expires_at'])) {
            echo json_encode(["success" => false, "message" => "Licenca ka skaduar."]);
        } else {
            if (!empty($device_id)) {
                $pdo->prepare("UPDATE devices SET last_login = NOW() WHERE device_id = ?")->execute([$device_id]);
            }
            
            echo json_encode([
                "success" => true,
                "message" => "Mirëseerdhët përsëri!",
                "expires_at" => $row['expires_at'],
                "package_name" => $row['package_name']
            ]);
        }
    } else {
        if (empty($device_id)) {
            echo json_encode(["success" => false, "message" => "Kodi është i vlefshëm. Shkruaj ID-në e pajisjes për aktivizim."]);
            exit;
        }

        $duration = $row['duration_days'] ?? 30;
        $expiry_date = date('Y-m-d H:i:s', strtotime("+$duration days"));

        try {
            $pdo->beginTransaction();

            $update = $pdo->prepare("UPDATE activation_codes SET used = true, device_id = :dev, expires_at = :exp WHERE code = :code");
            $update->execute([
                'dev' => $device_id,
                'exp' => $expiry_date,
                'code' => $license_key
            ]);

            $deviceSql = "INSERT INTO devices (device_id, last_login) VALUES (:dev, NOW()) 
                          ON CONFLICT (device_id) DO UPDATE SET last_login = NOW()";
            $pdo->prepare($deviceSql)->execute(['dev' => $device_id]);

            $subSql = "INSERT INTO subscriptions (customer_id, package_id, start_date, end_date, status) 
                       VALUES (:cust, :pkg, NOW(), :exp, 'active')";
            $pdo->prepare($subSql)->execute([
                'cust' => $row['customer_id'], 
                'pkg'  => $row['package_id'], 
                'exp'  => $expiry_date
            ]);

            $pdo->commit();

            echo json_encode([
                "success" => true,
                "message" => "Aktivizimi u krye me sukses dhe abonimi u regjistrua!",
                "expires_at" => $expiry_date,
                "package_name" => $row['package_name']
            ]);

        } catch (Exception $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            echo json_encode(["success" => false, "message" => "Gabim gjatë procesimit: " . $e->getMessage()]);
        }
    }

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Gabim DB: " . $e->getMessage()]);
}
?>
