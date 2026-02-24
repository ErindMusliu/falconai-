<?php
$host = 'localhost';
$user = 'username_yt';
$pass = 'password_yt';
$db   = 'emri_i_databazes';

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

$fshire = 0;

foreach ($tables as $table) {
    if ($table !== 'packages' && $table !== 'migrations') {
        if ($conn->query("TRUNCATE TABLE $table")) {
            echo "Tabela [<b>$table</b>] u pastrua me sukses.<br>";
            $fshire++;
        } else {
            echo "Gabim gjatë pastrimit të tabelës $table: " . $conn->error . "<br>";
        }
    } else {
        echo "Tabela [<b>$table</b>] u anashkalua (Mbeti e paprekur).<br>";
    }
}

$conn->query('SET FOREIGN_KEY_CHECKS = 1');

echo "<h3>Përfundoi! U pastruan gjithsej $fshire tabela.</h3>";

$conn->close();
?>
