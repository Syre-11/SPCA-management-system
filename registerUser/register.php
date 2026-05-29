<?php
session_start();
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User Registration | Makhanda SPCA</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, Helvetica, sans-serif;
      background: linear-gradient(rgba(26, 227, 204, 0.7), rgba(26, 227, 204, 0.7)),
                  url("images/nav-pictures/Volunteer.jpg") center/cover no-repeat;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }
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
    .form-container {
      position: relative;
      background: white;
      padding: 40px 35px;
      border-radius: 16px;
      width: 100%;
      max-width: 500px;
      box-shadow: 0 15px 35px rgba(0,0,0,0.2);
      text-align: center;
      animation: fadeIn 1s ease;
      box-sizing: border-box;
    }
    .form-container h2 {
      color: #1a237e;
      margin-bottom: 20px;
      font-weight: 700;
    }
    .message {
      padding: 12px;
      margin-bottom: 20px;
      border-radius: 8px;
      font-size: 0.95em;
      font-weight: bold;
    }
    .error { background: #ffcdd2; color: #b71c1c; border: 1px solid #b71c1c; }
    .success { background: #c8e6c9; color: #1b5e20; border: 1px solid #1b5e20; }
    .input-group {
      position: relative;
      margin-bottom: 20px;
    }
    .input-group input,
    .input-group select {
      width: 100%;
      padding: 15px 15px 15px 45px;
      border: 1.5px solid #ddd;
      border-radius: 12px;
      font-size: 15px;
      outline: none;
      box-shadow: inset 0 2px 5px rgba(0,0,0,0.05);
      transition: all 0.3s ease;
      box-sizing: border-box;
    }
    .input-group input:focus,
    .input-group select:focus {
      border-color: #13cebb;
      box-shadow: 0 0 8px rgba(19,206,187,0.4);
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
    .password-criteria {
      text-align: left;
      background: #f5f7fa;
      padding: 12px 15px;
      border-radius: 10px;
      margin-bottom: 20px;
      border: 1px solid #ddd;
    }
    .password-criteria ul {
      list-style-type: none;
      padding: 0;
      margin: 0;
      font-size: 0.9em;
    }
    .password-criteria li {
      margin: 6px 0;
      font-weight: 500;
    }
    .valid { color: green; }
    .invalid { color: red; }
    .button-group {
      text-align: center;
      margin-top: 10px;
    }
    .button-group input[type="submit"],
    .button-group input[type="reset"] {
      padding: 15px 25px;
      margin: 10px 10px 0 10px;
      border: none;
      border-radius: 12px;
      font-weight: bold;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    .button-group input[type="submit"] {
      background: linear-gradient(45deg, #50ffc8, #0ce18c);
      color: white;
    }
    .button-group input[type="submit"]:hover {
      background: white;
      color: #0ed7a1;
      transform: scale(1.05);
    }
    .button-group input[type="reset"] {
      background-color: #cfd8dc;
      color: #000;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>
<div class="form-container">
  <a href="LoginUser.php" class="nav-btn"><i class="fas fa-home"></i></a>
  <h2>Create New User</h2>


  <form action="registerUser.php" method="POST">

   <div class="input-group">
      <i class="fas fa-user"></i>
      <input type="text" name="username" placeholder="username" required>
    </div>

    <div class="input-group">
      <i class="fas fa-user"></i>
      <input type="text" name="firstname" placeholder="First Name" required>
    </div>

    <div class="input-group">
      <i class="fas fa-user"></i>
      <input type="text" name="surname" placeholder="Surname" required>
    </div>

     <div class="input-group">
      <i class="fas fa-user"></i>
      <input type="date" name="dateOfBirth" placeholder="Date Of Birth" required>
    </div>

    <div class="input-group">
      <i class="fas fa-envelope"></i>
      <input type="email" name="email" maxlength="100" placeholder="Email Address" required>
    </div>

    <div class="input-group">
      <i class="fas fa-lock"></i>
      <input type="password" name="password" id="password" maxlength="35" placeholder="Password" required>
    </div>

    <div class="input-group">
  <i class="fas fa-lock"></i>
  <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
</div>

    <div class="password-criteria">
      <ul>
        <li id="length" class="invalid">• At least 5 characters</li>
        <li id="uppercase" class="invalid">• An uppercase letter</li>
        <li id="number" class="invalid">• A number</li>
        <li id="special" class="invalid">• A special character (!@#$%^&*)</li>
      </ul>
    </div>

   <div class="input-group">
  <i class="fas fa-phone"></i>
  <input type="tel" name="phone" maxlength="13"
         placeholder="Phone Number (e.g. +27123456789 or 0123456789)"
         pattern="^(\+?\d{1,3})?\d{10}$"
         title="Enter 10-digit phone number, with optional country code (e.g. +27123456789 or 0123456789)"
         required>
</div>


    <div class="input-group">
      <i class="fas fa-venus-mars"></i>
      <select name="gender" required>
        <option value="" disabled selected>Select Gender</option>
        <option value="Female">Female</option>
        <option value="Male">Male</option>
        <option value="Non-binary">Non-binary</option>
        <option value="Prefer Not to Say">Prefer Not to Say</option>
        <option value="Other">Other</option>
      </select>
    </div>

    <div class="input-group">
      <i class="fas fa-user-tag"></i>
      <select name="position" required>
        <option value="" disabled selected>Select Role</option>
        <option value="Administrative Staff">Administrative Staff</option>
        <option value="Veterinary Staff">Veterinary Staff</option>
        <option value="Volunteer Staff">Volunteer Staff</option>
      </select>
    </div>

    <div class="button-group">
      <input type="submit" value="Create User">
      <input type="reset" value="Clear Form">
    </div>
  </form>
</div>

<script>
  const passwordInput = document.getElementById('password');
const confirmPasswordInput = document.getElementById('confirm_password');
const length = document.getElementById('length');
const uppercase = document.getElementById('uppercase');
const number = document.getElementById('number');
const special = document.getElementById('special');

passwordInput.addEventListener('input', validatePassword);

confirmPasswordInput.addEventListener('input', () => {
  if (confirmPasswordInput.value !== passwordInput.value) {
    confirmPasswordInput.setCustomValidity("Passwords do not match.");
  } else {
    confirmPasswordInput.setCustomValidity("");
  }
});

function validatePassword() {
  const value = passwordInput.value;
  length.className = value.length >= 5 ? 'valid' : 'invalid';
  uppercase.className = /[A-Z]/.test(value) ? 'valid' : 'invalid';
  number.className = /[0-9]/.test(value) ? 'valid' : 'invalid';
  special.className = /[!@#$%^&*]/.test(value) ? 'valid' : 'invalid';

  // You can also check confirm password here to update its validity on password change
  if (confirmPasswordInput.value && confirmPasswordInput.value !== value) {
    confirmPasswordInput.setCustomValidity("Passwords do not match.");
  } else {
    confirmPasswordInput.setCustomValidity("");
  }
}
</script>
</body>
</html>
