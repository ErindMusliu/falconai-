<?php
header("Content-Type: text/plain");

$host = 'dpg-d6bllv2li9vc73dkbbhg-a.oregon-postgres.render.com';
$db   = 'falconai_db_k76d';
$user = 'falconai_db_k76d_user';
$pass = 'sYYhitKQLAMwkELMc5V6SdKRWvFBjOZC';

try {
    $dsn = "pgsql:host=$host;port=5432;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    echo "Duke krijuar tabelën 'channels' nga fillimi...\n";

    $sql = "
        CREATE TABLE IF NOT EXISTS channels (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            url TEXT NOT NULL,
            category VARCHAR(100),
            live_content TEXT,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ";

    $pdo->exec($sql);

    echo "Sukses: Tabela 'channels' u krijua me kolonat live_content dhe last_updated!";

} catch (PDOException $e) {
    echo "Gabim kritik: " . $e->getMessage();
}
?>
