<?php
session_start();
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']); // Clear error after showing
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | Makhanda SPCA</title>
  <link rel="stylesheet" href="#"> <!-- main CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* ===== Body & Background ===== */
    body {
      font-family: Arial, Helvetica, sans-serif;
      margin: 0;
      padding: 0;
      background: linear-gradient(rgba(2, 177, 157, 0.71), rgba(4, 124, 110, 0.73)),
                  url("images/nav-pictures/Volunteer.jpg") center/cover no-repeat;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    /* ===== Navigation Button ===== */
    .nav-btn {
      position: absolute;
      top: 15px;
      right: 15px;
      background: #0ce18c;
      color: white;
      padding: 8px 10px;
      border-radius: 8px;
      font-size: 16px;
      text-decoration: none;
      transition: 0.3s ease;
    }

    .nav-btn:hover {
      background: #13cebb;
      transform: scale(1.1);
    }

    /* ===== Login Container ===== */
    .login-container {
      position: relative;
      background: white;
      padding: 45px 35px;
      border-radius: 15px;
      width: 100%;
      max-width: 400px;
      box-shadow: 0 15px 35px rgba(0,0,0,0.2);
      box-sizing: border-box;
      text-align: center;
      animation: fadeIn 1s ease;
    }

    .login-container h2 {
      font-size: 2rem;
      color: #1a237e;
      margin-bottom: 10px;
      font-weight: 700;
    }

    .login-container p {
      font-size: 14px;
      color: #555;
      margin-bottom: 25px;
    }

    /* ===== Input Groups ===== */
    .input-group {
      position: relative;
      margin-bottom: 20px;
    }

    .input-group input {
      width: 100%;
      padding: 15px 20px 15px 45px;
      border: 1.5px solid #ddd;
      border-radius: 12px;
      font-size: 16px;
      outline: none;
      box-shadow: inset 0 2px 5px rgba(0,0,0,0.05);
      box-sizing: border-box;
      transition: all 0.3s ease;
    }

    .input-group input:focus {
      border-color: #13cebb;
      box-shadow: 0 0 8px rgba(19, 206, 187, 0.4);
    }

    .input-group i {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #888;
      font-size: 18px;
      pointer-events: none;
    }

    /* ===== Login Button ===== */
    .login-btn {
      width: 100%;
      padding: 15px;
      background: linear-gradient(45deg, #50ffc8, #0ce18c);
      border: none;
      border-radius: 12px;
      font-size: 16px;
      font-weight: bold;
      color: white;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .login-btn:hover {
      background: white;
      color: #0ed7a1;
      transform: scale(1.05);
    }

    /* ===== Links ===== */
    .login-footer {
      margin-top: 20px;
      font-size: 14px;
      color: #555;
    }

    .login-footer a {
      color: #1a237e;
      text-decoration: none;
      transition: 0.3s ease;
      margin: 0 8px;
    }

    .login-footer a:hover {
      color: #0ed7a1;
    }

    /* ===== Animations ===== */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* ===== Responsive ===== */
    @media(max-width: 480px) {
      .login-container {
        padding: 35px 25px;
      }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <!-- Navigation Button (Top Right) -->
    <a href="../frontPage.html" class="nav-btn"><i class="fas fa-home"></i></a>
    <h2>Welcome Back</h2>
    <p>Please log in to continue</p>
    
    <p id="spca-login-error" style="color:red;"><?php if ($error) echo htmlspecialchars($error); ?></p>
    
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
      <a href="../registerUser/forgotPassword.php">Forgot Password?</a> | 
      <a href="register.php">Create an Account</a>
    </div>
  </div>
</body>
</html>