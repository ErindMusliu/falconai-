<?php
include 'db.php';
header("Content-Type: text/plain");

try {
    echo "--- RIKRIJIMI I TABELES DEVICES ---\n\n";

    $pdo->exec("DROP TABLE IF EXISTS devices CASCADE");
    echo "✅ Tabela e vjetër 'devices' u fshish.\n";

    $sql = "CREATE TABLE devices (
        id SERIAL PRIMARY KEY,
        device_id VARCHAR(255) UNIQUE NOT NULL,
        device_uid VARCHAR(255),
        last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "✅ Tabela 'devices' u krijua me sukses me të gjitha kolonat!\n\n";

    echo "Tani provo përsëri aktivizimin.";

} catch (Exception $e) {
    echo "❌ GABIM: " . $e->getMessage();
}
?>
