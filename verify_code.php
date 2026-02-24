<?php
include 'db.php';
try {
    $pdo->exec("ALTER TABLE devices ALTER COLUMN device_uid DROP NOT NULL");
    
    $pdo->exec("ALTER TABLE devices ALTER COLUMN device_id DROP NOT NULL");

    echo "✅ Databaza u rregullua! Provo tani aktivizimin.";
} catch (Exception $e) {
    echo "❌ Gabim: " . $e->getMessage();
}
?>
