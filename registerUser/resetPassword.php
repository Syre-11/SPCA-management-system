<?php
session_start();
if (!isset($_SESSION['verified_user'])) {
    header("Location: forgotPassword.php");
    exit();
}
$message = '';
if (isset($_SESSION['error'])) {
    $message = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password | Makhanda SPCA</title>
    <link rel="stylesheet" href="../css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="spca-auth-page spca-app">
    <div class="login-container">
        <!-- Navigation Button (Top Right) -->
        <a href="../frontPage.html" class="nav-btn"><i class="fas fa-home"></i></a>
        <h2>Set New Password</h2>
        <p>Please set a new password for your account</p>
        
        <?php if ($message): ?>
            <p style="color:red;"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        
        <form action="SavePassword.php" method="post">
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="New Password" required>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            </div>
            <button type="submit" class="login-btn">Save Password</button>
        </form>

        <div class="login-footer">
            <a href="LoginUser.php">Back to Login</a>
        </div>
    </div>
</body>
</html>