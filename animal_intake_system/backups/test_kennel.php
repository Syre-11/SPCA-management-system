<?php
// test_kennel.php
include 'DatabaseConnection.php';

// Check if kennel table exists and has data
$sql = "SHOW TABLES LIKE 'kennel'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "Kennel table exists.<br>";
    
    // Check the structure of the kennel table
    $sql = "DESCRIBE kennel";
    $result = $conn->query($sql);
    
    echo "Kennel table structure:<br>";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "<br>";
    }
    
    // Check if there's any data in the kennel table
    $sql = "SELECT * FROM kennel LIMIT 5";
    $result = $conn->query($sql);
    
    echo "<br>Sample kennel data:<br>";
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