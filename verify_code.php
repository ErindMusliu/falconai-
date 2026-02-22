<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

error_reporting(0);

include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);
$license = trim($data['license'] ?? $data['activation_code'] ?? '');

if (empty($license)) {
    echo json_encode(["valid" => false, "message" => "Ju lutem vendosni kodin."]);
    exit;
}

try {
    $query = "SELECT id, email, plan, status FROM payments WHERE license_key = ? LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$license]);
    $row = $stmt->fetch();

    if ($row) {
        if ($row['status'] === 'paid') {
            echo json_encode([
                "valid" => true,
                "message" => "Licenca u gjet me sukses",
                "license_id" => (int)$row['id'],
                "email" => $row['email'],
                "plan" => $row['plan']
            ]);
        } else {
            echo json_encode([
                "valid" => false, 
                "message" => "Kjo licencë nuk është e paguar."
            ]);
        }
    } else {
        echo json_encode([
            "valid" => false, 
            "message" => "Kodi është i pasaktë ose nuk ekziston."
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        "valid" => false, 
        "message" => "Gabim teknik gjatë verifikimit."
    ]);
}
?>
