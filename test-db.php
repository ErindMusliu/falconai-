<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$host     = "trolley.proxy.rlwy.net"; 
$port     = "22626";
$dbname   = "railway"; 
$user     = "postgres";
$password = "EKfRjcFXFPXNADxvjfuNQdkcZZxlGhEy"; 

$results = [
    "connection" => false,
    "tables" => [],
    "packages_status" => "Unknown"
];

// 1. Testo Lidhjen
$db = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require");

if ($db) {
    $results["connection"] = true;
    
    // 2. Testo nese tabelat ekzistojne
    $tables_to_check = ['packages', 'payments', 'activation_code', 'customers'];
    
    foreach ($tables_to_check as $table) {
        $query = pg_query($db, "SELECT count(*) FROM information_schema.tables WHERE table_name = '$table'");
        $exists = pg_fetch_result($query, 0, 0);
        $results["tables"][$table] = ($exists > 0) ? "Exists" : "MISSING";
    }

    // 3. Kontrollo nese tabela packages ka te dhena (shume e rendesishme)
    if ($results["tables"]["packages"] === "Exists") {
        $pkg_count_query = pg_query($db, "SELECT count(*) FROM packages");
        $count = pg_fetch_result($pkg_count_query, 0, 0);
        $results["packages_status"] = ($count > 0) ? "Has $count packages" : "EMPTY (This is why it fails!)";
    }

    // 4. Testo nje INSERT fiktiv (Rollback ne fund qe mos te ndryshoje gje)
    pg_query($db, "BEGIN");
    $test_insert = pg_query($db, "INSERT INTO payments (email, plan, order_id, status, license_key) VALUES ('test@test.com', 'Basic', 'TEST-123', 'paid', 'TEST-KEY')");
    
    if ($test_insert) {
        $results["insert_test"] = "Success (Write permission OK)";
    } else {
        $results["insert_test"] = "Failed: " . pg_last_error($db);
    }
    pg_query($db, "ROLLBACK"); // E fshijme menjehere testin

} else {
    $results["error"] = "Could not connect to database.";
}

echo json_encode($results, JSON_PRETTY_PRINT);
pg_close($db);
