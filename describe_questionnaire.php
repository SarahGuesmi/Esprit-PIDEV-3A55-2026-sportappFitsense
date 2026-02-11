<?php
$dsn = "mysql:host=127.0.0.1;dbname=fitsense";
$user = "root";
$pass = "root";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $stmt = $pdo->query("DESCRIBE questionnaire");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
