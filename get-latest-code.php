<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once 'db.php';

$email = $_GET['email'] ?? '';

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email missing']);
    exit;
}

$stmt = $pdo->prepare("SELECT license_key FROM payments WHERE email = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$email]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo json_encode(['success' => true, 'code' => $row['license_key']]);
} else {
    echo json_encode(['success' => false]);
}
