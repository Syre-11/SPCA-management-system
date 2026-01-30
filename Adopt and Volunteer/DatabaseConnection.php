<?php
// DatabaseConnection.php - MySQLi-based database connection

$serverName = "";
$user = "";
$password = "";
$database = "";

$conn = new mysqli($serverName, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection to server and database failed: " . $conn->connect_error);
}

// Optional: Make $conn globally accessible if needed
// global $conn; // Uncomment if you need to use $conn in included files without redefining

?>