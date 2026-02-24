<?php

$host = 'dpg-d6bllv2li9vc73dkbbhg-a.oregon-postgres.render.com';
$port = '5432';
$db   = 'falconai_db_k76d';
$user = 'falconai_db_k76d_user';
$pass = 'sYYhitKQLAMwkELMc5V6SdKRWvFBjOZC';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "<h2 style='color:blue;'>Lidhja me Render PostgreSQL u arrit me sukses!</h2>";
    echo "<h4>Duke filluar procesin e pastrimit...</h4><hr>";

    $query = "SELECT table_name FROM information_schema.tables 
              WHERE table_schema = 'public' 
              AND table_type = 'BASE TABLE'";
    
    $stmt = $pdo->query($query);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $count = 0;
    foreach ($tables as $table) {
        if ($table !== 'packages' && $table !== 'migrations') {
            $pdo->exec("TRUNCATE TABLE \"$table\" RESTART IDENTITY CASCADE");
            
            echo "✅ Tabela [<b>$table</b>] u pastrua dhe ID-të u resetuan.<br>";
            $count++;
        } else {
            echo "🛡️ Tabela [<b>$table</b>] u ruajt (Përjashtuar nga fshirja).<br>";
        }
    }

    echo "<hr><h3 style='color:green;'>Përfundoi! U pastruan gjithsej $count tabela.</h3>";

} catch (PDOException $e) {
    die("<h3 style='color:red;'>Gabim gjatë lidhjes:</h3> " . $e->getMessage());
}
?>
