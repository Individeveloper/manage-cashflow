<?php
// Debug database connection
header('Content-Type: text/plain');

echo "=== Railway Database Debug ===\n\n";

echo "Environment Variables:\n";
echo "MYSQLHOST: " . (getenv('MYSQLHOST') ?: '(not set)') . "\n";
echo "MYSQLPORT: " . (getenv('MYSQLPORT') ?: '(not set)') . "\n";
echo "MYSQLDATABASE: " . (getenv('MYSQLDATABASE') ?: '(not set)') . "\n";
echo "MYSQLUSER: " . (getenv('MYSQLUSER') ?: '(not set)') . "\n";
echo "MYSQLPASSWORD: " . (getenv('MYSQLPASSWORD') ? '*****(set)' : '(not set)') . "\n";
echo "MYSQL_DATABASE: " . (getenv('MYSQL_DATABASE') ?: '(not set)') . "\n";

echo "\n=== Testing Connection ===\n";

$host = getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: 'localhost';
$port = getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: '3306';
$database = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: 'railway';
$username = getenv('MYSQLUSER') ?: getenv('MYSQL_USER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: '';

echo "\nUsing:\n";
echo "Host: $host\n";
echo "Port: $port\n";
echo "Database: $database\n";
echo "Username: $username\n";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    echo "\nDSN: $dsn\n";
    
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    echo "\n✅ CONNECTION SUCCESSFUL!\n";
    
    // Test query
    $stmt = $pdo->query("SELECT VERSION() as version");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "MySQL Version: " . $row['version'] . "\n";
    
} catch (PDOException $e) {
    echo "\n❌ CONNECTION FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
}
?>
