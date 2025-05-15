<?php
$host = 'localhost'; // Replace with your host
$user = 'root';              // your MySQL username
$pass = '';                  // your MySQL password ('' if none)
$database = 'weblaptop';

// Create connection
$conn = new mysqli($host, $user, $pass, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>