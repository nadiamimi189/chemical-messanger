<?php
/**
 * Database connection settings for Chemical Connect
 * Update these 4 values to match your hosting / XAMPP / WAMP setup.
 */
$DB_HOST = 'localhost';
$DB_NAME = 'chemical_connect';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed. Please import database.sql and check config/db.php. (' . $e->getMessage() . ')');
}
