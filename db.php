<?php
$db_url = getenv('DATABASE_URL');

try {
    if ($db_url) {
        $parts = parse_url($db_url);
        
        $host = $parts['host'];
        $port = $parts['port'] ?? 5432;
        $user = $parts['user'];
        $pass = $parts['pass'];
        $dbname = ltrim($parts['path'], '/');

        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
        
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

    } else {
        throw new Exception("DATABASE_URL nuk u gjet në server.");
    }
} catch (PDOException $e) {
    error_log("GABIM LIDHJEJE DB: " . $e->getMessage());
    
    header('Content-Type: application/json');
    die(json_encode([
        "success" => false, 
        "message" => "Gabim teknik: Nuk u lidhëm dot me bazën e të dhënave."
    ]));
}
