<?php
declare(strict_types=1);
$host = 'localhost';
$db   = 'glassmart_db';
$user = 'root';
$pass = ''; // Set your MySQL password here if you have one.
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed. Check db.php and confirm MySQL is running and glassmart_db has been created.');
}
