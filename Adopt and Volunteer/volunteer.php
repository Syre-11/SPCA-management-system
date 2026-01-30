<?php
// volunteer.php - Handle volunteer application and lookup

include 'DatabaseConnection.php';

// Handle volunteer application form submission
$error = null;
$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $experience = $_POST['experience'];
    $interests = implode(', ', $_POST['interests'] ?? []);
    $availability = implode(', ', $_POST['availability'] ?? []);

    $stmt = $conn->prepare("INSERT INTO VolunteerApplication (FirstName, LastName, Email, Phone, Address, Experience, Interests, Availability, Status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
    if ($stmt === false) {
        $error = "Failed to prepare the application insertion statement: " . $conn->error;
    } else {
        $stmt->bind_param("ssssssss", $firstName, $lastName, $email, $phone, $address, $experience, $interests, $availability);
        $result = $stmt->execute();
        if ($result === false) {
            $error = "Failed to execute the application insertion: " . $conn->error;
        } else {
            $success = "Thank you for your application! We'll be in touch soon.";
        }
    }
}

// Handle volunteer lookup
$application = null;
$assignments = [];
$hours = [];
$lookupMessage = null;
$lookupError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lookup'])) {
    $email = $_POST['lookupEmail'] ?? '';
    $applicationId = $_POST['lookupId'] ?? '';

    $stmt = null;
    if (!empty($email)) {
        $stmt = $conn->prepare("SELECT * FROM VolunteerApplication WHERE Email = ? AND IsHidden = 0");
    } elseif (!empty($applicationId)) {
        $stmt = $conn->prepare("SELECT * FROM VolunteerApplication WHERE Application_ID = ? AND IsHidden = 0");
    }

    if ($stmt === false) {
        $lookupError = "Failed to prepare the lookup query: " . $conn->error;
    } elseif ($stmt !== null) {
        if (!empty($email)) {
            $stmt->bind_param("s", $email);
        } else {
            $stmt->bind_param("i", $applicationId);
        }
        $result = $stmt->execute();
        if ($result === false) {
            $lookupError = "Failed to execute the lookup query: " . $conn->error;
        } else {
            $result = $stmt->get_result();
            $application = $result->fetch_assoc();

            if ($application) {
                if ($application['Status'] === 'Pending') {
                    $lookupMessage = "Your application is pending review.";
                } elseif ($application['Status'] === 'Rejected') {
                    $lookupMessage = "Your application was rejected. Please contact us for more information.";
                } elseif ($application['Status'] === 'Approved') {
                    // Get assignments
                    $stmt = $conn->prepare("SELECT AssignmentDate, StartTime, EndTime, ActivityType, Description FROM VolunteerAssignment WHERE FK_Application_ID = ? ORDER BY AssignmentDate, StartTime");
                    if ($stmt === false) {
                        $lookupError = "Failed to prepare the assignments query: " . $conn->error;
                    } else {
                        $stmt->bind_param("i", $application['Application_ID']);
                        $result = $stmt->execute();
                        if ($result === false) {
                            $lookupError = "Failed to execute the assignments query: " . $conn->error;
                        } else {
                            $result = $stmt->get_result();
                            $assignments = $result->fetch_all(MYSQLI_ASSOC);
                        }
                    }

                    // Get hours
                    if (!isset($lookupError)) {
                        $stmt = $conn->prepare("SELECT DatePerformed, Hours, ActivityType, Description, Verified FROM VolunteerHours WHERE FK_Application_ID = ? ORDER BY DatePerformed DESC");
                        if ($stmt === false) {
                            $lookupError = "Failed to prepare the hours query: " . $conn->error;
                        } else {
                            $stmt->bind_param("i", $application['Application_ID']);
                            $result = $stmt->execute();
                            if ($result === false) {
                                $lookupError = "Failed to execute the hours query: " . $conn->error;
                            } else {
                                $result = $stmt->get_result();
                                $hours = $result->fetch_all(MYSQLI_ASSOC);
                            }
                        }
                    }
                }
            } else {
                $lookupError = "No volunteer record found.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer at Makhanda SPCA</title>
    <link rel="stylesheet" href="volunteer.css">
</head>

<body>
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
                <li><a href="About us/aboutUs.html">About</a></li>
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

    <!-- Why Volunteer Section -->
    <section class="MSPCA">
        <h3>Why Volunteer at Makhanda SPCA?</h3>
        <p>Our volunteers are the heart of our organization. Your time and compassion help us provide care, love, and
            second chances to animals in need.</p>
        <div class="row">
            <div class="row1">
                <h3>Animal Care</h3>
                <p>Help with feeding, grooming, and socializing our animals. Your interaction helps prepare them for
                    their forever homes.</p>
            </div>
            <div class="row1">
                <h3>Shelter Support</h3>
                <p>Assist with cleaning kennels, organizing supplies, and maintaining our facilities to ensure a safe
                    environment.</p>
            </div>
            <div class="row1">
                <h3>Events & Outreach</h3>
                <p>Represent SPCA at community events, help with fundraising, and spread awareness about animal welfare.
                </p>
            </div>
        </div>
    </section>

    <!-- Volunteer Opportunities Section -->
    <section class="MSPCA" style="background-color: #f0f8ff;">
        <h3>Volunteer Opportunities</h3>
        <div class="row">
            <div class="row1">
                <img src="dog_walking.jpg" alt="Dog Walking"
                    style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px; margin-bottom: 15px;">
                <h3>Dog Walking</h3>
                <p>Take our dogs for walks to provide exercise and socialization. Training provided for all volunteers.
                </p>
            </div>
            <div class="row1">
                <img src="cat_socializing.jpg" alt="Cat Socializing"
                    style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px; margin-bottom: 15px;">
                <h3>Cat Socializing</h3>
                <p>Spend time with our cats, helping them become comfortable with human interaction.</p>
            </div>
            <div class="row1">
                <img src="shelter cleaning.jpg" alt="Shelter Cleaning"
                    style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px; margin-bottom: 15px;">
                <h3>Facility Maintenance</h3>
                <p>Help keep our facilities clean and organized to ensure a healthy environment for our animals.</p>
            </div>
        </div>
        <div class="row">
            <div class="row1">
                <img src="fostering.jpg" alt="Fostering"
                    style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px; margin-bottom: 15px;">
                <h3>Fostering</h3>
                <p>Provide temporary homes for animals needing special care or socialization before adoption.</p>
            </div>
            <div class="row1">
                <img src="fundraising.jpg" alt="Events"
                    style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px; margin-bottom: 15px;">
                <h3>Events & Fundraising</h3>
                <p>Help organize and run adoption events, fundraising activities, and community outreach programs.</p>
            </div>
            <div class="row1">
                <img src="administration.jpg" alt="Administration"
                    style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px; margin-bottom: 15px;">
                <h3>Administrative Support</h3>
                <p>Assist with paperwork, phone calls, data entry, and other office tasks that keep our organization
                    running.</p>
            </div>
        </div>
    </section>

    <!-- Volunteer Lookup Section -->
    <section class="MSPCA">
        <h3>Current Volunteer Lookup</h3>
        <p>Already a volunteer? Enter your email to view your schedule and hours.</p>
        <form class="status-check-form" method="POST">
            <label for="lookupEmail">Email Address</label>
            <input type="email" id="lookupEmail" name="lookupEmail" placeholder="Your Email" required>
            <button type="submit" name="lookup">View My Schedule</button>
        </form>

        <?php if (isset($lookupError)): ?>
            <div class="notification error"><?php echo $lookupError; ?></div>
        <?php endif; ?>

        <?php if ($lookupMessage): ?>
            <div class="notification success"><?php echo $lookupMessage; ?></div>
        <?php endif; ?>

        <?php if ($application && $application['Status'] === 'Approved'): ?>
            <div class="status-result">
                <h4>Your Profile</h4>
                <p><strong>Name:</strong>
                    <?php echo htmlspecialchars($application['FirstName'] . ' ' . $application['LastName']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($application['Email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($application['Phone']); ?></p>
                <p><strong>Interests:</strong> <?php echo htmlspecialchars($application['Interests']); ?></p>
                <p><strong>Availability:</strong> <?php echo htmlspecialchars($application['Availability']); ?></p>

                <?php if (!empty($assignments)): ?>
                    <h4>Your Upcoming Assignments</h4>
                    <table class="applications-table" text-align="center">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Activity</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($assignment['AssignmentDate'])); ?></td>
                                    <td>
                                        <?php if ($assignment['StartTime']): ?>
                                            <?php echo date('g:i A', strtotime($assignment['StartTime'])) . ' - ' . date('g:i A', strtotime($assignment['EndTime'])); ?>
                                        <?php else: ?>
                                            All Day
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($assignment['ActivityType']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['Description'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty-state">No upcoming assignments scheduled.</p>
                <?php endif; ?>

                <?php if (!empty($hours)): ?>
                    <h4>Your Volunteer Hours</h4>
                    <table class="applications-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Hours</th>
                                <th>Activity</th>
                                <th>Description</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hours as $hour): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($hour['DatePerformed'])); ?></td>
                                    <td><?php echo htmlspecialchars($hour['Hours']); ?> hours</td>
                                    <td><?php echo htmlspecialchars($hour['ActivityType']); ?></td>
                                    <td><?php echo htmlspecialchars($hour['Description'] ?? 'N/A'); ?></td>
                                    <td><span class="status-badge status-<?php echo $hour['Verified'] ? 'verified' : 'pending'; ?>">
                                            <?php echo $hour['Verified'] ? 'Verified' : 'Pending Verification'; ?>
                                        </span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty-state">No hours recorded yet.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Application Form Section -->
    <section id="apply" class="MSPCA">
        <h3>Apply to Volunteer</h3>
        <p>Fill out the form below to apply as a volunteer. We'll contact you within 3-5 business days.</p>

        <?php if (isset($success)): ?>
            <div class="notification success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="notification error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form class="adoption-form" method="POST">
            <h4>Personal Information</h4>
            <label for="firstName">First Name *</label>
            <input type="text" id="firstName" name="firstName" required>
            <label for="lastName">Last Name *</label>
            <input type="text" id="lastName" name="lastName" required>
            <label for="email">Email Address *</label>
            <input type="email" id="email" name="email" required>
            <label for="phone">Phone Number *</label>
            <input type="tel" id="phone" name="phone" required>
            <label for="address">Address *</label>
            <textarea id="address" name="address" rows="3" required></textarea>

            <h4>Volunteer Interests</h4>
            <tr>
                <label>What activities interest you? *</label>
                <td>
                    <div
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; padding: 10px;">
                        <label for="dog_walking"><input type="checkbox" name="interests[]" value="Dog Walking"> Dog
                            Walking</label>
                        <label for="cat_socializing"><input type="checkbox" name="interests[]" value="Cat Socializing">
                            Cat Socializing</label>
                        <label for="cleaning"><input type="checkbox" name="interests[]" value="Cleaning"> Facility
                            Cleaning</label>
                        <label for="fostering"><input type="checkbox" name="interests[]" value="Fostering">
                            Fostering</label>
                        <label for="events"><input type="checkbox" name="interests[]" value="Events"> Events &
                            Fundraising</label>
                        <label for="admin"><input type="checkbox" name="interests[]" value="Administration">
                            Administrative Support</label>
                    </div>
                </td>
            </tr>

            <h4>Availability</h4>
            <label>When are you available? *</label>
            <div
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; padding: 10px;">
                <label><input type="checkbox" name="availability[]" value="Weekday Mornings"> Weekday Mornings</label>
                <label><input type="checkbox" name="availability[]" value="Weekday Afternoons"> Weekday
                    Afternoons</label>
                <label><input type="checkbox" name="availability[]" value="Weekday Evenings"> Weekday Evenings</label>
                <label><input type="checkbox" name="availability[]" value="Weekends"> Weekends</label>
            </div>

            <label for="experience">Previous Experience with Animals (if any)</label>
            <textarea id="experience" name="experience" rows="4"></textarea>

            <button type="submit" name="apply">Submit Application</button>
        </form>
    </section>

    <!-- Footer Section -->
    <footer>
        <h2 class="footerHeader">Paw Prints - Where every paw matters</h2>
        <hr>
        <a href="frontPage.html"><img src="Paw prints logo.png" alt="Makhanda SPCA Logo"></a>
        <div class="footer-container">
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
                    <a href="https://www.facebook.com/p/SPCA-Grahamstown-100064594333555/"><img src="social.png"
                            alt="Facebook"></a>
                    <a href="https://www.instagram.com/spca_grahamstown/?hl=en"><img src="instagram.png"
                            alt="Instagram"></a>
                    <a href="https://www.youtube.com/@rsanspca"><img src="youtube.png" alt="YouTube"></a>
                </li>
            </ul>
        </div>
        <hr>
        <p>&copy; 2025 SPCA Makhanda</p>
    </footer>

    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        document.querySelector('.adoption-form').addEventListener('submit', function (e) {
            const interests = document.querySelectorAll('input[name="interests[]"]:checked');
            const availability = document.querySelectorAll('input[name="availability[]"]:checked');
            if (interests.length === 0) {
                e.preventDefault();
                alert('Please select at least one volunteer interest.');
            }
            if (availability.length === 0) {
                e.preventDefault();
                alert('Please select at least one availability option.');
            }
        });
    </script>
</body>

</html>