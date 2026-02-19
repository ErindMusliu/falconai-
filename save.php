<?php
header("Content-Type: text/plain");

$db_url = getenv("DATABASE_URL");
if (!$db_url) {
    die("GABIM: Variabla DATABASE_URL nuk është vendosur në Render Environment.");
}

$conn = pg_connect($db_url . "?sslmode=require");

if (!$conn) {
    die("GABIM: Nuk mund të lidhem me databazën.");
}

echo "Lidhja u realizua me sukses...\n";

$queries = [
    "CREATE TABLE IF NOT EXISTS packages (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) UNIQUE NOT NULL,
        price NUMERIC(6,2) NOT NULL,
        duration_days INTEGER NOT NULL,
        max_devices INTEGER DEFAULT 2
    )",

    "CREATE TABLE IF NOT EXISTS customers (
        id SERIAL PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS devices (
        id SERIAL PRIMARY KEY,
        device_uid VARCHAR(50) UNIQUE NOT NULL,
        activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS payments (
        id SERIAL PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        plan VARCHAR(255) NOT NULL,
        order_id VARCHAR(255) UNIQUE NOT NULL,
        status VARCHAR(20) DEFAULT 'paid',
        license_key VARCHAR(255) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS activation_codes (
        id BIGSERIAL PRIMARY KEY,
        code VARCHAR(30) UNIQUE NOT NULL,
        package_id INTEGER NOT NULL REFERENCES packages(id),
        customer_id INTEGER REFERENCES customers(id),
        used BOOLEAN DEFAULT false,
        expires_at TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS subscriptions (
        id SERIAL PRIMARY KEY,
        device_id INTEGER NOT NULL REFERENCES devices(id),
        package_id INTEGER NOT NULL REFERENCES packages(id),
        customer_id INTEGER REFERENCES customers(id),
        starts_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NOT NULL,
        active BOOLEAN DEFAULT true
    )",

    "INSERT INTO packages (name, price, duration_days, max_devices) 
     VALUES 
     ('Basic', 4.99, 30, 1),
     ('Standard', 7.99, 30, 2),
     ('Pro', 9.99, 30, 3),
     ('Premium', 14.99, 30, 5)
     ON CONFLICT (name) DO NOTHING"
];

foreach ($queries as $sql) {
    $result = pg_query($conn, $sql);
    if ($result) {
        echo "[SUKSES] Ekzekutimi: " . substr($sql, 0, 30) . "...\n";
    } else {
        echo "[GABIM] " . pg_last_error($conn) . "\n";
    }
}

pg_close($conn);
echo "\nProcesi përfundoi. Mund ta fshini këtë skedar tani.";
?>
