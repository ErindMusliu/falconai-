<?php
header("Content-Type: text/plain");

$host = 'dpg-d6bllv2li9vc73dkbbhg-a.oregon-postgres.render.com';
$db   = 'falconai_db_k76d';
$user = 'falconai_db_k76d_user';
$pass = 'sYYhitKQLAMwkELMc5V6SdKRWvFBjOZC';

try {
    $dsn = "pgsql:host=$host;port=5432;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    echo "Duke filluar përditësimin e tabelës...\n";

    $sql = "
        ALTER TABLE channels ADD COLUMN IF NOT EXISTS live_content TEXT;
        ALTER TABLE channels ADD COLUMN IF NOT EXISTS last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
    ";

    $pdo->exec($sql);

    echo "Sukses: Kolonat 'live_content' dhe 'last_updated' u shtuan me sukses!";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "Njoftim: Kolonat ekzistojnë tashmë në tabelë.";
    } else {
        echo "Gabim gjatë ekzekutimit: " . $e->getMessage();
    }
}
?>
