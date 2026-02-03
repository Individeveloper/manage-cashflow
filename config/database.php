<?php
// Supabase PostgreSQL Database Configuration
$host = getenv('SUPABASE_HOST') ?: 'localhost';
$port = getenv('SUPABASE_PORT') ?: '5432';
$database = getenv('SUPABASE_DATABASE') ?: 'postgres';
$username = getenv('SUPABASE_USER') ?: 'postgres';
$password = getenv('SUPABASE_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$database",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper functions
function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}
?>
