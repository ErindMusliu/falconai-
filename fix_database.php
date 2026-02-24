<?php
include 'db.php';

header("Content-Type: text/plain");

try {
    echo "--- RREGULLIMI I DATABASE-IT ---\n\n";

    echo "Duke rregulluar tabelën 'devices'...\n";
    $pdo->exec("ALTER TABLE devices ADD COLUMN IF NOT EXISTS device_id VARCHAR(255) UNIQUE");
    $pdo->exec("ALTER TABLE devices ADD COLUMN IF NOT EXISTS last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    echo "✅ Tabela 'devices' u përditësua.\n\n";

    echo "Duke rregulluar tabelën 'subscriptions'...\n";
    $pdo->exec("ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS customer_id INTEGER");
    $pdo->exec("ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS package_id INTEGER");
    $pdo->exec("ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    $pdo->exec("ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS end_date TIMESTAMP");
    $pdo->exec("ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'active'");
    echo "✅ Tabela 'subscriptions' u përditësua.\n\n";

    echo "--- PROCESI U KRYE ---";
    echo "\nTani mund të provosh përsëri aktivizimin.";

} catch (Exception $e) {
    echo "❌ GABIM: " . $e->getMessage();
}
?>
