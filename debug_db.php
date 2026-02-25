<?php
require 'vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

$url = $_ENV['DATABASE_URL'];
$parsedUrl = parse_url($url);
$host = $parsedUrl['host'];
$user = $parsedUrl['user'];
$pass = $parsedUrl['pass'];
$db = ltrim($parsedUrl['path'], '/');

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SHOW TABLES");
echo "Tables in database '$db':\n";
while ($row = $result->fetch_array()) {
    echo "- " . $row[0] . "\n";
}
$conn->close();
