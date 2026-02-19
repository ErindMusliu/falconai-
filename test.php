<?php
if (!function_exists('pg_connect')) {
    die("GABIM: Driver-i i PostgreSQL (pgsql) nuk është aktivizuar në php.ini!");
}

$conn = pg_connect("host=localhost port=5432 dbname=falconai_db user=postgres password=FalconAi123!)");

if (!$conn) {
    die("GABIM: Nuk lidhem dot me databazën. Kontrollo nëse PostgreSQL është NDEZUR.");
}

echo "SUKSES: Çdo gjë është në rregull!";
?>