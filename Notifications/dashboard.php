<?php
session_start();

// If user is not logged in, send back to login page
if (!isset($_SESSION['position'])) {
    header("Location: ../registerUser/LoginUser.php");
    exit();
}

// Redirect based on role
switch (strtolower($_SESSION['position'])) {
    case 'Adminstrative Staff':
        header("Location: ../Cruelty Reports/admin_dashboard.php");
        break;
    case 'Veterinary Staff':
        header("Location: ../medicalrecords/vetdashboard.php");
        break;
    case 'Volunteer Staff':
        header("Location: ../Adopt and Volunteer/volunteer_dashboard.php");
        break;
    default:
        header("Location: ../Cruelty Reports/admin_dashboard.php");
}
exit();
?>