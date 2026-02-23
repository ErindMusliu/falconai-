<?php
include 'db.php';

echo "<style>
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; font-family: sans-serif; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
    th { background-color: #f4f4f4; }
    h2 { font-family: sans-serif; color: #333; border-bottom: 2px solid #333; padding-bottom: 10px; }
    .status-paid { color: green; font-weight: bold; }
</style>";

function shfaq Tabelen($pdo, $tableName) {
    echo "<h2>Tabela: $tableName</h2>";
    try {
        $stmt = $pdo->query("SELECT * FROM $tableName");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) > 0) {
            echo "<table><thead><tr>";
            foreach (array_keys($rows[0]) as $columnName) {
                echo "<th>" . htmlspecialchars($columnName) . "</th>";
            }
            echo "</tr></thead><tbody>";

            foreach ($rows as $row) {
                echo "<tr>";
                foreach ($row as $key => $value) {
                    $displayValue = ($value === false) ? 'false' : (($value === true) ? 'true' : $value);
                    echo "<td>" . htmlspecialchars($displayValue) . "</td>";
                }
                echo "</tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p>Kjo tabelë është bosh.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>Gabim me tabelën $tableName: " . $e->getMessage() . "</p>";
    }
}

shfaqTabelen($pdo, 'payments');
shfaqTabelen($pdo, 'activation_codes');
shfaqTabelen($pdo, 'packages');
shfaqTabelen($pdo, 'customers');
?>
