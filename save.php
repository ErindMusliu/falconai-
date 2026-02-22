<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host     = "switchyard.proxy.rlwy.net"; 
$port     = "22684";
$dbname   = "railway"; 
$user     = "postgres";
$password = "igbUlyPXVgpusTwXgtbwIkpwXoxcpFPd"; 

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $sql = "
    DROP TABLE IF EXISTS activation_codes CASCADE;
    DROP TABLE IF EXISTS subscriptions CASCADE;
    DROP TABLE IF EXISTS payments CASCADE;
    DROP TABLE IF EXISTS devices CASCADE;
    DROP TABLE IF EXISTS customers CASCADE;
    DROP TABLE IF EXISTS packages CASCADE;

    CREATE TABLE packages (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        price NUMERIC(6,2) NOT NULL,
        duration_days INTEGER NOT NULL,
        max_devices INTEGER NOT NULL
    );

    CREATE TABLE customers (
        id SERIAL PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE devices (
        id SERIAL PRIMARY KEY,
        device_uid VARCHAR(50) NOT NULL UNIQUE,
        activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE payments (
        id SERIAL PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        plan VARCHAR(255) NOT NULL,
        order_id VARCHAR(255) NOT NULL UNIQUE,
        status VARCHAR(20) DEFAULT 'paid',
        license_key VARCHAR(255) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE activation_codes (
        id BIGSERIAL PRIMARY KEY,
        code VARCHAR(30) NOT NULL UNIQUE,
        package_id INTEGER NOT NULL REFERENCES packages(id),
        customer_id INTEGER REFERENCES customers(id),
        device_id VARCHAR(50),
        used BOOLEAN DEFAULT FALSE,
        expires_at TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE subscriptions (
        id SERIAL PRIMARY KEY,
        device_id INTEGER NOT NULL REFERENCES devices(id),
        package_id INTEGER NOT NULL REFERENCES packages(id),
        customer_id INTEGER REFERENCES customers(id),
        starts_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NOT NULL,
        active BOOLEAN DEFAULT TRUE
    );

    INSERT INTO packages (name, price, duration_days, max_devices) VALUES 
    ('Basic', 4.99, 30, 1),
    ('Standard', 9.99, 30, 2),
    ('Pro', 14.99, 30, 3),
    ('Premium', 19.99, 30, 4);
    ";

    $pdo->exec($sql);
    echo "✅ Databaza u instalua me sukses në hostin e ri!";

} catch (PDOException $e) {
    echo "❌ Gabim: " . $e->getMessage();
}
?>
