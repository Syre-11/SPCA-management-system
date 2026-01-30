<?php
session_start();
include "DatabaseConnection.php";

// Ensure user is logged in
if (!isset($_SESSION['position'])) {
    echo "<p>Please log in to view notifications.</p>";
    exit();
}

$role = $_SESSION['position'];

// Handle marking a notification as read
if (isset($_POST['mark_read']) && isset($_POST['notif_id'])) {
    $notif_id = intval($_POST['notif_id']);
    $update_stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND role = ?");
    $update_stmt->bind_param("is", $notif_id, $role);
    $update_stmt->execute();
}

// Fetch unread notifications for the user's role
$stmt = $conn->prepare("SELECT id, message, created_at FROM notifications WHERE role = ? AND is_read = 0 ORDER BY created_at DESC");
$stmt->bind_param("s", $role);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<div class='notifications-container'>";
    echo "<h3>Notifications</h3>";
    while ($row = $result->fetch_assoc()) {
        echo "<div class='notification'>";
        echo "<p>{$row['message']}</p>";
        echo "<small>Received: {$row['created_at']}</small>";
        echo "<form method='post' style='display:inline;'>";
        echo "<input type='hidden' name='notif_id' value='{$row['id']}'>";
        echo "<button type='submit' name='mark_read'>Mark as Read</button>";
        echo "</form>";
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "<p>No new notifications.</p>";
}

$stmt->close();
$conn->close();
?>
