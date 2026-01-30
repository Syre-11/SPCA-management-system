<?php
session_start();
$message = '';

// 1. Include DB connection
include "DatabaseConnection.php";

// 2. Check request method and verify data
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SESSION['verify_data'])) {
    $username = $_SESSION['verify_data']['username'] ?? '';
    $surname = $_SESSION['verify_data']['surname'] ?? '';

    // 3. Validate inputs
    if (empty($username) || empty($surname)) {
        $message = "Username and surname are required.";
        $_SESSION['error'] = $message;
        header("Location: forgotPassword.php");
        exit();
    }

    // 4. Prepare SQL to verify user
    $statement = $conn->prepare("SELECT username, surname FROM systemuser WHERE username = ? AND surname = ?");
    if (!$statement) {
        $message = "Database error: " . $conn->error;
        $_SESSION['error'] = $message;
        error_log("Database error: " . $conn->error, 3, 'errors.log');
        header("Location: forgotPassword.php");
        exit();
    }

    // 5. Bind parameters
    $statement->bind_param("ss", $username, $surname);

    // 6. Execute query
    $statement->execute();
    $result = $statement->get_result();

    // 7. Check if user exists
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['verified_user'] = $username; // Store username as verified user identifier
        unset($_SESSION['verify_data']);
        $statement->close();
        $conn->close();
        header("Location: resetPassword.php");
        exit();
    } else {
        $message = "Invalid username or surname. Please try again.";
        $_SESSION['error'] = $message;
        $statement->close();
        $conn->close();
        header("Location: forgotPassword.php");
        exit();
    }
} else {
    header("Location: forgotPassword.php");
    exit();
}
?>