<?php
// Database connection and total donation calculation
include 'DatabaseConnection.php';

$totalDonations = 0;
$totalDonors = 0;

// Query to get total donations and number of donors
$sql = "SELECT SUM(Amount) as total, COUNT(DISTINCT donor_id) as donors FROM alldonations";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $totalDonations = $row['total'] ? number_format($row['total'], 2) : '0.00';
    $totalDonors = $row['donors'] ? $row['donors'] : 0;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donate - Paw Prints Animal Rescue</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
       * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background-color: #f9f9f9;
    color: #333;
    line-height: 1.6;
}

/* TOP SECTION 1: Main Navigation Bar */
nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 5%;
    background-color: #03574fb8;
    background: rgba(7, 71, 64, 0.693);
    backdrop-filter: blur(10px);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
}

/* Logo container */
.nav-logo {
    display: flex;
    align-items: center;
    gap: 15px;
}

/* Logo in navigation */
nav img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid white;
}

/* Organization name next to logo */
.nav-logo h2 {
    color: white;
    font-size: 18px;
    font-weight: 600;
    margin: 0;
}

/* Navigation links container */
.nav-links {
    display: flex;
    align-items: center;
}

.nav-links ul {
    list-style-type: none;
    display: flex;
    gap: 35px;
    margin: 0;
    padding: 0;
    align-items: center;
}

.nav-links ul li {
    position: relative;
}

.nav-links ul li a {
    color: white;
    text-decoration: none;
    font-size: 16px;
    font-weight: 500;
    padding: 8px 12px;
    transition: all 0.3s ease;
    border-radius: 4px;
}

.nav-links ul li a:hover {
    background-color: rgba(7, 105, 89, 0.809);
}
.nav-links ul li a::after {
    content: "";
    position: absolute;
    left: 0;
    bottom: 0;
    width: 0;
    height: 2px;
    background: white;
    transition: width 0.3s ease;
}

.nav-links ul li a:hover::after {
    width: 100%;
}

/* Donate button styling */
.donate-btn {
    background-color: #dc2626 !important;
    color: white !important;
    padding: 12px 24px !important;
    border-radius: 6px !important;
    font-weight: 600 !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-left: 20px;
}

.donate-btn:hover {
    background-color: #b91c1c !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(220, 38, 38, 0.3);
}

