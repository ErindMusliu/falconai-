<?php
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '5432';
$user = getenv('DB_USERNAME') ?: 'postgres';
$pass = getenv('DB_PASSWORD') ?: '';
$db   = getenv('DB_DATABASE') ?: 'databaza_jote';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "<h2>Duke pastruar PostgreSQL...</h2><hr>";
    
    $query = "SELECT table_name FROM information_schema.tables 
              WHERE table_schema = 'public' 
              AND table_type = 'BASE TABLE'";
    
    $stmt = $pdo->query($query);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $count = 0;
    foreach ($tables as $table) {
        if ($table !== 'packages' && $table !== 'migrations') {
            $pdo->exec("TRUNCATE TABLE \"$table\" RESTART IDENTITY CASCADE");
            
            echo "✅ Tabela [<b>$table</b>] u fshi dhe ID-ja u resetua.<br>";
            $count++;
        } else {
            echo "🛡️ Tabela [<b>$table</b>] u ruajt e paprekur.<br>";
        }
    }

    echo "<hr><h3>Përfundoi! U pastruan $count tabela.</h3>";

} catch (PDOException $e) {
    die("<h3 style='color:red;'>Gabim:</h3> " . $e->getMessage());
}
?>
