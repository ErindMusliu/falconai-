<?php
$db_url = getenv('DATABASE_URL');

try {
    if ($db_url) {
        $parts = parse_url($db_url);
        $dsn = sprintf("pgsql:host=%s;port=%d;dbname=%s", 
            $parts['host'], 
            $parts['port'] ?? 5432, 
            ltrim($parts['path'], '/')
        );
        
        $pdo = new PDO($dsn, $parts['user'], $parts['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } else {
        die("DATABASE_URL mungon ne Environment Variables!");
    }
} catch (PDOException $e) {
    error_log("GABIM LIDHJEJE: " . $e->getMessage());
    die("Gabim teknik ne lidhje me databazen.");
}
?>
