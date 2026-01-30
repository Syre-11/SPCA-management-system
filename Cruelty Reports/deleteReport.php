<?php
include 'DatabaseConnection.php';

if (isset($_GET['id'])) {
    $report_id = $_GET['id'];

    // Soft delete: set deleted = 1
    $stmt = $conn->prepare("UPDATE crueltyreport SET deleted = 1 WHERE Report_ID = ?");
    $stmt->bind_param("i", $report_id);

    if ($stmt->execute()) {
        header("Location: Viewallreports.php?msg=deleted");
        exit;
    } else {
        echo "Error deleting report: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Invalid report ID.";
}
?>