/* Services dropdown menu */
.Services {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    position: absolute;
    top: 100%;
    left: 0;
    background: #046a5b92;
    min-width: 180px;
    max-height: 0;
    overflow: hidden;
    opacity: 0;
    visibility: hidden;
    transition: all 0.4s ease;
    border-radius: 6px;
    padding: 0;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Show on hover */
.nav-links ul li:hover .Services {
    max-height: 260px;
    opacity: 1;
    visibility: visible;
    padding: 8px 0;
}

/* Dropdown items */
.Services li {
    display: block;
    width: auto;
}

/* Dropdown links */
.Services li a {
    display: block;
    color: white;
    padding: 12px 20px;
    font-size: 14px;
    border-radius: 0;
    white-space: nowrap;
    margin: 0;
}

/* Hover effect for items */
.Services li a:hover {
    background: rgba(255, 255, 255, 0.1);
}

/* Impact Section */
.impact-section {
    background: linear-gradient(135deg, #056f65b8 0%, #034942 100%);
    color: white;
    padding: 60px 5%;
    text-align: center;
    margin: 30px 0;
}

.impact-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.impact-title {
    font-size: 2.5rem;
    margin-bottom: 30px;
    font-weight: 700;
}

.impact-stats {
    display: flex;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
    margin-bottom: 40px;
}

.stat-item {
    background: rgba(255, 255, 255, 0.1);
    padding: 30px;
    border-radius: 15px;
    min-width: 250px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(10px);
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 10px;
    color: #a3e4d7;
}

.stat-label {
    font-size: 1.2rem;
    opacity: 0.9;
}

.impact-message {
    max-width: 800px;
    font-size: 1.2rem;
    line-height: 1.6;
    margin-top: 20px;
    background: rgba(255, 255, 255, 0.1);
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.impact-highlight {
    color: #a3e4d7;
    font-weight: 700;
}

/* Hero Section */
.donation-hero {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 60px 5%;
    background: linear-gradient(to right, #f8f9fa 60%, #e9ecef 40%);
    gap: 40px;
    max-width: 1400px;
    margin: 30px auto;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
}

.hero-content {
    flex: 1;
}

.hero-content h1 {
    font-size: 2.5rem;
    color: #2c3e50;
    margin-bottom: 20px;
}

.hero-content p {
    font-size: 1.1rem;
    color: #555;
    margin-bottom: 15px;
    max-width: 90%;
}

.hero-images {
    flex: 1;
    display: flex;
    gap: 20px;
}

.carousel-container {
    flex: 1;
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.carousel-slide {
    display: flex;
    transition: transform 0.5s ease;
}

.carousel-slide img {
    width: 100%;
    border-radius: 10px;
    object-fit: cover;
}

.carousel-nav {
    position: absolute;
    bottom: 15px;
    left: 0;
    right: 0;
    display: flex;
    justify-content: center;
    gap: 10px;
}

.carousel-nav-btn {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: none;
    background-color: rgba(255, 255, 255, 0.5);
    cursor: pointer;
}

.carousel-nav-btn.active {
    background-color: white;
}

.side-image {
    flex: 1;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.side-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Donation Options */
.donation-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
    padding: 50px 5%;
    max-width: 1400px;
    margin: 0 auto;
}

.donation-option {
    background: white;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.donation-option:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.donation-icon {
    width: 60px;
    height: 60px;
    margin-bottom: 20px;
}

.donation-option h3 {
    font-size: 1.4rem;
    color: #2c3e50;
    margin-bottom: 15px;
}

.donation-option button {
    background-color: #056f65b8;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.donation-option button:hover {
    background-color: #045950;
    transform: translateY(-2px);
}

/* Bank Details */
.bank-details {
    background: #056f65b8;
    color: white;
    padding: 50px 5%;
    text-align: center;
    margin: 30px 0;
}

.bank-details h2 {
    font-size: 2rem;
    margin-bottom: 30px;
}

.bank-details p {
    font-size: 1.1rem;
    margin-bottom: 10px;
}

/* Donation Form */
.donation-form-container {
    max-width: 800px;
    margin: 50px auto;
    padding: 40px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
}

.donation-header {
    text-align: center;
    margin-bottom: 40px;
    position: relative;
}

.donation-header h1 {
    color: #2c3e50;
    margin-bottom: 15px;
    font-weight: 700;
}

.donation-header p {
    color: #7f8c8d;
    font-size: 1.1rem;
}

.form-section {
    margin-bottom: 30px;
}

.section-title {
    font-size: 1.3rem;
    color: #2c3e50;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #ecf0f1;
    font-weight: 600;
}

.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    flex: 1;
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
    color: #34495e;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.3s;
    background-color: #f8f9fa;
}

.form-control:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
    background-color: #fff;
}

.amount-options {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.amount-option {
    padding: 14px 24px;
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    font-weight: 500;
}

.amount-option:hover {
    background: #e8f4fd;
    border-color: #3498db;
}

.amount-option.selected {
    background: #056f65b8;
    color: white;
    border-color: #056f65b8;
}

.payment-method-cards {
    display: flex;
    gap: 20px;
    margin-bottom: 25px;
}

.payment-method {
    flex: 1;
    text-align: center;
    padding: 20px;
    border: 2px solid #ecf0f1;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s;
}

.payment-method:hover {
    border-color: #056f65b8;
    transform: translateY(-3px);
}

.payment-method.selected {
    border-color: #056f65b8;
    background-color: rgba(5, 111, 101, 0.1);
}

.payment-method i {
    font-size: 36px;
    margin-bottom: 15px;
    color: #7f8c8d;
}

.payment-method.selected i {
    color: #056f65b8;
}

.payment-details {
    padding: 25px;
    background: #f8f9fa;
    border-radius: 10px;
    margin-top: 20px;
    display: none;
}

.payment-details.active {
    display: block;
    animation: fadeIn 0.5s;
}

.card-row {
    display: flex;
    gap: 20px;
}

.card-row .form-group {
    flex: 1;
}

.btn-donate {
    width: 100%;
    padding: 18px;
    background: #056f65b8;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1.2rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 20px;
}

.btn-donate:hover {
    background: #045950;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.btn-donate:active {
    transform: translateY(0);
}

.optional-note {
    font-style: italic;
    color: #7f8c8d;
    font-size: 14px;
    margin-top: 5px;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Thank You Section */
.thank-you {
    display: flex;
    justify-content: space-between;
    padding: 60px 5%;
    background: white;
    gap: 40px;
    max-width: 1400px;
    margin: 50px auto;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
}

.thank-you-content {
    flex: 1;
}

.thank-you-content h2 {
    color: #056f65b8;
    font-size: 2rem;
    margin-bottom: 20px;
}

.thank-you-content p {
    color: #555;
    font-size: 1.1rem;
    line-height: 1.8;
}

.thank-you-media {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.thank-you-media img {
    width: 100%;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.thank-you-media iframe {
    width: 100%;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

/* Footer Section */
footer {
    background-color: #056f65b8;
    color: white;
    padding: 40px 20px 20px;
    position: relative;
    margin-top: 60px; /* Added to prevent overlap with nav-container */
}

.footerHeader {
    text-align: center;
    margin-bottom: 30px;
    font-size: 1.8rem;
}

hr {
    border: 0;
    height: 1px;
    background: rgba(255, 255, 255, 0.3);
    margin: 20px 0;
}

/* Footer Logo */
footer a:first-of-type img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(255, 255, 255, 0.5);
    margin-bottom: 15px;
}

/* Footer Grid Layout */
.footer-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

footer ul {
    list-style: none;
}

footer li {
    margin-bottom: 8px;
    font-size: 14px;
}

footer li:first-child {
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 15px;
}

footer a {
    color: white;
    text-decoration: none;
    transition: color 0.3s ease;
}

footer a:hover {
    color: #b3e5fc;
}

/* Social media section */
.social-links {
    list-style: none;
    padding: 0;
    margin: 0;
    text-align: center;
}

.social-links .social-title {
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 10px;
}

/* Icons row */
.social-links .icons {
    display: flex;
    justify-content: center;
    gap: 10px;
}

.social-links .icons a img {
    width: 20px;
    height: 20px;
    transition: transform 0.3s ease;
}

.social-links .icons a img:hover {
    transform: scale(1.15);
}

footer p {
    text-align: center;
    margin-top: 20px;
    font-size: 14px;
}

@media (max-width: 768px) {
    /* Footer adjustments */
    .footer-container {
        grid-template-columns: 1fr;
        text-align: center;
    }

    .social-links {
        align-items: center;
    }

    footer a:first-of-type img {
        margin: 0 auto 20px;
        display: block;
    }
}

/* PayPal Popup Styles */
.paypal-popup {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 2000;
    justify-content: center;
    align-items: center;
}

.paypal-popup.active {
    display: flex;
    animation: fadeIn 0.3s;
}

.paypal-popup-content {
    background: white;
    width: 400px;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    position: relative;
}

.paypal-header {
    background: #0070ba;
    padding: 20px;
    text-align: center;
}

.paypal-logo {
    width: 120px;
}

.paypal-form {
    padding: 25px;
}

.paypal-form .form-group {
    margin-bottom: 20px;
}

.paypal-form label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 500;
}

.paypal-form input {
    width: 100%;
    padding: 14px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.paypal-btn {
    background: #0070ba;
    color: white;
    border: none;
    padding: 15px;
    width: 100%;
    border-radius: 4px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s;
}

.paypal-btn:hover {
    background: #005ea6;
}

.paypal-btn:disabled {
    background: #cccccc;
    cursor: not-allowed;
}

.paypal-footer {
    padding: 15px 25px;
    background: #f5f5f5;
    text-align: center;
    font-size: 14px;
    color: #666;
}

.close-popup {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(255, 255, 255, 0.3);
    color: white;
    border: none;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    font-size: 18px;
    cursor: pointer;
    display: flex;
    justify-content: center;
    align-items: center;
}

.paypal-success {
    color: #28a745;
    font-size: 1rem;
    margin: 15px 0;
    padding: 10px;
    background: #d4edda;
    border-radius: 5px;
    border: 1px solid #c3e6cb;
    text-align: center;
    display: none;
}

.paypal-success.active {
    display: block;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .donation-hero {
        flex-direction: column;
        text-align: center;
    }
    
    .hero-content p {
        max-width: 100%;
    }
    
    .thank-you {
        flex-direction: column;
    }
}

@media (max-width: 768px) {
    /* Navigation adjustments */
    nav {
        padding: 10px 3%;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .nav-logo h2 {
        font-size: 16px;
    }
    
    nav img {
        width: 50px;
        height: 50px;
    }
    
    .nav-links ul {
        gap: 20px;
        flex-wrap: wrap;
    }
    
    .nav-links ul li a {
        font-size: 14px;
        padding: 6px 10px;
    }
    
    .donate-btn {
        padding: 10px 18px !important;
        font-size: 14px !important;
    }
    
    /* Action buttons stack vertically */
    .nav-container {
        flex-direction: column;
        gap: 30px;
        padding: 40px 20px;
        min-height: auto;
    }
            
    .form-row {
        flex-direction: column;
        gap: 0;
    }
    
    .payment-method-cards {
        flex-direction: column;
    }
    
    .card-row {
        flex-direction: column;
        gap: 0;
    }
    
    .hero-images {
        flex-direction: column;
    }
    
    .paypal-popup-content {
        width: 90%;
        margin: 0 5%;
    }
    
    .impact-stats {
        flex-direction: column;
        align-items: center;
        gap: 20px;
    }
    
    .stat-item {
        width: 100%;
        max-width: 300px;
    }
    
    .impact-title {
        font-size: 2rem;
    }
}

    </style>
</head>
<body>
    <!-- PayPal Popup -->
    <div class="paypal-popup" id="paypal-popup">
        <div class="paypal-popup-content">
            <div class="paypal-header">
                <img src="https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_111x69.jpg" class="paypal-logo" alt="PayPal Logo">
            </div>
            
            <div class="paypal-form">
                <div class="form-group">
                    <label for="paypal-email">Email or mobile number</label>
                    <input type="text" id="paypal-email" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="paypal-password">Password</label>
                    <input type="password" id="paypal-password" class="form-control">
                </div>
                
                <button class="paypal-btn" id="paypal-login">Log In</button>
                
                <div class="paypal-success" id="paypal-success">
                    Successfully logged into PayPal
                </div>
            </div>
            
            <div class="paypal-footer">
                By logging in, you agree to PayPal's User Agreement and Privacy Policy.
            </div>
            
            <button class="close-popup" id="close-popup">×</button>
        </div>
    </div>

     <!-- SECTION 1: Main Navigation Bar at Very Top -->
    <nav>
        <div class="nav-logo">
            <a href="#">
                <img src="../images/Logo.png" alt="Makhanda SPCA Logo">
            </a>
            <h2>Paw Prints</h2>
        </div>
        <div class="nav-links" id="navLinks">
            <ul>
                <li><a href="../frontPage.html">Home</a></li>
                <li><a href="../About us/aboutUs.html">About</a></li>
                <li>
                    <a href="#services">Services</a>
                    <ul class="Services">
                        <li><a href="../Adopt and Volunteer/adoption.php">Adopt</a></li>
                        <li><a href="../Adopt and Volunteer/volunteer.php">Volunteer</a></li>
                        <li><a href="../Cruelty Reports/Cruelty Management.php">Report</a></li>
                        <li><a href="../DONATIONS/donationSite.php">Donate</a></li>
                    </ul>
                </li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="../registerUser/LoginUser.php">Login</a></li>
            
            </ul>
        </div>
    </nav>


    <!-- Hero Section -->
    <section class="donation-hero">
        <div class="hero-content">
            <h1>Be a Lifeline for Animals in Need</h1>
            <p>Your contribution provides shelter, treatment, rehabilitation and care for up to 200 companion animals.</p>
            <p>Every cent counts. Every act of kindness makes a difference.</p>
        </div>
        <div class="hero-images">
            <div class="carousel-container">
                <div class="carousel-slide">
                    <img src="https://images.unsplash.com/photo-1543466835-00a7907e9de1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1074&q=80" alt="Happy Dog">
                    <img src="https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1143&q=80" alt="Cute Cat">
                    <img src="https://images.unsplash.com/photo-1583337130417-3346a1be7dee?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1064&q=80" alt="Animal Care">
                </div>
                <div class="carousel-nav">
                    <button class="carousel-nav-btn active"></button>
                    <button class="carousel-nav-btn"></button>
                    <button class="carousel-nav-btn"></button>
                </div>
            </div>
            <div class="side-image">
                <img src="https://images.unsplash.com/photo-1450778869180-41d0601e046e?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwa90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1155&q=80" alt="Donate Hero">
            </div>
        </div>
    </section>

    <!-- NEW: Donation Impact Section -->
    <section class="impact-section">
        <div class="impact-container">
            <h2 class="impact-title">Our Collective Impact</h2>
            
            <div class="impact-stats">
                <div class="stat-item">
                    <div class="stat-value">R<?php echo $totalDonations; ?></div>
                    <div class="stat-label">Total Donations Raised</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-value"><?php echo $totalDonors; ?></div>
                    <div class="stat-label">Generous Donors</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-value">200+</div>
                    <div class="stat-label">Animals Helped</div>
                </div>
            </div>
            
            <div class="impact-message">
                <p>Thanks to supporters like you, we've been able to provide <span class="impact-highlight">shelter, medical care, and love</span> to hundreds of animals in need. Every donation, no matter the size, contributes to our mission of creating a better world for our furry friends.</p>
                <p>Join our community of compassionate donors and help us make an even greater impact!</p>
            </div>
        </div>
    </section>

    <!-- Donation Options Grid -->
    <section class="donation-grid">
        <div class="donation-option">
            <i class="fas fa-credit-card donation-icon"></i>
            <h3>Give by Card</h3>
            <a href="#donation-form-container"><button type="button">Donate</button></a>
        </div>
        <div class="donation-option">
            <i class="fas fa-university donation-icon"></i>
            <h3>Give by Instant EFT</h3>
            <a href="#donation-form-container"><button type="button">Donate</button></a>
        </div>
        <div class="donation-option">
            <i class="fab fa-paypal donation-icon"></i>
            <h3>Give through PayPal</h3>
            <a href="#donation-form-container"><button type="button">Donate</button></a>
        </div>
    </section>

    <!-- Bank Details -->
    <section class="bank-details">
        <h2>EFT Bank Details</h2>
        <p><strong>Bank:</strong> Standard Bank</p>
        <p><strong>Branch:</strong> Blue Route Mall, Tokai</p>
        <p><strong>Branch Code:</strong> 051001</p>
        <p><strong>Account Name:</strong> Paw Prints</p>
        <p><strong>Account Number:</strong> 123456789</p>
    </section>

    <!-- Donation Form -->
    <div class="donation-form-container" id="donation-form-container">
        <div class="donation-header">
            <h1>Support Our Animal Rescue</h1>
            <p>Your donation helps us provide care and shelter for animals in need</p>
        </div>

        <form action="donation.php" method="POST" id="donation-form">
            <!-- Donation Amount Section -->
            <div class="form-section">
                <div class="section-title">Donation Amount</div>
                
                <div class="form-group">
                    <label for="DonationDate">Donation Date:</label>
                    <input type="date" name="DonationDate" id="DonationDate" class="form-control" required>
                </div>
                
                <div class="amount-options">
                    <div class="amount-option" data-amount="50">R50</div>
                    <div class="amount-option" data-amount="100">R100</div>
                    <div class="amount-option" data-amount="250">R250</div>
                    <div class="amount-option" data-amount="500">R500</div>
                    <div class="amount-option selected" data-amount="1000">R1000</div>
                </div>
                
                <div class="form-group">
                    <label for="Amount">Or enter custom amount (R):</label>
                    <input type="number" name="Amount" id="Amount" class="form-control" 
                           required min="1" value="1000">
                </div>
            </div>

            <!-- Payment Method Section -->
            <div class="form-section">
                <div class="section-title">Payment Method</div>
                
                <div class="payment-method-cards">
                    <div class="payment-method selected" data-method="Card">
                        <i class="far fa-credit-card"></i>
                        <div>Credit/Debit Card</div>
                    </div>
                    <div class="payment-method" data-method="PayPal" id="paypal-method">
                        <i class="fab fa-paypal"></i>
                        <div>PayPal</div>
                    </div>
                    <div class="payment-method" data-method="EFT">
                        <i class="fas fa-university"></i>
                        <div>Bank Transfer</div>
                    </div>
                </div>
                
                <input type="hidden" name="PaymentMethod" id="PaymentMethod" value="Card">
                
                <!-- Card Payment Details -->
                <div class="payment-details active" id="card-details">
                    <div class="card-row">
                        <div class="form-group">
                            <label for="card_number">Card Number</label>
                            <input type="text" name="card_number" id="card_number" class="form-control" 
                                   placeholder="1234 5678 9012 3456" maxlength="19">
                        </div>
                    </div>
                    
                    <div class="card-row">
                        <div class="form-group">
                            <label for="expiry">Expiry Date</label>
                            <input type="month" name="expiry" id="expiry" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="cvv">CVV</label>
                            <input type="text" name="cvv" id="cvv" class="form-control" 
                                   placeholder="123" pattern="\d{3}" maxlength="3">
                        </div>
                    </div>
                </div>
                
                <!-- PayPal Payment Details -->
                <div class="payment-details" id="paypal-details">
                    <div class="paypal-message" id="paypal-message">
                        <p style="margin: 0; color: #2980b9; font-weight: 500;">
                            <i class="fas fa-info-circle"></i> 
                            Please log in to your PayPal account using the popup to proceed with the donation.
                        </p>
                    </div>
                </div>
                
                <!-- EFT Message -->
                <div class="payment-details" id="eft-details">
                    <div style="padding: 15px; background: #e8f4fd; border-radius: 8px;">
                        <p style="margin: 0; color: #2980b9; font-weight: 500;">
                            <i class="fas fa-info-circle"></i> 
                            Please use the bank details provided above to complete your transfer.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Personal Information Section -->
            <div class="form-section">
                <div class="section-title">Your Information</div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">First Name</label>
                        <input type="text" name="name" id="name" class="form-control" 
                               placeholder="John" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="surname">Last Name</label>
                        <input type="text" name="surname" id="surname" class="form-control" 
                               placeholder="Doe" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" 
                               placeholder="john.doe@example.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="CellNumber">Phone Number</label>
                        <input type="text" name="CellNumber" id="CellNumber" class="form-control" 
                               placeholder="(123) 456-7890" required>
                    </div>
                </div>
            </div>

            <!-- Optional Message -->
            <div class="form-section">
                <div class="section-title">Additional Information <span class="optional-note">(optional)</span></div>
                
                <div class="form-group">
                    <label for="message">Leave a message with your donation</label>
                    <textarea name="message" id="message" class="form-control" 
                              rows="3" placeholder="Let us know why you're supporting our cause..."></textarea>
                </div>
            </div>

            <!-- Hidden Input Fields -->
            <input type="hidden" name="donor_id" value="1">
            <input type="hidden" name="user_id" value="1">

            <!-- Submit Button -->
            <button type="submit" class="btn-donate">
                <i class="fas fa-heart"></i> Donate Now
            </button>
        </form>
    </div>

    <!-- Thank You Section -->
    <section class="thank-you">
        <div class="thank-you-content">
            <h2>Thank You for Your Support</h2>
            <p>Your contribution is more than an animal shelter donation. It provides shelter, treatment, rehabilitation and care for up to 200 companion animals at the Paw Prints Shelter. Your donation funds an outreach solution that sterilises and vaccinates companion animals in vulnerable communities, helps educate children and communities on how to look after their pets, and rescues animals in distress from all over the region giving them a second chance.</p>
            <p>Every cent counts. Every act of kindness makes a difference. Miracles happen because of people like you.</p>
        </div>
        <div class="thank-you-media">
            <img src="https://images.unsplash.com/photo-1552053831-71594a27632d?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=612&q=80" alt="Thank You">
            <iframe width="560" height="315" src="https://www.youtube.com/embed/xLd9-qHNq6c" title="Animal Rescue Video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>
    </section>
      <!-- Footer Section -->
    <footer>
        <h2 class="footerHeader">Paw Prints - Where every paw matters</h2>
        <hr>
        <a href="../frontPage.html">
            <img src="../images/Logo.png" alt="Makhanda SPCA Logo">
        </a>
        <ul class="footer-container">
            <ul>
                <li>Who Are We</li>
                <li><a href="../About us/aboutUs.html">About Us</a></li>
                <li><a href="">Services</a></li>
            </ul>
            <ul>
                <li>OFFICE HOURS</li>
                <li>Mon - Fri: 09:00am - 16:00pm</li>
                <li>Sat: 09:00am-12:00pm</li>
                <li>Sun: 09:00-11:00am</li>
                <li>Public Holidays - Closed</li>
            </ul>
            <ul>
                <li>POLICIES</li>
                <li><a href="../images/documents/Euthanasia.png">Euthanasia Policy</a></li>
                <li><a href="../images/documents/AnimalsProtectionAct.pdf">Animal Protection Act</a></li>
                <li><a href="#">SPCA Policy Statement</a></li>

            </ul>
            <ul>
                <li>GET INVOLVED</li>
                <li><a href="../Adopt and Volunteer/adoption.php">Adopt</a></li>
                <li><a href="../Adopt and Volunteer/volunteer.php">Volunteer</a></li>
                <li><a href="../DONATIONS/donationSite.php">Donate</a></li>
                <li><a href="../Cruelty Reports/Cruelty Management.php">Report</a></li>

            </ul>
            <ul>
                <li>CONTACT US</li>
                <li>Phone: 046 622 3233</li>
                <li>After Hours: 083 326 1604</li>
                <li>Email: chairperson@spcaght.co.za</li>
                <li>Old Bay Road, Makhanda, 6139</li>
                <li>Search for your nearest SPCA</li>
            </ul>
            <ul class="social-links">
                <li class="social-title">FOLLOW US</li>
                <li class="icons">
                    <a href="https://www.facebook.com/p/SPCA-Grahamstown-100064594333555/"><img
                            src="../images/footer icons/social.png" alt="Facebook"></a>
                    <a href="https://www.instagram.com/spca_grahamstown/?hl=en"><img
                            src="../images/footer icons/instagram.png" alt="Instagram"></a>
                    <a href="https://www.youtube.com/@rsanspca"><img src="../images/footer icons/youtube.png"
                            alt="YouTube"></a>
                </li>
            </ul>
        </ul>
        <hr>
        <p>&copy; 2025 SPCA Makhanda</p>
    </footer>

    <script>
        // Set minimum date to today for donation date
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const yyyy = today.getFullYear();
            let mm = today.getMonth() + 1;
            let dd = today.getDate();
            
            if (dd < 10) dd = '0' + dd;
            if (mm < 10) mm = '0' + mm;
            
            const formattedToday = `${yyyy}-${mm}-${dd}`;
            document.getElementById('DonationDate').setAttribute('min', formattedToday);
            document.getElementById('DonationDate').value = formattedToday; // Set default to today
            
            // Image Carousel Functionality
            const slide = document.querySelector('.carousel-slide');
            const images = document.querySelectorAll('.carousel-slide img');
            const navBtns = document.querySelectorAll('.carousel-nav-btn');
            let currentIndex = 0;
            const totalImages = images.length;
            
            // Set initial positions
            images.forEach((img, index) => {
                img.style.transform = `translateX(${index * 100}%)`;
            });
            
            // Auto-scroll every 3 seconds
            const interval = setInterval(nextSlide, 3000);
            
            function nextSlide() {
                currentIndex = (currentIndex + 1) % totalImages;
                updateCarousel();
            }
            
            function updateCarousel() {
                slide.style.transform = `translateX(-${currentIndex * 100}%)`;
                navBtns.forEach((btn, index) => {
                    btn.classList.toggle('active', index === currentIndex);
                });
            }
            
            navBtns.forEach((btn, index) => {
                btn.addEventListener('click', () => {
                    currentIndex = index;
                    updateCarousel();
                    clearInterval(interval);
                });
            });
            
            // Donation Form Functionality
            // Track PayPal login status
            let paypalLoggedIn = false;
            
            // Amount selection
            const amountOptions = document.querySelectorAll('.amount-option');
            const amountInput = document.getElementById('Amount');
            
            amountOptions.forEach(option => {
                option.addEventListener('click', function() {
                    amountOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    amountInput.value = this.getAttribute('data-amount');
                });
            });
            
            // Update selected amount when custom input changes
            amountInput.addEventListener('input', function() {
                amountOptions.forEach(opt => opt.classList.remove('selected'));
            });
            
            // Payment method selection
            const paymentMethods = document.querySelectorAll('.payment-method');
            const paymentMethodInput = document.getElementById('PaymentMethod');
            const cardDetails = document.getElementById('card-details');
            const paypalDetails = document.getElementById('paypal-details');
            const eftDetails = document.getElementById('eft-details');
            const paypalPopup = document.getElementById('paypal-popup');
            const closePopup = document.getElementById('close-popup');
            const paypalMethod = document.getElementById('paypal-method');
            
            paymentMethods.forEach(method => {
                method.addEventListener('click', function() {
                    paymentMethods.forEach(m => m.classList.remove('selected'));
                    this.classList.add('selected');
                    
                    const selectedMethod = this.getAttribute('data-method');
                    paymentMethodInput.value = selectedMethod;
                    
                    // Show/hide appropriate payment details
                    cardDetails.classList.remove('active');
                    paypalDetails.classList.remove('active');
                    eftDetails.classList.remove('active');
                    
                    if (selectedMethod === 'Card') {
                        cardDetails.classList.add('active');
                    } else if (selectedMethod === 'PayPal') {
                        paypalDetails.classList.add('active');
                        paypalPopup.classList.add('active');
                    } else if (selectedMethod === 'EFT') {
                        eftDetails.classList.add('active');
                    }
                });
            });
            
            // Close popup when close button is clicked
            closePopup.addEventListener('click', function() {
                paypalPopup.classList.remove('active');
                document.getElementById('paypal-success').classList.remove('active');
                document.getElementById('paypal-email').value = '';
                document.getElementById('paypal-password').value = '';
            });
            
            // Close popup when clicking outside the content
            paypalPopup.addEventListener('click', function(e) {
                if (e.target === paypalPopup) {
                    paypalPopup.classList.remove('active');
                    document.getElementById('paypal-success').classList.remove('active');
                    document.getElementById('paypal-email').value = '';
                    document.getElementById('paypal-password').value = '';
                }
            });
            
            // PayPal login button functionality
            const paypalLoginBtn = document.getElementById('paypal-login');
            paypalLoginBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const paypalEmail = document.getElementById('paypal-email').value;
                const paypalPassword = document.getElementById('paypal-password').value;
                
                if (!paypalEmail || !paypalPassword) {
                    alert('Please enter both email and password');
                    return;
                }
                
                // Show processing state
                this.textContent = 'Processing...';
                this.disabled = true;
                
                // Process for 2 seconds, then show success message, remove PayPal message, and close popup
                setTimeout(() => {
                    document.getElementById('paypal-success').classList.add('active');
                    this.textContent = 'Log In';
                    this.disabled = false;
                    paypalLoggedIn = true;
                    
                    // Remove PayPal message from form
                    const paypalMessage = document.getElementById('paypal-message');
                    if (paypalMessage) {
                        paypalMessage.remove();
                    }
                    
                    // Close popup after 1 second to allow success message to be seen
                    setTimeout(() => {
                        paypalPopup.classList.remove('active');
                        document.getElementById('paypal-success').classList.remove('active');
                        document.getElementById('paypal-email').value = '';
                        document.getElementById('paypal-password').value = '';
                    }, 1000);
                }, 2000);
            });
            
            // Format card number as user types
            const cardNumberInput = document.getElementById('card_number');
            if (cardNumberInput) {
                cardNumberInput.addEventListener('input', function() {
                    let value = this.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                    let formattedValue = '';
                    
                    for (let i = 0; i < value.length; i++) {
                        if (i > 0 && i % 4 === 0) {
                            formattedValue += ' ';
                        }
                        formattedValue += value[i];
                    }
                    
                    this.value = formattedValue;
                });
            }
            
            // Form submission
            const donationForm = document.getElementById('donation-form');
            donationForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Simple validation
                const donationDate = document.getElementById('DonationDate').value;
                const amount = document.getElementById('Amount').value;
                const name = document.getElementById('name').value;
                const email = document.getElementById('email').value;
                const selectedMethod = paymentMethodInput.value;
                
                if (!donationDate || !amount || !name || !email) {
                    alert('Please fill in all required fields');
                    return;
                }
                
                // Check if PayPal is selected and login has been completed
                if (selectedMethod === 'PayPal' && !paypalLoggedIn) {
                    alert('Please log in to PayPal first');
                    paypalPopup.classList.add('active');
                    return;
                }
                
                // If validation passes, submit the form
                this.submit();
            });
        });
    </script>
</body>
</html>