<?php
session_start();
$message = '';

// 1. Include DB connection
include "DatabaseConnection.php";

// 2. Check request method
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['verified_user'])) {
    $username = $_SESSION['verified_user'];
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 3. Validate inputs
    if (empty($password) || empty($confirm_password)) {
        $message = "Both password fields are required.";
        $_SESSION['error'] = $message;
        header("Location: resetPassword.php");
        exit();
    }

    if ($password !== $confirm_password) {
        $message = "Passwords do not match. Please try again.";
        $_SESSION['error'] = $message;
        header("Location: resetPassword.php");
        exit();
    }

    if (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long.";
        $_SESSION['error'] = $message;
        header("Location: resetPassword.php");
        exit();
    }

    // 4. Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 5. Prepare SQL to update password
    $statement = $conn->prepare("UPDATE systemuser SET password = ? WHERE username = ?");
    if (!$statement) {
        $message = "Database error: " . $conn->error;
        $_SESSION['error'] = $message;
        error_log("Database error: " . $conn->error, 3, 'errors.log');
        header("Location: resetPassword.php");
        exit();
    }

    // 6. Bind parameters
    $statement->bind_param("ss", $hashed_password, $username);

    // 7. Execute update
    if ($statement->execute()) {
        unset($_SESSION['verified_user']);
        $_SESSION['success'] = "Password updated successfully. You can now log in.";
        $statement->close();
        $conn->close();
        header("Location: Login.php");
        exit();
    } else {
        $message = "Error updating password: " . $statement->error;
        $_SESSION['error'] = $message;
        error_log("Error: " . $statement->error, 3, 'errors.log');
        $statement->close();
        $conn->close();
        header("Location: resetPassword.php");
        exit();
    }
} else {
    header("Location: forgotPassword.php");
    exit();
}
?>