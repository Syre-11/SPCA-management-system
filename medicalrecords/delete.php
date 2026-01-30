<?php
// recover.php

// Database configuration
$serverName = "";
$user = "";
$password = "";
$database = "";

// Create connection
$conn = new mysqli($serverName, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Recover record if ID is provided
if (isset($_GET['id'])) {
    $record_id = intval($_GET['id']); // Ensure it's an integer
    $sql = "UPDATE medicalrecords SET Hide = 0 WHERE Record_ID = $record_id";

    if ($conn->query($sql)) {
        header("Location: display.php?message=Record+recovered+successfully");
        exit;
    } else {
        header("Location: display.php?error=Error+recovering+record");
        exit;
    }
} else {
    header("Location: display.php");
    exit;
}

$conn->close();
?>
