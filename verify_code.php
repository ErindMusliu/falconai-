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
$device_id_str = trim($data["device_id"] ?? $_GET['device'] ?? "");

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
        echo json_encode(["success" => false, "message" => "Kodi nuk u gjet."]);
        exit;
    }

    $is_used = ($row['used'] === 't' || $row['used'] == 1 || $row['used'] === true);

    if ($is_used) {
        if (!empty($device_id_str) && trim($row['device_id']) !== $device_id_str) {
            echo json_encode(["success" => false, "message" => "Ky kod është aktiv në një pajisje tjetër."]);
        } else {
            echo json_encode([
                "success" => true, 
                "message" => "Mirëseerdhët!", 
                "expires_at" => $row['expires_at'], 
                "package_name" => $row['package_name']
            ]);
        }
    } else {
        if (empty($device_id_str)) {
            echo json_encode(["success" => false, "message" => "ID e pajisjes kërkohet."]);
            exit;
        }

        $duration = $row['duration_days'] ?? 30;
        $expiry_date = date('Y-m-d H:i:s', strtotime("+$duration days"));

        try {
            $pdo->beginTransaction();

            $update = $pdo->prepare("UPDATE activation_codes SET used = true, device_id = :dev, expires_at = :exp WHERE code = :code");
            $update->execute(['dev' => $device_id_str, 'exp' => $expiry_date, 'code' => $license_key]);

            $devSql = "INSERT INTO devices (device_id, device_uid, last_login) 
                       VALUES (:dev, :dev, NOW()) 
                       ON CONFLICT (device_id) DO UPDATE SET last_login = NOW() 
                       RETURNING id";
            $stmtDev = $pdo->prepare($devSql);
            $stmtDev->execute(['dev' => $device_id_str]);
            $device_numeric_id = $stmtDev->fetchColumn();

            $subSql = "INSERT INTO subscriptions (customer_id, package_id, device_id, start_date, expires_at, status) 
                       VALUES (:cust, :pkg, :dev_id, NOW(), :exp, 'active')";
            $pdo->prepare($subSql)->execute([
                'cust'   => $row['customer_id'],
                'pkg'    => $row['package_id'],
                'dev_id' => $device_numeric_id,
                'exp'    => $expiry_date
            ]);

            $pdo->commit();
            echo json_encode(["success" => true, "message" => "Aktivizimi u krye me sukses!", "expires_at" => $expiry_date]);

        } catch (Exception $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            echo json_encode(["success" => false, "message" => "Gabim procesimi: " . $e->getMessage()]);
        }
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Gabim DB: " . $e->getMessage()]);
}
