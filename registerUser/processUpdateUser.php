<?php
session_start();
require_once("DatabaseConnection.php");

// Ensure database connection is valid
if (!$conn) {
    $_SESSION['error'] = "Database connection failed.";
    header("Location: update_user.php?id=" . ($_POST['id'] ?? 0));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid CSRF token.";
        header("Location: update_user.php?id=" . ($_POST['id'] ?? 0));
        exit();
    }

    // Sanitize and validate inputs
    $id = intval($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $firstname = trim($_POST['firstname'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $dob = $_POST['dateOfBirth'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $position = $_POST['position'] ?? '';
    $password = trim($_POST['password'] ?? '');

    // Validate required fields
    if (empty($id) || empty($username) || empty($firstname) || empty($surname) || empty($dob) || empty($email) || empty($phone) || empty($gender) || empty($position)) {
        $_SESSION['error'] = "All fields except password are required.";
        header("Location: update_user.php?id=" . $id);
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
        header("Location: update_user.php?id=" . $id);
        exit();
    }

    // Validate phone format
    if (!preg_match('/^(0\d{9}|\+27\d{9})$/', $phone)) {
    $_SESSION['error'] = "Invalid phone number format. Use 0XXXXXXXXX or +27XXXXXXXXX.";
    header("Location: update_user.php?id=" . $id);
    exit();
}


    // Validate gender and position
    $valid_genders = ['Female', 'Male', 'Non-binary', 'Prefer Not to Say', 'Other'];
    $valid_positions = ['Administrative Staff', 'Veterinary Staff', 'Volunteer Staff'];
    if (!in_array($gender, $valid_genders) || !in_array($position, $valid_positions)) {
        $_SESSION['error'] = "Invalid gender or position selected.";
        header("Location: update_user.php?id=" . $id);
        exit();
    }

    // Validate password if provided
    $hashed_password = null;
    if (!empty($password)) {
        if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*]).{5,}$/', $password)) {
            $_SESSION['error'] = "Password must have at least 5 characters, an uppercase letter, a number, and a special character.";
            header("Location: update_user.php?id=" . $id);
            exit();
        }
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    }

    // Prepare and execute SQL query
    try {
        if ($hashed_password) {
            $sql = "UPDATE systemuser SET username=?, firstname=?, surname=?, dateOfBirth=?, email=?, phone=?, gender=?, position=?, password=? WHERE SystemUser_ID=?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                $_SESSION['error'] = "Failed to prepare SQL query: " . $conn->error;
                error_log("Prepare Error: " . $conn->error . " | SQL: $sql");
                header("Location: update_user.php?id=" . $id);
                exit();
            }
            $stmt->bind_param("sssssssssi", $username, $firstname, $surname, $dob, $email, $phone, $gender, $position, $hashed_password, $id);
        } else {
            $sql = "UPDATE systemuser SET username=?, firstname=?, surname=?, dateOfBirth=?, email=?, phone=?, gender=?, position=? WHERE SystemUser_ID=?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                $_SESSION['error'] = "Failed to prepare SQL query: " . $conn->error;
                error_log("Prepare Error: " . $conn->error . " | SQL: $sql");
                header("Location: update_user.php?id=" . $id);
                exit();
            }
            $stmt->bind_param("ssssssssi", $username, $firstname, $surname, $dob, $email, $phone, $gender, $position, $id);
        }

        if ($stmt->execute()) {
            $_SESSION['success'] = "User updated successfully.";
            header("Location: display_users.php");
        } else {
            $_SESSION['error'] = "Error updating user: " . $stmt->error;
            error_log("Execute Error: " . $stmt->error);
            header("Location: update_user.php?id=" . $id);
        }

        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        error_log("Database Error: " . $e->getMessage());
        header("Location: update_user.php?id=" . $id);
    }

    $conn->close();
    exit();
} else {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: update_user.php?id=" . ($_POST['id'] ?? 0));
    exit();
}
?>