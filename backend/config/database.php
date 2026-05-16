<?php
require_once __DIR__ . '/app.php';

// Configuration settings
$host     = 'localhost';         
$dbname   = 'food_bank_app';     // Database Name
$username = 'root';              
$password = '';

// $host     = 'sql309.infinityfree.com';         
// $dbname   = 'if0_41838795_food_bank_app';
// $username = 'if0_41838795';              
// $password = 'Gereuel26';            

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec("SET time_zone = '+08:00'");
    
} catch(PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>
