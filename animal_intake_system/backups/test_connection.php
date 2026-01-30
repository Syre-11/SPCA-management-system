<?php
// test_connection.php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing database connection...<br>";

include 'DatabaseConnection.php';

// Check if connection was successful
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
} else {
    echo "Database connection successful!<br>";
}

// Check if kennel table exists
$sql = "SHOW TABLES LIKE 'kennel'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "Kennel table exists.<br>";
    
    // Check if there's any data in the kennel table
    $sql = "SELECT * FROM kennel LIMIT 5";
    $result = $conn->query($sql);
    
    echo "Sample kennel data:<br>";
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "ID: " . $row['Kennel_ID'] . ", Name: " . $row['Kennel_Name'] . "<br>";
        }
    } else {
        echo "No data found in kennel table.<br>";
    }
} else {
    echo "Kennel table does not exist.<br>";
}

$conn->close();
?>