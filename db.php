<?php

$db_url = getenv('DATABASE_URL');

try {
    if ($db_url) {
        $pdo = new PDO($db_url);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } else {
        die("Gabim: Variabla DATABASE_URL nuk u gjet.");
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    die("Gabim teknik ne lidhje me databazen.");
}

?>
