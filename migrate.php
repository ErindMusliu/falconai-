<?php
header('Content-Type: text/plain');

$url = "postgresql://falconai_db_xeru_user:P3Ld2ygWMWaVyDiVVTRwMxqPSS0cCfaT@dpg-d6i9utcr85hc739v3ss0-a.oregon-postgres.render.com/falconai_db_xeru";

try {
    $dbopts = parse_url($url);
    
    // Marrim te dhenat direkt dhe vendosim 5432 nese porta mungon
    $host = $dbopts["host"];
    $port = isset($dbopts["port"]) ? $dbopts["port"] : "5432";
    $user = $dbopts["user"];
    $pass = $dbopts["pass"];
    $dbname = ltrim($dbopts["path"], '/');

    // DSN String i saktë
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 30
    ]);

    echo "✅ Lidhja me sukses në Oregon!\n\n";

    $sql = "
    CREATE TABLE IF NOT EXISTS packages (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) UNIQUE NOT NULL,
        price NUMERIC(6,2) NOT NULL,
        duration_days INTEGER NOT NULL,
        max_devices INTEGER DEFAULT 2
    );

    CREATE TABLE IF NOT EXISTS customers (
        id SERIAL PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS devices (
        id SERIAL PRIMARY KEY,
        device_uid VARCHAR(50) UNIQUE NOT NULL,
        activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS payments (
        id SERIAL PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        plan VARCHAR(255) NOT NULL,
        order_id VARCHAR(255) UNIQUE NOT NULL,
        status VARCHAR(20) DEFAULT 'paid',
        license_key VARCHAR(255) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS activation_codes (
        id SERIAL PRIMARY KEY,
        code VARCHAR(30) UNIQUE NOT NULL,
        package_id INTEGER NOT NULL REFERENCES packages(id),
        customer_id INTEGER REFERENCES customers(id),
        used BOOLEAN DEFAULT FALSE,
        expires_at TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS subscriptions (
        id SERIAL PRIMARY KEY,
        device_id INTEGER NOT NULL REFERENCES devices(id),
        package_id INTEGER NOT NULL REFERENCES packages(id),
        customer_id INTEGER REFERENCES customers(id),
        starts_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NOT NULL,
        active BOOLEAN DEFAULT TRUE
    );

    INSERT INTO packages (name, price, duration_days, max_devices)
    VALUES 
        ('Basic', 4.99, 30, 2),
        ('Standard', 7.99, 30, 2),
        ('Pro', 9.99, 30, 2),
        ('Premium', 14.99, 30, 2)
    ON CONFLICT (name) DO NOTHING;
    ";

    $pdo->exec($sql);
    echo "✅ Tabelat u krijuan me sukses!\n";
    echo "✅ Paketat u insertuan!";

} catch (PDOException $e) {
    die("❌ Gabim: " . $e->getMessage());
}
?>
