<?php
header("Content-Type: text/plain");

$db_host = "dpg-d6bllv2li9vc73dkbbhg-a";
$db_user = "falconai_db_k76d_user";
$db_pass = "sYYhitKQLAMwkELMc5V6SdKRWvFBjOZC";
$db_name = "falconai_db_k76d";

try {
    $dsn = "pgsql:host=$db_host;port=5432;dbname=$db_name;sslmode=disable";
    $pdo = new PDO($dsn, $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    echo "--- FILLIMI I PROCESIT ---\n";

    $sql_packages = "CREATE TABLE IF NOT EXISTS packages (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100),
        duration_days INTEGER
    )";
    $pdo->exec($sql_packages);
    echo "✅ Tabela 'packages' është gati.\n";

    $sql_codes = "CREATE TABLE IF NOT EXISTS activation_codes (
        id SERIAL PRIMARY KEY,
        code VARCHAR(50) UNIQUE NOT NULL,
        package_id INTEGER,
        device_id VARCHAR(255),
        used BOOLEAN DEFAULT FALSE,
        expires_at TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql_codes);
    echo "✅ Tabela 'activation_codes' është gati.\n";

    $pdo->exec("ALTER TABLE activation_codes ADD COLUMN IF NOT EXISTS device_id VARCHAR(255)");
    $pdo->exec("ALTER TABLE activation_codes ADD COLUMN IF NOT EXISTS used BOOLEAN DEFAULT FALSE");
    $pdo->exec("ALTER TABLE activation_codes ADD COLUMN IF NOT EXISTS expires_at TIMESTAMP");
    echo "✅ Strukturat e kolonave u verifikuan.\n";

    $pdo->exec("INSERT INTO packages (id, name, duration_days) VALUES (1, 'PREMIUM 30 DITE', 30) ON CONFLICT (id) DO NOTHING");
    
    $test_code = 'FALCON-AA558D-2787';
    $stmt = $pdo->prepare("INSERT INTO activation_codes (code, package_id) VALUES (?, 1) ON CONFLICT (code) DO NOTHING");
    $stmt->execute([$test_code]);
    
    echo "✅ Kodi testues '$test_code' u shtua në DB.\n";
    echo "\n--- PROCESI U KRYE ME SUKSES ---\n";
    echo "Tani provo: https://falconai-58jw.onrender.com/verify_code.php?code=$test_code&device=PROVA_1";

} catch (PDOException $e) {
    echo "❌ GABIM: " . $e->getMessage();
}
?>
