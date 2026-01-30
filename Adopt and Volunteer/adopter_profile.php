<?php
require_once("DatabaseConnection.php");


// Verify connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get adopter email from URL
$adopter_email = isset($_GET['email']) ? $_GET['email'] : '';
if (empty($adopter_email)) {
    die("No adopter specified.");
}

// Handle follow-up date updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_followup'])) {
    $adoption_id = $_POST['adoption_id'];
    $new_followup_date = $_POST['followup_date'];

    $update_stmt = $conn->prepare("UPDATE Adoptions SET FollowUpDate = ? WHERE Adoption_ID = ?");
    $update_stmt->bind_param("si", $new_followup_date, $adoption_id);

    if ($update_stmt->execute()) {
        $success_message = "Follow-up date updated successfully!";
    } else {
        $error_message = "Error updating follow-up date: " . $conn->error;
    }
    $update_stmt->close();
}

// Fetch adopter details from adoptionapplication table
$adopter_stmt = $conn->prepare("
    SELECT 
        Application_ID,
        First_Name, 
        Last_Name, 
        Email, 
        Phone, 
        Address, 
        Experience,
        Animal_Name,
        Application_Status,
        Application_Date
    FROM adoptionapplication 
    WHERE Email = ?
    ORDER BY Application_Date DESC
    LIMIT 1
");
$adopter_stmt->bind_param("s", $adopter_email);
$adopter_stmt->execute();
$adopter_result = $adopter_stmt->get_result();
$adopter = $adopter_result->fetch_assoc();
$adopter_stmt->close();

// Check if adopter was found
if (!$adopter) {
    die("Adopter not found with email: " . htmlspecialchars($adopter_email));
}

// Fetch all applications by this email 
$applications_stmt = $conn->prepare("
    SELECT 
        aa.*,
        a.Animal_Breed,
        a.Animal_Gender,
        a.Animal_Health
    FROM adoptionapplication aa
    LEFT JOIN Animal a ON aa.Animal_Name = a.Animal_Name
    WHERE aa.Email = ?
    ORDER BY aa.Application_Date DESC
");
$applications_stmt->bind_param("s", $adopter_email);
$applications_stmt->execute();
$applications_result = $applications_stmt->get_result();
$all_applications = $applications_result->fetch_all(MYSQLI_ASSOC);
$applications_stmt->close();

// Fetch adoption history - improved query to better connect applications to adoptions
$adoptions_stmt = $conn->prepare("
    SELECT 
        ad.*, 
        an.Animal_Name,
        an.Animal_Breed, 
        an.Animal_Gender,
        ap.Email
    FROM Adoptions ad
    JOIN Animal an ON ad.FK_Animal_ID = an.Animal_ID
    JOIN Adopter ap ON ad.FK_Adopter_ID = ap.Adopter_ID
    WHERE ap.Email = ?
");
$adoptions_stmt->bind_param("s", $adopter_email);
$adoptions_stmt->execute();
$adoptions_result = $adoptions_stmt->get_result();
$adoptions = $adoptions_result->fetch_all(MYSQLI_ASSOC);
$adoptions_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adopter Profile</title>
    <style>
        /* Your existing styles remain unchanged */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            padding-top: 80px;
            background-color: #f4f4f9;
            min-height: 100vh;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 5%;
            background: rgba(5, 111, 101, 0.7);
            backdrop-filter: blur(10px);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        nav img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }

        .nav-logo h2 {
            color: white;
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }

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
            background-color: rgba(132, 224, 175, 0.809);
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

        h2 {
            text-align: center;
            margin: 20px 0;
            color: #444;
            font-size: 1.6rem;
        }

        .profile-section {
            width: 95%;
            max-width: 1400px;
            margin: 20px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .profile-section h3 {
            color: #444;
            margin-bottom: 15px;
        }

        .applications-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            border-radius: 12px;
            overflow: hidden;
        }

        .applications-table th {
            background: rgba(5, 111, 101, 0.7);
            color: white;
            font-size: 15px;
            font-weight: 600;
            padding: 15px;
            text-align: left;
        }

        .applications-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
            vertical-align: middle;
        }

        .applications-table tr:hover {
            background-color: #f3f4f6;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #d97706;
        }

        .status-approved {
            background-color: #d1fae5;
            color: #059669;
        }

        .status-rejected {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .back-btn {
            display: inline-block;
            margin: 20px;
            padding: 10px 15px;
            background: rgba(5, 111, 101, 0.7);
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: white;
            color: #10b981;
            border: 2px solid #10b981;
        }

        footer {
            background-color: rgba(5, 111, 101, 0.7);
            color: white;
            padding: 40px 20px 20px;
            text-align: center;
            margin-top: 60px;
        }

        .footerHeader {
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8rem;
        }

        footer hr {
            border: 0;
            height: 1px;
            background: rgba(255, 255, 255, 0.3);
            margin: 20px 0;
        }

        footer p {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        /* New styles for notifications and edit forms */
        .notification {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            text-align: center;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .edit-form {
            display: inline-block;
            margin-left: 10px;
        }

        .date-input {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .update-btn {
            padding: 5px 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .update-btn:hover {
            background-color: #45a049;
        }

        @media (max-width: 768px) {
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

            .profile-section {
                width: 90%;
                padding: 15px;
            }

            .applications-table th,
            .applications-table td {
                padding: 10px;
                font-size: 13px;
            }

            .edit-form {
                display: block;
                margin-top: 5px;
                margin-left: 0;
            }
        }

        @media (max-width: 480px) {
            h2 {
                font-size: 1.5rem;
            }

            .profile-section h3 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>

<body>
    <section class="header">
        <nav>
            <div class="nav-logo">
                <a href="../frontPage.html"><img src="Paw prints logo.png" alt="Makhanda SPCA Logo"></a>
                <h2>Paw Prints</h2>
            </div>
            <div class="nav-links">
                <ul>
                    <li><a href="../Cruelty Reports/admin_dashboard.php">Home</a></li>
                    <li><a href="adoption_records.php">Display All Records</a></li>
                    <li><a href="../registerUser/logout.php">Logout</a></li>
                </ul>
            </div>
        </nav>
    </section>

    <a href="adoption.management.php" class="back-btn">← Back to Applications</a>

    <h2>Adopter Profile</h2>

    <?php if (isset($success_message)): ?>
        <div class="notification success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="notification error"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="profile-section">
        <h3>Personal Information</h3>
        <table class="applications-table">
            <tr>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
            </tr>
            <tr>
                <td><?php echo htmlspecialchars($adopter['First_Name'] . ' ' . $adopter['Last_Name']); ?></td>
                <td><?php echo htmlspecialchars($adopter['Email'] ?? 'Not provided'); ?></td>
                <td><?php echo htmlspecialchars($adopter['Phone'] ?? 'Not provided'); ?></td>
                <td><?php echo htmlspecialchars($adopter['Address'] ?? 'Not provided'); ?></td>
            </tr>
        </table>
    </div>

    <div class="profile-section">
        <h3>Adoption Experience</h3>
        <table class="applications-table">
            <tr>
                <th>Experience with Pets</th>
            </tr>
            <tr>
                <td><?php echo htmlspecialchars($adopter['Experience'] ?? 'Not provided'); ?></td>
            </tr>
        </table>
    </div>

    <div class="profile-section">
        <h3>Application History</h3>
        <?php if (!empty($all_applications)): ?>
            <table class="applications-table">
                <tr>
                    <th>Application ID</th>
                    <th>Animal</th>
                    <th>Breed</th>
                    <th>Status</th>
                    <th>Application Date</th>
                </tr>
                <?php foreach ($all_applications as $application): ?>
                    <?php
                    $status_class = 'status-' . strtolower($application['Application_Status'] ?? 'pending');
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($application['Application_ID']); ?></td>
                        <td><?php echo htmlspecialchars($application['Animal_Name']); ?></td>
                        <td><?php echo htmlspecialchars($application['Animal_Breed'] ?? 'Unknown'); ?></td>
                        <td><span
                                class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($application['Application_Status']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($application['Application_Date']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <div class="empty-state">No application history found.</div>
        <?php endif; ?>
    </div>

    <div class="profile-section">
        <h3>Approved Adoptions</h3>
        <?php if (!empty($adoptions)): ?>
            <table class="applications-table">
                <tr>
                    <th>Animal</th>
                    <th>Breed</th>
                    <th>Gender</th>
                    <th>Adoption Date</th>
                    <th>Follow-up Date</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($adoptions as $adoption): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($adoption['Animal_Name']); ?></td>
                        <td><?php echo htmlspecialchars($adoption['Animal_Breed'] ?? 'Unknown'); ?></td>
                        <td><?php echo htmlspecialchars($adoption['Animal_Gender'] ?? 'Unknown'); ?></td>
                        <td><?php echo htmlspecialchars($adoption['AdoptionDate']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($adoption['FollowUpDate']); ?>
                            <form method="POST" class="edit-form">
                                <input type="hidden" name="adoption_id" value="<?php echo $adoption['Adoption_ID']; ?>">
                                <input type="date" name="followup_date" class="date-input"
                                    value="<?php echo $adoption['FollowUpDate']; ?>">
                                <button type="submit" name="update_followup" class="update-btn">Update</button>
                            </form>
                        </td>
                        <td>
                            <!-- Add any additional actions here -->
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <div class="empty-state">No approved adoptions found.</div>
        <?php endif; ?>
    </div>

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
        // Auto-refresh the page if coming from an approval action
        if (window.location.search.indexOf('approved=true') !== -1) {
            // Remove the parameter to prevent infinite refresh
            window.history.replaceState({}, document.title, window.location.pathname + window.location.search.replace('approved=true', ''));

            // Show a success message
            alert('Application approved successfully!');
        }
    </script>
</body>

</html>