<?php
// The ONE shared database connection. All modules (orders, inventory, payments, reports) use this file.
// While true, a save that fails shows the database's own reason on the page. Set to false on a real website.
define('APP_DEBUG', true);

$host = 'localhost';
$db   = 'avalanche_pastries';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed.");   // do not show the real error to visitors
}
