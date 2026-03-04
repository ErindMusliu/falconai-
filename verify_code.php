<?php
ini_set('display_errors', 0);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once 'db.php';

$input = json_decode(file_get_contents("php://input"), true);
$license_key = trim($input["activation_code"] ?? $_GET['code'] ?? "");
$device_uid = trim($input["device_id"] ?? $_GET['device'] ?? "");

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
        echo json_encode(["success" => false, "message" => "Kodi nuk u gjet në sistemin tonë."]);
        exit;
    }

    $is_used = ($row['used'] === 't' || $row['used'] == 1 || $row['used'] === true);

    if ($is_used) {
        if (!empty($device_uid) && trim($row['device_id']) !== $device_uid) {
            echo json_encode([
                "success" => false, 
                "message" => "Ky kod është aktiv në një pajisje tjetër. Kontaktoni mbështetjen."
            ]);
            exit;
        }

        if (time() > strtotime($row['expires_at'])) {
            echo json_encode([
                "success" => false, 
                "message" => "Licenca juaj ka skaduar më " . date('d/m/Y', strtotime($row['expires_at']))
            ]);
            exit;
        }

        echo json_encode([
            "success" => true,
            "message" => "Mirëseerdhët përsëri në FalconAI!",
            "expires_at" => $row['expires_at'],
            "package_name" => $row['package_name']
        ]);

    } 
    else {
        if (empty($device_uid)) {
            echo json_encode(["success" => false, "message" => "ID e pajisjes kërkohet për aktivizim."]);
            exit;
        }

        $duration = $row['duration_days'] ?? 30;
        $expiry_date = date('Y-m-d H:i:s', strtotime("+$duration days"));

        try {
            $pdo->beginTransaction();
            $update = $pdo->prepare("UPDATE activation_codes SET used = true, device_id = :dev, expires_at = :exp WHERE code = :code");
            $update->execute(['dev' => $device_uid, 'exp' => $expiry_date, 'code' => $license_key]);

            $devSql = "INSERT INTO devices (device_uid, activated_at) 
                       VALUES (:dev, NOW()) 
                       ON CONFLICT (device_uid) DO NOTHING 
                       RETURNING id";
            $stmtDev = $pdo->prepare($devSql);
            $stmtDev->execute(['dev' => $device_uid]);
            $device_id_numeric = $stmtDev->fetchColumn();

            if (!$device_id_numeric) {
                $getDev = $pdo->prepare("SELECT id FROM devices WHERE device_uid = ?");
                $getDev->execute([$device_uid]);
                $device_id_numeric = $getDev->fetchColumn();
            }

            $subSql = "INSERT INTO subscriptions (device_id, package_id, customer_id, starts_at, expires_at, active) 
                       VALUES (:dev_id, :pkg, :cust, NOW(), :exp, TRUE)";
            $pdo->prepare($subSql)->execute([
                'dev_id' => $device_id_numeric,
                'pkg'    => $row['package_id'],
                'cust'   => $row['customer_id'],
                'exp'    => $expiry_date
            ]);

            $pdo->commit();

            echo json_encode([
                "success" => true,
                "message" => "Aktivizimi u krye me sukses! Shijoni FalconAI.",
                "expires_at" => $expiry_date,
                "package_name" => $row['package_name']
            ]);

        } catch (Exception $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log("Activation Error: " . $e->getMessage());
            echo json_encode(["success" => false, "message" => "Gabim gjatë procesimit teknik."]);
        }
    }

} catch (PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Gabim në lidhjen me databazën."]);
}
?>
