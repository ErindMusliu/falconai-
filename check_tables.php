<?php
include 'db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Falcon AI - Database Explorer</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f9f9f9; padding: 20px; }
        .table-container { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 30px; overflow-x: auto; }
        h2 { color: #2c3e50; border-left: 5px solid #3498db; padding-left: 10px; text-transform: uppercase; font-size: 18px; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #eee; padding: 12px; text-align: left; font-size: 14px; }
        th { background-color: #3498db; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        tr:hover { background-color: #e9f5ff; }
        .empty { color: #888; font-style: italic; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-paid { background: #d4edda; color: #155724; }
        .badge-used { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h1>🚀 Falcon AI - Paneli i Kontrollit të Tabelave</h1>";

try {
    $stmtTables = $pdo->query("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public' ORDER BY tablename");
    $tables = $stmtTables->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        echo "<div class='table-container'><p class='empty'>Nuk u gjet asnjë tabelë në databazë.</p></div>";
    } else {
        foreach ($tables as $table) {
            echo "<div class='table-container'>";
            echo "<h2>Tabela: $table</h2>";

            $stmtData = $pdo->query("SELECT * FROM \"$table\"");
            $rows = $stmtData->fetchAll(PDO::FETCH_ASSOC);

            if (count($rows) > 0) {
                echo "<table><thead><tr>";
                foreach (array_keys($rows[0]) as $col) {
                    echo "<th>" . htmlspecialchars($col) . "</th>";
                }
                echo "</tr></thead><tbody>";

                foreach ($rows as $row) {
                    echo "<tr>";
                    foreach ($row as $key => $val) {
                        $cellValue = $val;
                        if ($val === true || $val === 'true') $cellValue = "<span class='badge badge-used'>PO</span>";
                        if ($val === false || $val === 'false') $cellValue = "<span class='badge badge-paid'>JO</span>";
                        if ($val === 'paid') $cellValue = "<span class='badge badge-badge-paid'>E PAGUAR</span>";
                        
                        echo "<td>" . $cellValue . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</tbody></table>";
            } else {
                echo "<p class='empty'>Kjo tabelë është aktualisht bosh.</p>";
            }
            echo "</div>";
        }
    }
} catch (Exception $e) {
    echo "<div style='color:red; background:#fff1f1; padding:20px; border-radius:8px;'>
            <b>GABIM KRITIK:</b> " . htmlspecialchars($e->getMessage()) . "
          </div>";
}

echo "</body></html>";
