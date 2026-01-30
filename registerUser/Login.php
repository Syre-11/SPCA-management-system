<?php
session_start();

// 1. Include DB connection
include "DatabaseConnection.php";

// 2. Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: LoginUser.php");
    exit();
}

// 3. Get POST data safely
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$position = $_POST['position'] ?? '';

// 4. Input validation
if (empty($username) || empty($password) || empty($position)) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: LoginUser.php");
    exit();
}

// 5. Query to find the user
$sql = "SELECT * FROM systemuser WHERE username = ? AND position = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $position);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // 6. Verify hashed password
    if (password_verify($password, $user['password'])) {
        // Success: set session variables
        $_SESSION['username'] = $user['username'];
        $_SESSION['position'] = $user['position'];

        // 7. Redirect based on position
        switch ($user['position']) {
            case 'Administrative Staff':
                header("Location: ../Cruelty Reports/admin_dashboard.php");
                break;
            case 'Veterinary Staff':
                header("Location: ../medicalrecords/vetdashboard.php");
                break;
            case 'Volunteer Staff':
                header("Location: ../Adopt and Volunteer/volunteer_dashboard.php");
                break;
            default:
                $_SESSION['error'] = "Unknown role.";
                header("Location: LoginUser.php");
        }
        exit();
    } else {
        $_SESSION['error'] = "Invalid credentials. Try again.";
        header("Location: LoginUser.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Invalid credentials. Try again.";
    header("Location: LoginUser.php");
    exit();
}

// 8. Cleanup
$stmt->close();
$conn->close();
?>
