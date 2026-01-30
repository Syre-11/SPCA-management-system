<?php
// ============================
// Database Configuration
// ============================
define("DB_HOST", "IS3-DEV.ICT.RU.AC.ZA");
define("DB_USER", "G23Z9841");
define("DB_PASS", "G23Z9841");
define("DB_NAME", "G23Z9841");

// ============================
// System Information
// ============================
define("SYSTEM_NAME", "Codex");
define("SYSTEM_URL", "http://is3-dev.ict.ru.ac.za/mspca"); // Change to your server URL
define("NO_REPLY_EMAIL", "no-reply@mspca.org.za");
define("ADMIN_EMAIL", "admin@mspca.org.za");

// ============================
// Business Rules & Thresholds
// ============================
// Donations
define("DONATION_FLAG_THRESHOLD", 5);  // Flag donations above R5000

// Cruelty Reports
define("MAX_REPORT_ATTACHMENTS", 5);      // Max evidence files per report
define("REPORT_ALERT_EMAIL", ADMIN_EMAIL); // Who gets notified of new reports

// Adoptions
define("ADOPTION_PENDING_DAYS", 7);      // Adoption pending period
define("MAX_ADOPTION_REQUESTS", 3);       // Max adoption requests per user

// Animals
define("MAX_ANIMAL_AGE", 100);             // Upper limit in years

// ============================
// File Paths
// ============================
define("UPLOADS_DIR", __DIR__ . "/../uploads/");
define("LOGS_DIR", __DIR__ . "/logs/");

// ============================
// User Roles
// ============================
define("ROLE_ADMIN", "Admin");
define("ROLE_VET", "Vet");     
define("ROLE_VOLUNTEER", "Volunteer");

// ============================
// Database Connection
// ============================
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>