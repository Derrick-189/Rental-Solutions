<?php
// Database configuration for InfinityFree
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'hostel_booking';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// // Security: Prevent SQL injection
// function sanitize_input($conn, $data) {
//     if ($data === null) {
//         return '';
//     }
//     return mysqli_real_escape_string($conn, htmlspecialchars(trim((string)$data)));
// }
?>