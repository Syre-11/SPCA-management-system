<?php
session_start();
require_once("DatabaseConnection.php");

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Handle messages
$message = '';
$messageClass = '';
if (isset($_SESSION['error'])) {
    $message = $_SESSION['error'];
    $messageClass = 'error';
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $message = $_SESSION['success'];
    $messageClass = 'success';
    unset($_SESSION['success']);
}

// Get user ID from query string
$id = intval($_GET['id'] ?? 0);
if (!$id) {
    die("User ID missing in URL.");
}

// Fetch user details
try {
    $sql = "SELECT * FROM systemuser WHERE SystemUser_ID = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing query: " . $conn->error);
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        die("User not found.");
    }

    $user = $result->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User | Makhanda SPCA</title>
    <link rel="stylesheet" href="animal_records_theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .form-container {
            background: white;
            padding: 40px 35px;
            border-radius: 8px;
            width: 100%;
            max-width: 700px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            text-align: center;
            box-sizing: border-box;
        }

        .form-container h2 {
            color: #1a237e;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
            width: 100%;
        }

        .input-group input,
        .input-group select {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            outline: none;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }

        .input-group input:focus,
        .input-group select:focus {
            border-color: #4CAF50;
        }

        .input-group i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            font-size: 16px;
        }

        .button-group {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .button-group input[type="submit"],
        .button-group input[type="reset"] {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .button-group input[type="submit"] {
            background-color: #4CAF50;
            color: white;
        }

        .button-group input[type="submit"]:hover {
            background-color: #45a049;
        }

        .button-group input[type="submit"]:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .button-group input[type="reset"] {
            background-color: #6c757d;
            color: white;
        }

        .button-group input[type="reset"]:hover {
            background-color: #5a6268;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .back-btn {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: #4CAF50;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
        }

        .back-btn:hover {
            background-color: #45a049;
        }

        .password-criteria {
            text-align: left;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }

        .password-criteria ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
            font-size: 14px;
        }

        .password-criteria li {
            margin: 5px 0;
        }

        .valid { color: #155724; }
        .invalid { color: #721c24; }

        @media (max-width: 768px) {
            .form-container {
                padding: 20px;
                max-width: 90%;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <a href="display_users.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
        <h2>Edit User</h2>

        <?php if ($message): ?>
            <div class="message <?= htmlspecialchars($messageClass); ?>"><?= htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form action="processUpdateUser.php" method="POST">
            <input type="hidden" name="id" value="<?= htmlspecialchars($user['SystemUser_ID']); ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="username" value="<?= htmlspecialchars($user['username']); ?>" required aria-label="Username">
            </div>

            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="firstname" value="<?= htmlspecialchars($user['firstname']); ?>" required aria-label="First Name">
            </div>

            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="surname" value="<?= htmlspecialchars($user['surname']); ?>" required aria-label="Surname">
            </div>

            <div class="input-group">
                <i class="fas fa-calendar"></i>
                <input type="date" name="dateOfBirth" value="<?= htmlspecialchars($user['dateOfBirth']); ?>" required aria-label="Date of Birth">
            </div>

            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required aria-label="Email">
            </div>

            <div class="input-group">
                <i class="fas fa-phone"></i>
                <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']); ?>" required aria-label="Phone Number">
            </div>

            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" maxlength="35" placeholder="New Password (leave blank to keep current)" aria-label="Password">
            </div>

            <div class="password-criteria">
                <ul>
                    <li id="length" class="invalid">At least 5 characters</li>
                    <li id="uppercase" class="invalid">An uppercase letter</li>
                    <li id="number" class="invalid">A number</li>
                    <li id="special" class="invalid">A special character (!@#$%^&*)</li>
                </ul>
            </div>

            <div class="input-group">
                <i class="fas fa-venus-mars"></i>
                <select name="gender" required aria-label="Gender">
                    <option value="Female" <?= $user['gender'] == "Female" ? "selected" : ""; ?>>Female</option>
                    <option value="Male" <?= $user['gender'] == "Male" ? "selected" : ""; ?>>Male</option>
                    <option value="Non-binary" <?= $user['gender'] == "Non-binary" ? "selected" : ""; ?>>Non-binary</option>
                    <option value="Prefer Not to Say" <?= $user['gender'] == "Prefer Not to Say" ? "selected" : ""; ?>>Prefer not to say</option>
                    <option value="Other" <?= $user['gender'] == "Other" ? "selected" : ""; ?>>Other</option>
                </select>
            </div>

            <div class="input-group">
                <i class="fas fa-user-tag"></i>
                <select name="position" required aria-label="Position">
                    <option value="Administrative Staff" <?= $user['position'] == "Administrative Staff" ? "selected" : ""; ?>>Administrative Staff</option>
                    <option value="Veterinary Staff" <?= $user['position'] == "Veterinary Staff" ? "selected" : ""; ?>>Veterinary Staff</option>
                    <option value="Volunteer Staff" <?= $user['position'] == "Volunteer Staff" ? "selected" : ""; ?>>Volunteer Staff</option>
                </select>
            </div>

            <div class="button-group">
                <input type="submit" value="Update User" id="submit-btn" disabled>
                <input type="reset" value="Reset">
            </div>
        </form>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const submitButton = document.getElementById('submit-btn');
        const length = document.getElementById('length');
        const uppercase = document.getElementById('uppercase');
        const number = document.getElementById('number');
        const special = document.getElementById('special');

        function validatePassword() {
            const password = passwordInput.value;
            const isLengthValid = password.length >= 5 || password.length === 0;
            const isUppercaseValid = /[A-Z]/.test(password) || password.length === 0;
            const isNumberValid = /\d/.test(password) || password.length === 0;
            const isSpecialValid = /[!@#$%^&*]/.test(password) || password.length === 0;

            length.className = isLengthValid ? 'valid' : 'invalid';
            uppercase.className = isUppercaseValid ? 'valid' : 'invalid';
            number.className = isNumberValid ? 'valid' : 'invalid';
            special.className = isSpecialValid ? 'valid' : 'invalid';

            submitButton.disabled = password.length > 0 && !(isLengthValid && isUppercaseValid && isNumberValid && isSpecialValid);
        }

        passwordInput.addEventListener('input', validatePassword);
        validatePassword(); // Run on page load to enable button if password is empty
    </script>
</body>
</html>