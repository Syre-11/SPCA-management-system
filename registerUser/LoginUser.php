<?php
session_start();
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | Makhanda SPCA</title>
  <link rel="stylesheet" href="../css/auth.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="spca-auth-page spca-app">
  <div class="login-container">
    <a href="../frontPage.html" class="nav-btn"><i class="fas fa-home"></i></a>
    <h2>Welcome Back</h2>
    <p>Please log in to continue</p>

    <p id="spca-login-error"><?php if ($error) echo htmlspecialchars($error); ?></p>

    <form action="Login.php" method="post">
      <div class="input-group">
        <i class="fas fa-user"></i>
        <input type="text" name="username" placeholder="Enter Username" required>
      </div>
      <div class="input-group">
        <i class="fas fa-lock"></i>
        <input type="password" name="password" placeholder="Enter Password" required>
      </div>
      <div class="input-group">
        <i class="fas fa-user-tag"></i>
        <select name="position" required>
          <option value="" disabled selected>Select Position</option>
          <option value="Administrative Staff">Administrative Staff</option>
          <option value="Veterinary Staff">Veterinary Staff</option>
          <option value="Volunteer Staff">Volunteer Staff</option>
        </select>
      </div>
      <button type="submit" class="login-btn">Log In</button>
    </form>

    <div class="login-footer">
      <a href="forgotPassword.php">Forgot Password?</a> |
      <a href="register.php">Create an Account</a>
    </div>
  </div>
</body>
</html>
