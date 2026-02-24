<?php
include 'db.php';

echo "<!DOCTYPE html>
<html lang='sq'>
<head>
    <meta charset='UTF-8'>
    <title>Falcon AI - Database Explorer</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; margin: 0; padding: 20px; }
        .container { max-width: 1300px; margin: auto; }
        h1 { color: #1a73e8; border-bottom: 3px solid #1a73e8; padding-bottom: 10px; }
        .section { background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 40px; }
        h2 { color: #202124; font-size: 1.4rem; margin-top: 0; display: flex; align-items: center; }
        h2::before { content: '📊'; margin-right: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e0e0e0; font-size: 14px; }
        th { background: #f8f9fa; color: #5f6368; font-weight: 600; text-transform: uppercase; font-size: 12px; }
        tr:hover { background-color: #f1f3f4; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; display: inline-block; }
        .status-active { background: #e6f4ea; color: #137333; }
        .status-expired { background: #fce8e6; color: #c5221f; }
        .status-used { background: #feefc3; color: #b05e00; }
        .id-cell { font-family: 'Courier New', monospace; font-weight: bold; color: #1a73e8; }
    </style>
</head>
<body>

<div class='container'>
    <h1>🚀 Falcon AI - Menaxhimi i Sistemit</h1>";

$tables = [
    'customers' => '👤 Klientët',
    'packages' => '📦 Paketat',
    'activation_codes' => '🔑 Kodet e Aktivizimit',
    'devices' => '📱 Pajisjet',
    'subscriptions' => '💳 Abonimet'
];

foreach ($tables as $table => $title) {
    echo "<div class='section'>";
    echo "<h2>$title</h2>";

    try {
        $stmt = $pdo->query("SELECT * FROM \"$table\" ORDER BY id DESC LIMIT 50");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) > 0) {
            echo "<table><thead><tr>";
            foreach (array_keys($rows[0]) as $col) {
                echo "<th>" . str_replace('_', ' ', $col) . "</th>";
            }
            echo "</tr></thead><tbody>";

            foreach ($rows as $row) {
                echo "<tr>";
                foreach ($row as $key => $val) {
                    $displayVal = htmlspecialchars($val ?? '-');
                    
                    if ($key == 'used' || $key == 'status') {
                        $class = ($val === 'active' || $val === 't' || $val === true) ? 'status-active' : 'status-expired';
                        $text = ($val === 't' || $val === true) ? 'PËRDORUR' : strtoupper($val);
                        $displayVal = "<span class='badge $class'>$text</span>";
                    }
                    
                    if ($key == 'code' || $key == 'device_id') {
                        $displayVal = "<span class='id-cell'>$displayVal</span>";
                    }

                    echo "<td>$displayVal</td>";
                }
                echo "</tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p style='color: #70757a; font-style: italic;'>Nuk ka të dhëna në këtë tabelë.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Gabim gjatë leximit të tabelës $table: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
}

echo "</div></body></html>";
