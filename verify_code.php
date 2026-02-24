<?php
ini_set('display_errors', 0);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

include 'db.php';

$input = file_get_contents("php://input");
$data = json_decode($input, true);

$license_key = trim($data["activation_code"] ?? $_GET['code'] ?? "");
$device_id = trim($data["device_id"] ?? $_GET['device'] ?? "");

if (empty($license_key)) {
    echo json_encode(["success" => false, "message" => "Mungon kodi i aktivizimit."]);
    exit;
}

try {
    $sql = "SELECT ac.*, p.duration_days, p.name AS package_name 
            FROM activation_codes ac 
            JOIN packages p ON ac.package_id = p.id 
            WHERE ac.code = :code LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['code' => $license_key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(["success" => false, "message" => "Kodi nuk u gjet në sistem."]);
        exit;
    }

    $is_used = ($row['used'] === 't' || $row['used'] == 1 || $row['used'] === true);

    if ($is_used) {
        if (!empty($device_id) && trim($row['device_id']) !== $device_id) {
            echo json_encode(["success" => false, "message" => "Ky kod është aktiv në një pajisje tjetër."]);
        } else if (!empty($row['expires_at']) && time() > strtotime($row['expires_at'])) {
            echo json_encode(["success" => false, "message" => "Licenca ka skaduar."]);
        } else {
            $pdo->prepare("UPDATE devices SET last_login = NOW() WHERE device_id = ?")->execute([$device_id]);
            
            echo json_encode([
                "success" => true,
                "message" => "Mirëseerdhët!",
                "expires_at" => $row['expires_at'],
                "package_name" => $row['package_name']
            ]);
        }
    } else {
        if (empty($device_id)) {
            echo json_encode(["success" => false, "message" => "Jepni ID-në e pajisjes për aktivizim."]);
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

            $devSql = "INSERT INTO devices (device_id, device_uid, last_login, created_at) 
                       VALUES (:dev, :dev, NOW(), NOW()) 
                       ON CONFLICT (device_id) DO UPDATE SET last_login = NOW()";
            $pdo->prepare($devSql)->execute(['dev' => $device_id]);

            $subSql = "INSERT INTO subscriptions (customer_id, package_id, device_id, start_date, end_date, status) 
                       VALUES (:cust, :pkg, :dev, NOW(), :exp, 'active')";
            $pdo->prepare($subSql)->execute([
                'cust' => $row['customer_id'],
                'pkg'  => $row['package_id'],
                'dev'  => $device_id,
                'exp'  => $expiry_date
            ]);

            $pdo->commit();

            echo json_encode([
                "success" => true,
                "message" => "Aktivizimi u krye me sukses!",
                "expires_at" => $expiry_date,
                "package_name" => $row['package_name']
            ]);

        } catch (Exception $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            echo json_encode(["success" => false, "message" => "Gabim procesimi: " . $e->getMessage()]);
        }
    }

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Gabim Serveri: " . $e->getMessage()]);
}
?>
