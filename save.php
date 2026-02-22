<?php
header("Content-Type: text/plain");

$host     = "trolley.proxy.rlwy.net"; 
$port     = "22626";
$dbname   = "railway"; 
$user     = "postgres";
$password = "EKfRjcFXFPXNADxvjfuNQdkcZZxlGhEy"; 

$connection_string = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
$conn = pg_connect($connection_string);

if (!$conn) {
    die("GABIM: Nuk mund të lidhem me databazën në Railway.");
}

echo "Lidhja me Railway u realizua me sukses...\n\n";

$queries = [
    "CREATE TABLE IF NOT EXISTS packages (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) UNIQUE NOT NULL,
        price NUMERIC(6,2) NOT NULL,
        duration_days INTEGER NOT NULL,
        max_devices INTEGER DEFAULT 2
    )",

    "CREATE TABLE IF NOT EXISTS activation_code (
        id BIGSERIAL PRIMARY KEY,
        code VARCHAR(30) UNIQUE NOT NULL,
        package_id INTEGER NOT NULL REFERENCES packages(id),
        device_id VARCHAR(100), -- ID e pajisjes Android/TV
        used BOOLEAN DEFAULT false,
        expires_at TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS payments (
        id SERIAL PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        plan VARCHAR(255) NOT NULL,
        order_id VARCHAR(255) UNIQUE NOT NULL,
        status VARCHAR(20) DEFAULT 'paid',
        activation_code_id BIGINT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($queries as $sql) {
    $result = pg_query($conn, $sql);
    if ($result) {
        echo "[SUKSES] Ekzekutimi: " . substr(str_replace("\n", " ", $sql), 0, 50) . "...\n";
    } else {
        echo "[GABIM] " . pg_last_error($conn) . "\n";
    }
}

pg_close($conn);
echo "\nStruktura u krijua! Tani mund të përdorni sistemin.";
?>
