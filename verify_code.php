<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

error_reporting(0);
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$license = trim($data['activation_code'] ?? '');
$deviceId = trim($data['device_id'] ?? '');

if (empty($license)) {
    echo json_encode(["success" => false, "message" => "Ju lutem vendosni kodin."]);
    exit;
}

try {
    $pdo->beginTransaction();

    $query = "SELECT ac.*, p.name as package_name 
              FROM activation_codes ac 
              JOIN packages p ON ac.package_id = p.id 
              WHERE ac.code = ? LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$license]);
    $row = $stmt->fetch();

    if ($row) {
        if ($row['used'] && $row['device_id'] !== $deviceId) {
            echo json_encode(["success" => false, "message" => "Ky kod eshte i lidhur me nje pajisje tjeter."]);
            $pdo->rollBack();
            exit;
        }

        if (!$row['used']) {
            $stmtDev = $pdo->prepare("INSERT INTO devices (device_uid) VALUES (?) ON CONFLICT (device_uid) DO NOTHING");
            $stmtDev->execute([$deviceId]);

            $update = $pdo->prepare("UPDATE activation_codes SET used = true, device_id = ? WHERE code = ?");
            $update->execute([$deviceId, $license]);
            
            $stmtSub = $pdo->prepare("INSERT INTO subscriptions (device_id, package_id, customer_id, expires_at) 
                                      SELECT d.id, ?, ?, ? FROM devices d WHERE d.device_uid = ? LIMIT 1");
            $stmtSub->execute([$row['package_id'], $row['customer_id'], $row['expires_at'], $deviceId]);
        }

        $pdo->commit();

        echo json_encode([
            "success" => true,
            "message" => "Aktivizimi u krye me sukses",
            "expires_at" => $row['expires_at'],
            "package_name" => $row['package_name']
        ]);

    } else {
        echo json_encode(["success" => false, "message" => "Kodi është i pasaktë."]);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["success" => false, "message" => "Gabim teknik: " . $e->getMessage()]);
}
?>
