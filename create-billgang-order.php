<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db.php';

$apiKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6IjI1NDcwODExNSIsImN1c3RvbWVyIjoiRmFsc2UiLCJleHAiOjE3NzUwODIwNzB9.hTA9cpVGHkEB7I54bot1CrIKzl5zsmBdTi3tyBbKPcI';
$productId = 'VENDOS_ID_E_PRODUKTIT_KETU';

$input = json_decode(file_get_contents("php://input"), true);
$email = $input['email'] ?? '';
$plan = $input['plan'] ?? 'Subscription';
$price = (float)($input['price'] ?? 4.99);

if (empty($email)) {
    echo json_encode(["success" => false, "message" => "Ju lutem jepni një email të vlefshëm."]);
    exit;
}

try {
    $url = "https://billgang.com/api/v1/invoices";
    
    $data = [
        "product_id" => $productId,
        "customer_email" => $email,
        "gateway" => "all", 
        "quantity" => 1,
        "custom_fields" => [
            "Plan" => $plan
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json",
        "Accept: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $result = json_decode($response, true);
    curl_close($ch);

    if ($httpCode === 201 || (isset($result['status']) && $result['status'] === 'success')) {
        $invoiceId = $result['data']['id'];
        $checkoutUrl = $result['data']['url'];
      
        $generated_code = "FAI-" . strtoupper(bin2hex(random_bytes(4)));

        $stmt = $pdo->prepare("INSERT INTO payments (email, plan, order_id, status, license_key) VALUES (?, ?, ?, 'pending', ?)");
        $stmt->execute([
            $email, 
            $plan, 
            (string)$invoiceId, 
            $generated_code
        ]);

        echo json_encode([
            "success" => true,
            "checkout_url" => $checkoutUrl,
            "transaction_id" => $invoiceId
        ]);
    } else {
        echo json_encode([
            "success" => false, 
            "message" => "Billgang Error: " . ($result['message'] ?? "Gabim në krijimin e faturës."),
            "debug" => $result
        ]);
    }

} catch (Exception $e) {
    error_log("Billgang Create Error: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Ndodhi një gabim teknik në server."
    ]);
}
?>
