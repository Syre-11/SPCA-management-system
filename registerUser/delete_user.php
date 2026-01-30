<?php
include 'DatabaseConnection.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // make sure it's a number

    $stmt = $conn->prepare("DELETE FROM systemuser WHERE SystemUser_ID = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: display_users.php");
        exit();
    } else {
        echo "Error deleting record: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "No user ID provided.";
}

$conn->close();
?>
