<?php
include 'db.php';

echo "<h2>Struktura e Databazës Falcon AI</h2>";

try {
    $query = $pdo->query("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'");
    $tables = $query->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        echo "❌ Nuk u gjet asnjë tabelë! Duhet të ekzekutosh skriptin SQL.";
    } else {
        foreach ($tables as $table) {
            $count = $pdo->query("SELECT count(*) FROM $table")->fetchColumn();
            echo "✅ Tabela: <b>$table</b> | Rreshta: $count <br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Gabim: " . $e->getMessage();
}
?>
