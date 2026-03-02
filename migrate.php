<?php
require_once 'db.php';

$tables = ['packages', 'customers', 'payments', 'activation_codes', 'devices', 'subscriptions'];

echo "<html><head><title>FalconAI Admin</title>";
echo "<style>
    body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
    table { border-collapse: collapse; width: 100%; background: white; margin-bottom: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background-color: #007bff; color: white; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    h2 { color: #333; border-left: 5px solid #007bff; padding-left: 10px; }
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; background: #e0e0e0; }
</style></head><body>";

echo "<h1>🚀 FalconAI Database Explorer</h1>";

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT * FROM $table");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h2>Tabela: " . ucfirst($table) . " <small>(" . count($rows) . " rreshta)</small></h2>";

        if (count($rows) > 0) {
            echo "<table><thead><tr>";
            
            foreach (array_keys($rows[0]) as $columnName) {
                echo "<th>" . htmlspecialchars($columnName) . "</th>";
            }
            echo "</tr></thead><tbody>";

            foreach ($rows as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . ($value === null ? "<i>null</i>" : htmlspecialchars($value)) . "</td>";
                }
                echo "</tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p style='color: #888;'>Kjo tabelë është boshe momentalisht.</p><hr>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Gabim me tabelën $table: " . $e->getMessage() . "</p>";
    }
}
echo "</body></html>";
?>
