<?php
require_once("DatabaseConnection.php");

$id = $_GET['id'];
$sql = "DELETE FROM animal WHERE Animal_ID='$id'";

if ($conn->query($sql) === TRUE) {
    header("Location: display_animals.php");
    exit();
} else {
    echo "Error deleting record: " . $conn->error;
}

$conn->close();
?>
