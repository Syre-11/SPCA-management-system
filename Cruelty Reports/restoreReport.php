<?php
// restoreReport.php
include 'DatabaseConnection.php';

// Check if a report ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("No report ID specified.");
}

$report_id = intval($_GET['id']);

// Prepare update statement to restore the report
$stmt = $conn->prepare("UPDATE crueltyreport SET deleted = 0 WHERE Report_ID = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $report_id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    // Redirect back to View All Reports page with a success message
    header("Location: Viewallreports.php?msg=restored");
    exit();
} else {
    $stmt->close();
    $conn->close();
    die("Failed to restore report: " . $stmt->error);
}
?>