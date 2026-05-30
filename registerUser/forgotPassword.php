<?php
session_start();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $surname = $_POST['surname'] ?? '';

    // Store data in session and redirect to verify
    $_SESSION['verify_data'] = ['username' => $username, 'surname' => $surname];
    header("Location: verifyIdentity.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Makhanda SPCA</title>
    <link rel="stylesheet" href="../css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="spca-auth-page spca-app">
    <div class="login-container">
        <!-- Navigation Button (Top Right) -->
        <a href="../frontPage.html" class="nav-btn"><i class="fas fa-home"></i></a>
        <h2>Reset Password</h2>
        <p>Please verify your identity to reset your password</p>
        
        <?php if ($message): ?>
            <p style="color:red;"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        
        <form action="forgotPassword.php" method="post">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="username" placeholder="Enter Username" required>
            </div>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="surname" placeholder="Enter Surname" required>
            </div>
            <button type="submit" class="login-btn">Verify</button>
        </form>

        <div class="login-footer">
            <a href="LoginUser.php">Back to Login</a>
        </div>
    </div>
</body>
</html>