<?php
session_start();

// 1. Include DB connection
include "DatabaseConnection.php";


// 3. Get and validate form data 
$username = trim($_POST['username'] ?? '');
$firstname = trim($_POST['firstname'] ?? '');
$surname = trim($_POST['surname'] ?? '');
$dateOfBirth = !empty($_POST['dateOfBirth']) ? $_POST['dateOfBirth'] : null;
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$phone = trim($_POST['phone'] ?? '');
$gender = $_POST['gender'] ?? '';
$position = $_POST['position'] ?? '';

// Validate inputs
$errors = [];

if (empty($firstname)) $errors[] = "First name is required.";
if (empty($surname)) $errors[] = "Surname is required.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email address.";
if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{5,}$/', $password)) {
    $errors[] = "Password must have at least 5 characters, an uppercase letter, a number, and a special character.";  
}
if ($password !== $confirmPassword) {
    $errors[] = "Passwords do not match.";
}
if (!in_array($gender, ['Female', 'Male', 'Non-binary', 'Prefer Not to Say', 'Other'])) $errors[] = "Invalid gender.";
if (!in_array($position, ['Administrative Staff', 'Veterinary Staff', 'Volunteer Staff'])) $errors[] = "Invalid access level.";
if (!preg_match('/^(\+?\d{1,3})?\d{10}$/', $phone)) {
    $errors[] = "Invalid phone number. Must be exactly 10 digits, with optional country code (e.g. +27123456789 or 0123456789).";
}

if (!empty($errors)) {
    $_SESSION['error'] = implode(" ", $errors);
    header("Location: register.php");
    exit();
}

// 4. Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// 5. Prepare SQL
$statement = $conn->prepare("
    INSERT INTO systemuser(username, firstname, surname, dateOfBirth, email, password, phone, gender, position)
    VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$statement) {
    $_SESSION['error'] = "Database error: " . $conn->error;
    error_log("Database error: " . $conn->error, 3, 'errors.log');
    header("Location: register.php");
    exit();
}

// 6. Bind parameters
$statement->bind_param("sssssssss", $username, $firstname, $surname, $dateOfBirth, $email, $hashed_password, $phone, $gender, $position);

// 7. Execute and check
if ($statement->execute()) {
    $_SESSION['success'] = "User created successfully! You can now log in.";
    header("Location: LoginUser.php");
    exit();
} else {
    if ($conn->errno == 1062) {
        $_SESSION['error'] = "User already exists. Please try again.";
    } else {
        $_SESSION['error'] = "Error: " . $statement->error;
        error_log("Error: " . $statement->error, 3, 'errors.log');
    }
    header("Location: register.php");
    exit();
}

// 8. Cleanup
$statement->close();
$conn->close();
?>
