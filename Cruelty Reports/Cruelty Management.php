<?php
session_start(); // 1. Start session before any output

if (isset($_SESSION['report_success'])) {
    echo "<p style='color: green; text-align: center;'>" . $_SESSION['report_success'] . "</p>";
    unset($_SESSION['report_success']);
}

include 'DatabaseConnection.php';

// --- Fetch dropdown data (if needed for form) ---
$animals = [];
$animalResult = $conn->query("SELECT Animal_ID, Animal_Name FROM animal ORDER BY Animal_Name ASC");
if ($animalResult->num_rows > 0) {
    while ($row = $animalResult->fetch_assoc()) {
        $animals[] = $row;
    }
}

$staff = [];
$staffResult = $conn->query("SELECT SystemUser_ID, FirstName, Surname FROM systemuser ORDER BY FirstName ASC");
if ($staffResult->num_rows > 0) {
    while ($row = $staffResult->fetch_assoc()) {
        $staff[] = $row;
    }
}

// --- Handle form submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $FirstName   = $_POST['FirstName'] ?? null;
    $Surname     = $_POST['Surname'] ?? null;
    $CellNumber  = $_POST['CellNumber'] ?? null;
    $Email       = $_POST['Email'] ?? null;
    $ReportDate  = $_POST['IncidentDate'] ?? null;
    $Description = $_POST['Details'] ?? null;
    $Location    = $_POST['Location'] ?? null;
    $FK_Animal_ID = $_POST['Animal_ID'] ?? null;

    // Get system user from session instead of the form
    $FK_SystemUser_ID = $_SESSION['SystemUser_ID'] ?? null;

    // Validate required fields
    if (empty($ReportDate) || empty($Location) || empty($Description) || empty($FK_Animal_ID)) {
        die("Error: Please fill all required fields for the report.");
    }

    // Validate email
    if (!empty($Email) && !filter_var($Email, FILTER_VALIDATE_EMAIL)) {
        die("Error: Invalid email format.");
    }

    // Prevent future dates
    if ($ReportDate > date("Y-m-d")) {
        die("Error: Report date cannot be in the future.");
    }

    // Handle file upload
    $evidence_path = null;
    if (isset($_FILES['Evidence']) && $_FILES['Evidence']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $filename = basename($_FILES['Evidence']['name']);
        $evidence_path = $uploadDir . uniqid() . '_' . $filename;
        if (!move_uploaded_file($_FILES['Evidence']['tmp_name'], $evidence_path)) {
            die("Error uploading evidence file.");
        }
    }

    // Insert reporter if details provided
    $FK_Reporter_ID = null;
    if (!empty($FirstName) || !empty($Surname) || !empty($CellNumber) || !empty($Email)) {
        $stmt = $conn->prepare("INSERT INTO reporter (FirstName, Surname, CellNumber, Email) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $FirstName, $Surname, $CellNumber, $Email);
        $stmt->execute();
        $FK_Reporter_ID = $stmt->insert_id;
        $stmt->close();
    }

    // Insert cruelty report
    $stmt = $conn->prepare("INSERT INTO crueltyreport 
        (ReportDate, Description, Location, FK_Animal_ID, FK_Reporter_ID, FK_SystemUser_ID, evidence)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "sssiiis",
        $ReportDate,
        $Description,
        $Location,
        $FK_Animal_ID,
        $FK_Reporter_ID,
        $FK_SystemUser_ID,
        $evidence_path
    );
    $stmt->execute();

    // Get the inserted report ID
    $lastReportId = $conn->insert_id;

    $stmt->close();

    // Set success flash message
    $_SESSION['report_success'] = $FK_Reporter_ID
        ? "Cruelty report submitted successfully. <br>Your Report ID is: <strong>{$lastReportId}</strong>"
        : "Anonymous cruelty report submitted. <br>Your Report ID is: <strong>{$lastReportId}</strong>";

    // Redirect back to form or another page
    header("Location: Cruelty Management.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Cruelty Report</title>
    <link rel="stylesheet" href="Cruelty Managements.css">
</head>
<body>
<!-- Navigation Bar -->
<nav class="navbar">
    <a href="../frontPage.html" class="logo">
        <img src="Paw prints logo.png" alt="Logo">
    </a>
    <div class="nav-links">
        <ul>
            <li><a href="../frontPage.html">Home</a></li>
            <li><a href="../About us/aboutUs.html">About</a></li>
            <li><a href="">Services</a>
            <ul class="Services">
                        <li><a href="../Adopt and Volunteer/adoption.php">Adopt</a></li>
                        <li><a href="../Adopt and Volunteer/volunteer.php">Volunteer</a></li>
                        <li><a href="Cruelty Management.php">Report</a></li>
                        <li><a href="../DONATIONS/donationSite.php">Donate</a></li><br>
                    </ul>
            </li>
            <li><a href="">Contact</a></li>
            <li><a href="../registerUser/LoginUser.php">Login</a></li>
        </ul>
    </div>
</nav>

<!-- Banner -->
<div class="banner">
    <img src="Stop cruelty.png" alt="image">
</div>


<!-- Cruelty Report Options -->
<section class="report-options">
    <div class="report-grid">
        <div class="card">
            <img src="Image1.jpeg" alt="Report Abuse">
            <h3>Report Abuse</h3>
            <p>Report cases of neglect, abandonment, or abuse. (Available 24/7)</p>
        </div>
        <div class="card">
            <img src="Image2.jpeg" alt="Emergency Rescue">
            <h3>Emergency Rescue</h3>
            <p>Request immediate rescue for animals in life-threatening situations. (Hotline support)</p>
        </div>
        <div class="card">
            <img src="Image3.jpeg" alt="Anonymous Tip">
            <h3>Anonymous Tip</h3>
            <p>Submit confidential information about cruelty cases without revealing your identity.</p>
        </div>
        <div class="card">
            <img src="Image 4.png" alt="Follow-Up">
            <h3>Follow-Up</h3>
            <p>Check the status of a report you submitted and get updates on actions taken.</p>
        </div>
    </div>
</section>
<!-- Status Check Section -->

<!-- Form -->
<section class="form-container">
    <h2>Submit Cruelty Report</h2>
    <form method="post" action="" enctype="multipart/form-data">

        <label for="IncidentDate">Incident Date:</label>
        <input type="date" name="IncidentDate" required>

        <label for="Location">Location:</label>
        <input type="text" name="Location" required>

        <label for="Details">Details of the Incident:</label>
        <textarea name="Details" rows="5" required></textarea>

        <label for="Evidence">Upload Evidence (image/video):</label>
        <input type="file" name="Evidence">

        <label for="Animal_ID">Select Animal:</label>
        <select name="Animal_ID" id="Animal_ID" required>
            <option value="" disabled selected>-- Select Animal --</option>
            <?php foreach($animals as $a): ?>
                <option value="<?= $a['Animal_ID'] ?>"><?= htmlspecialchars($a['Animal_Name']) ?></option>
            <?php endforeach; ?>
        </select>

        <div class="row">
            <label class="checkbox-label">Report Anonymously: </label><br>
            <input type="checkbox" id="anonymousCheck" onclick="toggleReporter()">
        </div>

        <div id="reporterFields">
            <h2>Reporter Details</h2>
            <label for="FirstName">First Name:</label>
            <input type="text" name="FirstName" id="FirstName">

            <label for="Surname">Surname:</label>
            <input type="text" name="Surname" id="Surname">

            <label for="CellNumber">Cell Number:</label>
            <input type="text" name="CellNumber" id="CellNumber">

            <label for="Email">Email:</label>
            <input type="email" name="Email" id="Email">
        </div>

        <div class="button-group">
            <button type="submit">Submit Report</button>
            <button type="reset">Reset</button>
        </div>
    </form>
</section>
<section class="check-status">
    <h2>Check the Status of Your Report</h2>
    <form method="GET" action="">
        <label for="report_id">Enter Your Report ID:</label>
        <input type="text" name="report_id" id="report_id" placeholder="e.g., 1" required>
        <button type="submit">Check Status</button>
    </form>

    <?php
    if (isset($_GET['report_id'])) {
        $report_id = intval($_GET['report_id']); // sanitize input
        $statusQuery = $conn->query("SELECT Report_ID, Status, Urgent FROM crueltyreport WHERE Report_ID = $report_id");

        if ($statusQuery && $statusQuery->num_rows > 0) {
            $report = $statusQuery->fetch_assoc();
            echo "<div class='status-result'>";
            echo "<p><strong>Report ID:</strong> {$report['Report_ID']}</p>";
            echo "<p><strong>Status:</strong> {$report['Status']}</p>";
            if ($report['Urgent']) {
                echo "<p><strong>Urgent Case</strong></p>";
            }
            echo "</div>";
        } else {
            echo "<p>No report found with ID {$report_id}.</p>";
        }
    }
    ?>
</section>
<!-- Success Stories -->
<section class="success-stories">
    <h2>Success Stories</h2>
    <div class="stories-grid">
        <div class="story-card">
            <img src="Max Dog.png" alt="Happy Dog">
            <h3>From Rescue to Recovery</h3>
            <p>Max was found abandoned and in poor health. Thanks to a quick rescue and community support, he is now thriving in a loving home.</p>
        </div>
        <div class="story-card">
            <img src="Luna Cat.png" alt="Rescued Cat">
            <h3>A Second Chance</h3>
            <p>Luna was rescued from neglect and has since recovered beautifully. She is now adopted and brings joy to her new family.</p>
        </div>
        <div class="story-card">
            <img src="Happy puppies.png" alt="Puppies Playing">
            <h3>Together Again</h3>
            <p>These puppies were rescued during an emergency operation. Today, they are healthy, playful, and safe with caring families.</p>
        </div>
    </div>
</section>
  <!-- Footer Section -->
    <footer>
        <h2 class="footerHeader">Paw Prints - Where every paw matters</h2>
        <hr>
        <a href="../frontPage.html">
            <img src="../Cruelty Reports/Paw prints logo.png" alt="Makhanda SPCA Logo">
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

<script>
function toggleReporter() {
    const checkbox = document.getElementById('anonymousCheck');
    const reporterFields = document.getElementById('reporterFields');
    reporterFields.style.display = checkbox.checked ? 'none' : 'block';
}
</script>

</body>
</html>
