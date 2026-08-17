<?php
// config.php
$host = 'localhost';
/*$db = 'u552468652_accommodation';
$user = 'u552468652_accommodation';
$pass = 'Accommodation2026';*/

$db = 'accommodation_db';
$user = 'root';
$pass = '';


$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
     PDO::ATTR_EMULATE_PREPARES => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Connection failed: " . $e->getMessage());
}
?>