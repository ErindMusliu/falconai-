<?php
$host = getenv('DB_HOST') ?: 'localhost'; 
$user = getenv('DB_USERNAME') ?: 'emri_yt';
$pass = getenv('DB_PASSWORD') ?: 'fjalekalimi_yt';
$db   = getenv('DB_DATABASE') ?: 'emri_databazes';

$security_key = "12345"; 

if (!isset($_GET['key']) || $_GET['key'] !== $security_key) {
    die("Akses i ndaluar! Duhet çelësi i sigurisë.");
}

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Lidhja dështoi: " . $conn->connect_error);
}

$conn->query('SET FOREIGN_KEY_CHECKS = 0');

$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

echo "<h2>Duke pastruar databazën në Render...</h2><hr>";

foreach ($tables as $table) {
    if ($table !== 'packages' && $table !== 'migrations') {
        if ($conn->query("TRUNCATE TABLE `$table`")) {
            echo "✅ Tabela [<b>$table</b>] u fshi.<br>";
        } else {
            echo "❌ Gabim te $table: " . $conn->error . "<br>";
        }
    } else {
        echo "🛡️ Tabela [<b>$table</b>] u ruajt.<br>";
    }
}

$conn->query('SET FOREIGN_KEY_CHECKS = 1');
$conn->close();

echo "<hr><h3>Procesi përfundoi.</h3>";
?>
