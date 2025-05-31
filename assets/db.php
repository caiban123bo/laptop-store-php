<?php
$host = 'localhost'; // Replace with your host
$user = 'root';              // your MySQL username
$pass = '';                  // your MySQL password ('' if none)
$database = 'weblaptop';
$charset = 'utf8mb4';

// Create connection
$dsn = "mysql:host=$host;dbname=$database;charset=$charset";
$conn = new mysqli($host, $user, $pass, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
try {
    $pdo = new PDO($dsn, $user, $pass);
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}
?>