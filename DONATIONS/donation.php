<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation Receipt - Paw Prints Animal Rescue</title>
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
    font-weight: 600;
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
/* Services dropdown menu */
.Services {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap; /* Allow items to wrap to the next line */
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
    max-height: 260px; /* Adjustable height for wrapped items */
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

/* Hover effect for items */
.Services li a:hover {
    background: rgba(255, 255, 255, 0.1);
}

        
        /* Processing Container */
        .processing-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .success-message {
            color: #056f65;
            font-size: 1.5rem;
            margin: 20px 0;
            padding: 15px;
            background: #d1ecf1;
            border-radius: 5px;
            border: 1px solid #bee5eb;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 1.5rem;
            margin: 20px 0;
            padding: 15px;
            background: #f8d7da;
            border-radius: 5px;
            border: 1px solid #f5c6cb;
        }
        
        .warning-message {
            color: #856404;
            font-size: 1.1rem;
            margin: 15px 0;
            padding: 10px;
            background: #fff3cd;
            border-radius: 5px;
            border: 1px solid #ffeaa7;
        }
        
        .info-message {
            color: #0c5460;
            font-size: 1.1rem;
            margin: 15px 0;
            padding: 10px;
            background: #d1ecf1;
            border-radius: 5px;
            border: 1px solid #bee5eb;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 30px 0;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: #056f65b8;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        /* Receipt Styles */
        .receipt {
            margin: 30px 0;
            padding: 25px;
            border: 2px solid #056f65b8;
            border-radius: 10px;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }
        
        .receipt-header h2 {
            color: #056f65b8;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        
        .receipt-header p {
            color: #666;
        }
        
        .receipt-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .receipt-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed #eee;
        }
        
        .receipt-label {
            font-weight: 600;
            color: #555;
        }
        
        .receipt-value {
            color: #333;
        }
        
        .receipt-amount {
            font-size: 1.8rem;
            font-weight: 700;
            color: #056f65b8;
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .receipt-message {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            font-style: italic;
        }
        
        .receipt-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #eee;
            color: #666;
            font-size: 0.9rem;
        }
        
        .receipt-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
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
        
        /* Print Styles */
        @media print {
            body * {
                visibility: hidden;
            }
            .receipt, .receipt * {
                visibility: visible;
            }
            .receipt {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                box-shadow: none;
                border: none;
            }
            .receipt-actions, .action-buttons, .navigation, .login-section, footer, .thank-you {
                display: none;
            }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
            }
            
            .action-buttons, .receipt-actions {
                flex-direction: column;
            }
            
            .thank-you {
                flex-direction: column;
            }
            
            .receipt-details {
                grid-template-columns: 1fr;
            }
            
            
        }
    </style>
</head>
<body>
  <!-- SECTION 1: Main Navigation Bar at Very Top -->
    <nav>
        <div class="nav-logo">
            <a href="../frontPage.html">
                <img src="../images/Logo.png" alt="Makhanda SPCA Logo">
            </a>
            <h2>Paw Prints</h2>
        </div>
        <div class="nav-links" id="navLinks">
            <ul>
                <li><a href="../frontPage.html">Home</a></li>
                <li><a href="../About us/aboutUs.html">About</a></li>
                <li>
                    <a href="#">Services</a>
                    <ul class="Services">
                        <li><a href="../Adopt and Volunteer/adoption.php">Adopt</a></li>
                        <li><a href="../Adopt and Volunteer/volunteer.php">Volunteer</a></li>
                        <li><a href="../Cruelty Reports/Cruelty Management.php">Report</a></li>
                        <li><a href="../DONATIONS/donationSite.php">Donate</a></li>
                    </ul>
                </li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="../registerUser/registerUser.php">Login</a></li>
            </ul>
        </div>
    </nav>


<div class="processing-container">
    <?php
    // Include connection
    include "DatabaseConnection.php";

    mysqli_report(MYSQLI_REPORT_OFF); // We'll handle errors ourselves

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        // Read POST exactly as your form sends them
        $DonationDate     = $_POST['DonationDate'] ?? null;
        $Amount           = isset($_POST['Amount']) ? (float)$_POST['Amount'] : null;
        $PaymentMethod    = $_POST['PaymentMethod'] ?? '';
        $name             = $_POST['name'] ?? null;
        $surname          = $_POST['surname'] ?? null;
        $email            = $_POST['email'] ?? null;
        $CellNumber       = $_POST['CellNumber'] ?? null;
        $message          = $_POST['message'] ?? '';

        // Basic validation
        if (!$DonationDate || $Amount === null || !$PaymentMethod || !$name || !$surname) {
            echo "<div class='error-message'>Missing required fields. Please go back and fill all required information.</div>";
        } else {
            // Validate PaymentMethod against expected values
            $allowedMethods = ['Card', 'EFT', 'PayPal'];
            
            if (!in_array($PaymentMethod, $allowedMethods)) {
                echo "<div class='error-message'>Invalid payment method '$PaymentMethod'. Please use one of: " . implode(', ', $allowedMethods) . ".</div>";
            } else {
                // Auto-generate donor_id and user_id by incrementing the last seen values
                $maxRes = $conn->query("SELECT COALESCE(MAX(donor_id), 0) AS max_donor, COALESCE(MAX(user_id), 0) AS max_user FROM `alldonations`");
                if (!$maxRes) {
                    echo "<div class='error-message'>Failed to fetch next IDs: " . $conn->error . "</div>";
                } else {
                    $row = $maxRes->fetch_assoc();
                    $donor_id = (int)$row['max_donor'] + 1;
                    $user_id  = (int)$row['max_user']  + 1;

                    // Use prepared statement with placeholders
                    $sql = "INSERT INTO alldonations (donor_id, user_id, DonationDate, CellNumber, Amount, PaymentMethod, name, surname, email) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

                    $stmt = $conn->prepare($sql);
                    if (!$stmt) {
                        echo "<div class='error-message'>Prepare failed: " . $conn->error . "</div>";
                    } else {
                        // Bind parameters with correct data types: i=integer, d=double, s=string
                        $ok = $stmt->bind_param(
                            "iissdssss",
                            $donor_id,
                            $user_id,
                            $DonationDate,
                            $CellNumber,
                            $Amount,
                            $PaymentMethod,
                            $name,
                            $surname,
                            $email
                        );
                        
                        if (!$ok) {
                            echo "<div class='error-message'>bind_param failed: " . $stmt->error . "</div>";
                        } else {
                            if ($stmt->execute()) {
                                // Generate a transaction ID
                                $transaction_id = 'PP' . date('Ymd') . str_pad($donor_id, 5, '0', STR_PAD_LEFT);
                                
                                echo "<div class='success-message'><i class='fas fa-check-circle'></i> Donation Processed Successfully!</div>";
                                echo "<p>Thank you for your generous support of Paw Prints Animal Rescue.</p>";
                                
                                // Display receipt
                                echo "<div class='receipt'>";
                                echo "<div class='receipt-header'>";
                                echo "<h2>DONATION RECEIPT</h2>";
                                echo "<p>Paw Prints Animal Rescue - Where every paw matters</p>";
                                echo "</div>";
                                
                                echo "<div class='receipt-details'>";
                                echo "<div class='receipt-row'><span class='receipt-label'>Transaction ID:</span><span class='receipt-value'>$transaction_id</span></div>";
                                echo "<div class='receipt-row'><span class='receipt-label'>Date:</span><span class='receipt-value'>$DonationDate</span></div>";
                                echo "<div class='receipt-row'><span class='receipt-label'>Payment Method:</span><span class='receipt-value'>$PaymentMethod</span></div>";
                                echo "<div class='receipt-row'><span class='receipt-label'>Donor Name:</span><span class='receipt-value'>$name $surname</span></div>";
                                echo "<div class='receipt-row'><span class='receipt-label'>Email:</span><span class='receipt-value'>$email</span></div>";
                                echo "<div class='receipt-row'><span class='receipt-label'>Phone:</span><span class='receipt-value'>$CellNumber</span></div>";
                                echo "</div>";
                                
                                echo "<div class='receipt-amount'>Donation Amount: $$Amount</div>";
                                
                                if (!empty($message)) {
                                    echo "<div class='receipt-message'><strong>Donor Message:</strong> $message</div>";
                                }
                                
                                echo "<div class='receipt-footer'>";
                                echo "<p>Thank you for your support. Your donation is tax-deductible to the extent allowed by law.</p>";
                                echo "<p>Paw Prints Animal Rescue is a 501(c)(3) nonprofit organization. Tax ID: 12-3456789</p>";
                                echo "</div>";
                                
                                echo "<div class='receipt-actions'>";
                                echo "<button class='btn btn-primary' onclick='window.print()'><i class='fas fa-print'></i> Print Receipt</button>";
                                echo "</div>";
                                
                                echo "</div>"; // End receipt
                            } else {
                                echo "<div class='error-message'>Error processing donation: " . $stmt->error . "</div>";
                            }
                        }

                        $stmt->close();
                    }
                }
            }
        }
    } else {
        echo "<div class='error-message'>Invalid request method. Please use the donation form.</div>";
    }

    $conn->close();
    ?>
    
    <div class="action-buttons">
        <a href="donationSite.php" class="btn btn-primary">Make Another Donation</a>
        <a href="../frontPage.html" class="btn btn-success">Return to Homepage</a>
    </div>
    
    <div style="text-align: center; margin-top: 30px;">
        <img src="https://images.unsplash.com/photo-1552053831-71594a27632d?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=612&q=80" alt="Thank You" style="max-width: 100%; border-radius: 10px;">
    </div>
</div>

<!-- Thank You Section -->
<section class="thank-you">
    <div class="thank-you-content">
        <h2>Thank You for Your Support</h2>
        <p>Be a Lifeline for Animals in need. Your contribution is more than an animal shelter donation, it:
            provides shelter, treatment, rehabilitation and care for up to 200 companion animals at the TEARS Shelter,
            funds an outreach solution that sterilises and vaccinates companion animals in vulnerable communities,
            helps educate children and communities on how to look after their pets,
            and rescues animals in distress from all over Makhanda and gives them a second chance.
            Every cent counts.
            Every act of kindness makes a difference. Miracles happen because of people like you.</p>
    </div>
    <div class="thank-you-media">
        <img src="https://images.unsplash.com/photo-1543852786-1cf6624b9987?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1170&q=80" alt="Happy Animals">
    </div>
</section>

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
                <li><a href="#">Services</a></li>
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

</body>
</html>