<?php
/**
 * Database connection - single source of truth
 * XAMPP defaults: root with empty password
 */

$host = 'localhost';
$db   = 'library_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Never expose raw error details to the browser in production
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection failed. Please contact the administrator.');
}