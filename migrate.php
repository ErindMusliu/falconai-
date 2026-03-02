<?php
header('Content-Type: text/plain');

$host     = "dpg-xxxx-a.frankfurt-postgres.render.com"; 
$port     = "5432";
$dbname   = "falconai_db";
$user     = "postgres";
$password = "PASSWORD_YT_KETU";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "✅ Lidhja me sukses!\n";

    $sql = "
    -- 1. Tabela e Paketave
    CREATE TABLE IF NOT EXISTS packages (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) UNIQUE NOT NULL,
        price NUMERIC(6,2) NOT NULL,
        duration_days INTEGER NOT NULL,
        max_devices INTEGER DEFAULT 2
    );

    -- 2. Tabela e Klienteve
    CREATE TABLE IF NOT EXISTS customers (
        id SERIAL PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- 3. Tabela e Pajisjeve (Devices)
    CREATE TABLE IF NOT EXISTS devices (
        id SERIAL PRIMARY KEY,
        device_uid VARCHAR(50) UNIQUE NOT NULL,
        activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- 4. Tabela e Pagesave (Payments)
    CREATE TABLE IF NOT EXISTS payments (
        id SERIAL PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        plan VARCHAR(255) NOT NULL,
        order_id VARCHAR(255) UNIQUE NOT NULL,
        status VARCHAR(20) DEFAULT 'paid',
        license_key VARCHAR(255) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- 5. Tabela e Kodeve te Aktivizimit (Activation Codes)
    CREATE TABLE IF NOT EXISTS activation_codes (
        id SERIAL PRIMARY KEY,
        code VARCHAR(30) UNIQUE NOT NULL,
        package_id INTEGER NOT NULL REFERENCES packages(id),
        customer_id INTEGER REFERENCES customers(id),
        device_id VARCHAR(50),
        used BOOLEAN DEFAULT FALSE,
        expires_at TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- 6. Tabela e Abonimeve (Subscriptions)
    CREATE TABLE IF NOT EXISTS subscriptions (
        id SERIAL PRIMARY KEY,
        device_id INTEGER NOT NULL REFERENCES devices(id),
        package_id INTEGER NOT NULL REFERENCES packages(id),
        customer_id INTEGER REFERENCES customers(id),
        starts_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NOT NULL,
        active BOOLEAN DEFAULT TRUE
    );

    -- 7. Popullimi i paketave nese jane boshe
    INSERT INTO packages (name, price, duration_days, max_devices)
    VALUES 
        ('Basic', 4.99, 30, 2),
        ('Standard', 7.99, 30, 2),
        ('Pro', 9.99, 30, 2),
        ('Premium', 14.99, 30, 2)
    ON CONFLICT (name) DO NOTHING;
    ";

    $pdo->exec($sql);
    echo "✅ Te gjitha tabelat u krijuan me sukses!\n";
    echo "✅ Paketat u insertuan (Basic, Standard, Pro, Premium).\n";

} catch (PDOException $e) {
    die("❌ Gabim: " . $e->getMessage());
}
