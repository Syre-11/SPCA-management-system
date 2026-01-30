<?php
include 'DatabaseConnection.php';

// Verify connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch adoptable animals (removed Animal_Species)
$sql = "SELECT Animal_ID, Animal_Name, Animal_Breed FROM Animal WHERE Animal_AdoptionStatus = 'Available' 
        AND Animal_Health IN ('Excellent', 'Good')";
$result = $conn->query($sql);

$animals = [];
$error_message = '';

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $animals[] = $row;
        }
    }
} else {
    $error_message = "Error fetching animals: " . $conn->error;
    error_log("Query failed at " . date('h:i A T, F d, Y') . ": " . $conn->error);
}

// Handle forms (don't close $conn yet)
$success = '';
$form_error = ''; // Renamed for clarity
$status_result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_application'])) {
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $animal_name = $_POST['animal'] ?? '';
        $address = $_POST['address'] ?? '';
        $experience = $_POST['experience'] ?? '';

        error_log("POST data: " . print_r($_POST, true));

        $errors = [];
        if (empty(trim($first_name)))
            $errors[] = "First Name";
        if (empty(trim($last_name)))
            $errors[] = "Last Name";
        if (empty(trim($email)))
            $errors[] = "Email";
        if (empty(trim($phone)))
            $errors[] = "Phone";
        if (empty(trim($animal_name)))
            $errors[] = "Animal";
        if (empty(trim($address)))
            $errors[] = "Address";
        if (empty(trim($experience)))
            $errors[] = "Experience";

        if (!empty($errors)) {
            $form_error = "The following fields are required: " . implode(", ", $errors);
        } else {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $form_error = "Invalid email address.";
            } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
                $form_error = "Phone must be a 10-digit number.";
            } elseif (empty($animal_name) || !in_array($animal_name, array_column($animals, 'Animal_Name'))) {
                $form_error = "Invalid animal selection.";
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO adoptionapplication 
                    (First_Name, Last_Name, Email, Phone, Animal_Name, Address, Experience, Application_Status, Application_Date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
                ");
                if ($stmt === false) {
                    error_log("Prepare failed: " . $conn->error);
                    $form_error = "Database error: Could not prepare statement.";
                } else {
                    $stmt->bind_param("sssssss", $first_name, $last_name, $email, $phone, $animal_name, $address, $experience);
                    if ($stmt->execute()) {
                        $app_id = $stmt->insert_id;
                        $success = "Application submitted successfully! Your Application ID is $app_id. We will contact you soon.";
                    } else {
                        $form_error = "Could not submit application: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
    } elseif (isset($_POST['check_status'])) {
        $input = $_POST['input'] ?? '';
        if (!empty($input)) {
            if (is_numeric($input)) {
                $query = "
                    SELECT aa.Application_ID, aa.Application_Status, aa.Application_Date, aa.Animal_Name, a.Animal_Breed
                    FROM adoptionapplication aa
                    LEFT JOIN Animal a ON aa.Animal_Name = a.Animal_Name
                    WHERE aa.Application_ID = ?
                ";
                $stmt = $conn->prepare($query);
                if ($stmt === false) {
                    error_log("Prepare failed for ID query: " . $conn->error);
                    $form_error = "Database error in status check: " . $conn->error;
                } else {
                    $stmt->bind_param("i", $input);
                    $stmt->execute();
                    $result_set = $stmt->get_result();
                    if ($result_set->num_rows > 0) {
                        $status_result = $result_set->fetch_assoc();
                    }
                    $stmt->close();
                }
            } else {
                $query = "
                    SELECT aa.Application_ID, aa.Application_Status, aa.Application_Date, aa.Animal_Name, a.Animal_Breed
                    FROM adoptionapplication aa
                    LEFT JOIN Animal a ON aa.Animal_Name = a.Animal_Name
                    WHERE aa.Email = ?
                ";
                $stmt = $conn->prepare($query);
                if ($stmt === false) {
                    error_log("Prepare failed for email query: " . $conn->error);
                    $form_error = "Database error in status check: " . $conn->error;
                } else {
                    $stmt->bind_param("s", $input);
                    $stmt->execute();
                    $result_set = $stmt->get_result();
                    if ($result_set->num_rows > 0) {
                        $status_result = $result_set->fetch_assoc();
                    }
                    $stmt->close();
                }
            }
        }
        if (empty($status_result) && isset($_POST['check_status'])) {
            $form_error = "No application found with the provided Email or Application ID.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adopt a Pet - Makhanda SPCA</title>
    <link rel="stylesheet" href="adoption.css">
</head>

<body>
    <!-- SECTION 1: Main Navigation Bar at Very Top -->
    <nav>
        <div class="nav-logo">
            <a href="../frontPage.html">
                <img src="Paw prints logo.png" alt="Makhanda SPCA Logo">
            </a>
            <h2>Paw Prints</h2>
        </div>
        <div class="nav-links" id="nav">
            <ul>
                <li><a href="../frontPage.html">Home</a></li>
                <li><a href="../About us/aboutUs.html">About</a></li>
                <li>
                    <a href="#services">Services</a>
                    <ul class="Services">
                        <li><a href="adoption.php">Adopt</a></li>
                        <li><a href="volunteer.php">Volunteer</a></li>
                        <li><a href="../Cruelty Reports/Cruelty Management.php">Report</a></li>
                        <li><a href="../DONATIONS/donationSite.php">Donate</a></li><br>
                    </ul>
                </li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="../registerUser/LoginUser.php">Login</a></li>
            </ul>
        </div>
    </nav>

    <section class="MSPCA">
        <h3>Why Adopt from Makhanda SPCA?</h3>
        <div class="row">
            <div class="row1">
                <h4>Benefits of Adoption</h4>
                <p>Adopting a pet gives a loving animal a second chance. Our animals are health-checked, ensuring
                    they’re ready to join your family.</p>
            </div>
            <div class="row1">
                <h4>Our Adoption Process</h4>
                <p>Includes submitting an application, a meet-and-greet, a home check if required, and approval.</p>
            </div>
            <div class="row1">
                <h4>Adoption Requirements</h4>
                <p>Adopters must be 18+, provide proof of residence, and show commitment to pet care. Specific needs
                    will be discussed.</p>
            </div>
        </div>
    </section>

    <section class="MSPCA">
        <h3>Meet Our Adoptable Animals</h3>
        <p>Browse animals available for adoption.</p>
        <div class="row">
            <?php if ($error_message): ?>
                <div class="notification error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php elseif (empty($animals)): ?>
                <div class="empty-state">No animals available for adoption right now.</div>
            <?php else: ?>
                <?php foreach ($animals as $animal): ?>
                    <div class="row1">
                        <h3><?php echo htmlspecialchars($animal['Animal_Name']); ?></h3>
                        <p><?php echo htmlspecialchars($animal['Animal_Breed']); ?></p>

                        <!-- Display the image -->
                        <!-- Display the image -->
                        <?php if (!empty($animal['picture'])): ?>
                            <img src="<?php echo htmlspecialchars('../animal_intake_system/uploads' . $animal['picture']); ?>"
                                alt="Image of <?php echo htmlspecialchars($animal['Animal_Name']); ?>" />
                        <?php endif; ?>

                        <a href="#adoption-form" class="apply-btn">Apply to Adopt</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="MSPCA" id="adoption-form">
        <h3>Adoption Application & Status</h3>
        <div class="row">
            <div class="row1">
                <h3>Apply to Adopt</h3>
                <?php if ($success): ?>
                    <div class="notification success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if ($form_error): ?>
                    <div class="notification error"><?php echo htmlspecialchars($form_error); ?></div>
                <?php endif; ?>
                <form class="adoption-form" method="POST" onsubmit="return validateForm()">
                    <input type="hidden" name="submit_application" value="1">
                    <label for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" required>
                    <label for="last_name">Last Name:</label>
                    <input type="text" id="last_name" name="last_name" required>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                    <label for="phone">Phone:</label>
                    <input type="tel" id="phone" name="phone" pattern="[0-9]{10}" title="10-digit phone number"
                        required>
                    <label for="animal">Animal to Adopt:</label>
                    <select id="animal" name="animal" required>
                        <option value="">Select an animal</option>
                        <?php
                        if (!empty($animals)) {
                            foreach ($animals as $animal) {
                                echo "<option value='" . htmlspecialchars($animal['Animal_Name']) . "'>"
                                    . htmlspecialchars($animal['Animal_Name']) . " (" . htmlspecialchars($animal['Animal_Breed']) . ")</option>";
                            }
                        } else {
                            echo "<option value=''>No available animals</option>";
                        }
                        ?>
                    </select>
                    <label for="address">Address:</label>
                    <input type="text" id="address" name="address" required>
                    <label for="experience">Pet Ownership Experience:</label>
                    <textarea id="experience" name="experience" required></textarea>
                    <button type="submit">Submit Application</button>
                </form>
            </div>

            <div class="row1">
                <h3>Check Application Status</h3>
                <form class="status-check-form" method="POST">
                    <input type="hidden" name="check_status" value="1">
                    <label for="input">Email or Application ID:</label>
                    <input type="text" id="input" name="input" required>
                    <button type="submit">Check Status</button>
                </form>
                <?php if ($status_result): ?>
                    <div class='status-result'>
                        <p><strong>Application ID:</strong>
                            <?php echo htmlspecialchars($status_result['Application_ID']); ?></p>
                        <p><strong>Status:</strong> <span
                                class='status-badge status-<?php echo strtolower($status_result['Application_Status']); ?>'>
                                <?php echo htmlspecialchars($status_result['Application_Status']); ?></span></p>
                        <p><strong>Application Date:</strong>
                            <?php echo htmlspecialchars($status_result['Application_Date']); ?></p>
                        <p><strong>Animal:</strong> <?php echo htmlspecialchars($status_result['Animal_Name']); ?>
                            (<?php echo htmlspecialchars($status_result['Animal_Breed'] ?? 'Unknown'); ?>)</p>
                    </div>
                <?php endif; ?>
                <?php if (isset($_POST['check_status']) && empty($status_result) && !empty($_POST['input'])): ?>
                    <div class='notification error'><?php echo htmlspecialchars($form_error); ?></div>
                <?php endif; ?>
            </div>
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
        function validateForm() {
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;
            const experience = document.getElementById('experience').value;
            const animal = document.getElementById('animal').value;

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                alert('Please enter a valid email address.');
                return false;
            }

            const phonePattern = /^[0-9]{10}$/;
            if (!phonePattern.test(phone)) {
                alert('Please enter a valid 10-digit phone number.');
                return false;
            }

            if (!animal) {
                alert('Please select an animal to adopt.');
                return false;
            }

            if (experience.length < 10) {
                alert('Please provide more details about your pet ownership experience.');
                return false;
            }

            return true;
        }
    </script>
</body>

</html>