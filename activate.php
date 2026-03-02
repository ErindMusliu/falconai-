<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once 'db.php';

$input = file_get_contents("php://input");
$data = json_decode($input, true);

$license_key = trim($data["activation_code"] ?? "");
$device_uid = trim($data["device_id"] ?? "");

if (empty($license_key) || empty($device_uid)) {
    echo json_encode([
        "success" => false, 
        "message" => "Ju lutem jepni kodin e aktivizimit dhe ID-në e pajisjes."
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT ac.*, p.duration_days, p.name AS package_name 
        FROM activation_codes ac 
        JOIN packages p ON ac.package_id = p.id 
        WHERE ac.code = :code 
        LIMIT 1
    ");
    $stmt->execute(['code' => $license_key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(["success" => false, "message" => "Ky kod nuk ekziston në sistemin tonë."]);
        exit;
    }

    $is_used = (bool)$row['used'];
    $current_time = time();

    if ($is_used) {
        if (trim($row['device_id']) !== $device_uid) {
            echo json_encode([
                "success" => false, 
                "message" => "Ky kod është i lidhur me një pajisje tjetër. Kontaktoni mbështetjen."
            ]);
            exit;
        }

        $expiry_timestamp = strtotime($row['expires_at']);
        if ($current_time > $expiry_timestamp) {
            echo json_encode([
                "success" => false, 
                "message" => "Licenca juaj ka skaduar më " . date('d/m/Y', $expiry_timestamp)
            ]);
            exit;
        }

        echo json_encode([
            "success" => true,
            "message" => "Mirëseerdhët përsëri në FalconAI!",
            "expires_at" => $row['expires_at'],
            "package_name" => $row['package_name']
        ]);

    } else {
        $duration = (int)$row['duration_days'];
        $expiry_date = date('Y-m-d H:i:s', strtotime("+$duration days"));

        $pdo->beginTransaction();

        $update = $pdo->prepare("
            UPDATE activation_codes 
            SET used = true, 
                device_id = :dev, 
                expires_at = :exp 
            WHERE code = :code
        ");
        $update->execute([
            'dev' => $device_uid,
            'exp' => $expiry_date,
            'code' => $license_key
        ]);
        
        $stmtDev = $pdo->prepare("
            INSERT INTO devices (device_uid, activated_at) 
            VALUES (:dev, NOW()) 
            ON CONFLICT (device_uid) DO NOTHING
        ");
        $stmtDev->execute(['dev' => $device_uid]);

        $pdo->commit();

        echo json_encode([
            "success" => true,
            "message" => "Aktivizimi u krye me sukses! Shijoni FalconAI.",
            "expires_at" => $expiry_date,
            "package_name" => $row['package_name']
        ]);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Activation Error: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Ndodhi një gabim teknik gjatë procesimit."
    ]);
}
?>
