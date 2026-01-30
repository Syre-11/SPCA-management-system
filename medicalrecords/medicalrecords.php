<?php
// Database configuration
$serverName = "";
$user = "";
$password = "";
$database = "";

// Create connection
$conn = new mysqli($serverName, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection to server and database failed: " . $conn->connect_error);
}

// Initialize variables
$animalId = $procedureType = $vetId = $procedureDate = $nextDueDate = $medication = "";
$dosage = $frequency = $duration = $cost = $status = $notes = "";
$successMessage = $errorMessage = "";



// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input data
    $animalId = sanitizeInput($_POST["Animal_ID"]);
    $procedureType = sanitizeInput($_POST["procedure_type"]);
    $vetId = sanitizeInput($_POST["vet_id"]);
    $procedureDate = sanitizeInput($_POST["procedure_date"]);
    $nextDueDate = sanitizeInput($_POST["next_due_date"]);
    $medication = sanitizeInput($_POST["medication"]);
    $dosage = sanitizeInput($_POST["dosage"]);
    $frequency = sanitizeInput($_POST["frequency"]);
    $duration = sanitizeInput($_POST["duration"]);
    $cost = sanitizeInput($_POST["cost"]);
    $status = sanitizeInput($_POST["status"]);
    $notes = sanitizeInput($_POST["notes"]);

    // Validate required fields
    if (
        !empty($animalId) && !empty($procedureType) && !empty($vetId) &&
        !empty($procedureDate) && !empty($nextDueDate) && !empty($status)
    ) {

        // Validate dates according to guidelines
        $currentDate = date("Y-m-d");
        $twoWeeksLater = date("Y-m-d", strtotime("+2 weeks"));
        $procedureDateOnly = date("Y-m-d", strtotime($procedureDate));

        // Check if procedure date is valid (past or up to 2 weeks future)
        if ($procedureDateOnly > $twoWeeksLater) {
            $errorMessage = "Procedure date cannot be more than 2 weeks from today.";
        }
        // Check if next due date is valid (today or future)
        elseif ($nextDueDate < $currentDate) {
            $errorMessage = "Next due date cannot be in the past.";
        } else {
            // Prepare and bind
            $stmt = $conn->prepare("INSERT INTO medicalrecords (Animal_ID, procedure_type, vet_id, procedure_date, next_due_date, medication, dosage, frequency, duration, cost, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssdss", $animalId, $procedureType, $vetId, $procedureDate, $nextDueDate, $medication, $dosage, $frequency, $duration, $cost, $status, $notes);

            // Execute the statement
            if ($stmt->execute()) {
                $successMessage = "Record added successfully!";
                // Clear form fields
                $animalId = $procedureType = $vetId = $procedureDate = $nextDueDate = $medication = "";
                $dosage = $frequency = $duration = $cost = $status = $notes = "";
            } else {
                $errorMessage = "Error: " . $stmt->error;
            }

            $stmt->close();
        }
    } else {
        $errorMessage = "Please fill all required fields correctly.";
    }
}

function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Calculate default dates for the form
$defaultProcedureDate = date("Y-m-d\TH:i");
$defaultNextDueDate = date("Y-m-d");
$maxProcedureDate = date("Y-m-d\TH:i", strtotime("+2 weeks"));

// Close connection (will be done automatically at end of script)
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Record - Animal Medical Records System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        /* Body styling */
        body {
            padding-top: 80px;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            line-height: 1.6;
        }

        /* TOP SECTION: Main Navigation Bar */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 5%;
            background-color: #1ae3ccb7;
            background: rgba(5, 111, 101, 0.7);
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
            background-color: rgba(5, 111, 101, 0.7);
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

        /* Dropdown */
        nav .nav-links ul li ul {
            position: absolute;
            top: 100%;
            left: 0;
            background: rgba(4, 230, 196, 0.667);
            min-width: 180px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            border-radius: 6px;
            padding: 8px 0;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        nav .nav-links ul li:hover ul {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        nav .nav-links ul li ul li {
            display: block;
            padding: 0;
        }

        nav .nav-links ul li ul li a {
            display: block;
            color: white;
            padding: 12px 20px;
            font-size: 14px;
            border-radius: 0;
        }

        /* Container styling */
        .container {
            max-width: 800px;
            margin: 20px auto;
            text-align: left;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        /* Header styling */
        header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }

        header h1 {
            color: #444;
            font-size: 2.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        header h1 i {
            color: rgba(4, 230, 196, 0.667);
        }

        /* Tab content styling */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .tab-content h2 {
            color: #444;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.8rem;
        }

        .tab-content h2 i {
            color: rgba(5, 111, 101, 0.7);
        }

        /* Alert styling */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .alert-success {
            background: #d1fae5;
            color: #059669;
            border-left: 4px solid #059669;
        }

        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }

        /* Form styling */
        #createForm {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin: 10px 0 5px;
            font-weight: 500;
            color: #444;
            font-size: 16px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 16px;
            transition: all 0.3s;
            background-color: #f8f9fa;
        }

        .form-group {
            margin-bottom: 20px;
            /* space between fields */
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: rgba(5, 111, 101, 0.7);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Date info and vet info sections */
        .date-info,
        .medication-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            grid-column: 1 / -1;
        }

        /* Button group styling */
        .btn-group {
            grid-column: 1 / -1;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .btn-add,
        .btn-delete {
            display: block;
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

        .btn-add {
            background: rgba(5, 111, 101, 0.7);
        }

        .btn-add:hover {
            background: #045950;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: rgba(5, 111, 101, 0.7);
        }

        .btn-delete:hover {
            background: #045950;
            transform: translateY(-2px);
        }

        /* Footer Section */
        /* Footer */
        /* Footer Section */
        footer {
            background-color: rgba(5, 111, 101, 0.7);
            color: white;
            padding: 40px 20px;
            position: relative;
            margin-top: 60px;
        }

        .footerHeader {
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8rem;
        }

        .footer-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 20px;
        }

        .footer-section {
            flex: 1;
            min-width: 200px;
        }

        .footer-section h3 {
            margin-bottom: 10px;
        }

        .footer-section p,
        .footer-section a {
            color: white;
            text-align: center;
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .footer-section a:hover {
            text-decoration: underline;
        }

        /* Responsive Design */
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

            /* Form adjustments */
            #createForm {
                grid-template-columns: 1fr;
            }

            .date-info,
            .medication-info {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 20px;
            }

            header h1 {
                font-size: 1.8rem;
            }

            .tab-content h2 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .btn-group {
                flex-direction: column;
            }

            .btn-add,
            .btn-delete {
                width: 100%;
                justify-content: center;
            }

            header h1 {
                font-size: 1.5rem;
            }

            .tab-content h2 {
                font-size: 1.3rem;
            }
        }

        .db-status {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: #045950;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 14px;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .back-btn {
            display: block;
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

        .back-btn:hover {
            background: #045950;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>


    <!-- Database Connection Status -->
    <div class="db-status">
        <i class="fas fa-database"></i> Connected to database successfully
    </div>

    <!-- Navigation Bar -->
    <nav>
        <div class="nav-logo">
            <img src="../Adopt and Volunteer/Paw prints logo.png" alt="MSPCA Logo">
            <h2>Makhanda SPCA</h2>
        </div>
        <div class="nav-links">
            <ul>
                <li><a href="vetdashboard.php"></i> Dashboard</a></li>
                <li><a href="display.php"></i> View Records</a></li>
                <li><a href="../registerUser/logout.php"></i> Logout</a></li>

            </ul>
        </div>
    </nav>

    <div class="container">
        <header>
            <h1>Animal Medical Records System</h1>
        </header>

        <div class="tab-content active" id="create">
            <h2><i class="fas fa-plus-circle"></i> Create New Record</h2>

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success" id="createSuccessAlert">
                    <?php echo $successMessage; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-error" id="createErrorAlert">
                    <?php echo $errorMessage; ?>
                </div>
            <?php endif; ?>


<form id="createForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <div class="form-group">
        <label for="animalId">Animal ID:*</label>
        <select id="animalId" name="Animal_ID" required>
            <option value="">Select Animal</option>
            <?php
            $sql = "SELECT Animal_ID, Animal_Name FROM animal";
            $result = $conn->query($sql);

            while ($row = $result->fetch_assoc()) {
                $selected = ($animalId == $row['Animal_ID']) ? 'selected' : '';
                echo "<option value='{$row['Animal_ID']}' $selected>{$row['Animal_ID']} - {$row['Animal_Name']}</option>";
            }
            ?>
        </select>
    </div>








                <div class="form-group">
                    <label for="procedureType">Procedure Type:*</label>
                    <select id="procedureType" name="procedure_type" required>
                        <option value="">Select procedure type</option>
                        <option value="Vaccination" <?php if ($procedureType == "Vaccination")
                            echo "selected"; ?>>
                            Vaccination</option>
                        <option value="Check-up" <?php if ($procedureType == "Check-up")
                            echo "selected"; ?>>Check-up
                        </option>
                        <option value="Surgery" <?php if ($procedureType == "Surgery")
                            echo "selected"; ?>>Surgery
                        </option>
                        <option value="Dental" <?php if ($procedureType == "Dental")
                            echo "selected"; ?>>Dental Cleaning
                        </option>
                        <option value="Grooming" <?php if ($procedureType == "Grooming")
                            echo "selected"; ?>>Grooming
                        </option>
                        <option value="Other" <?php if ($procedureType == "Other")
                            echo "selected"; ?>>Other</option>
                    </select>
                </div>




                <div class="date-info">
                    <div class="form-group procedure-date-container">
                        <label for="procedureDate">Procedure Date and Time: *</label>
                        <input type="datetime-local" id="procedureDate" name="procedure_date"
                            value="<?php echo !empty($procedureDate) ? $procedureDate : $defaultProcedureDate; ?>"
                            max="<?php echo $maxProcedureDate; ?>" required>
                    </div>

                    <div class="form-group due-date-container">
                        <label for="nextDueDate">Next Due Date:*</label>
                        <input type="date" id="nextDueDate" name="next_due_date"
                            value="<?php echo !empty($nextDueDate) ? $nextDueDate : $defaultNextDueDate; ?>"
                            min="<?php echo $defaultNextDueDate; ?>" required>
                    </div>
                </div>





<div class="form-group">
    <label for="vetId">Vet ID:*</label>
    <select id="vetId" name="vet_id" required>
        <option value="">Select Vet ID</option>
        <option value="11" <?php if ($vetId == "11") echo "selected"; ?>>11 - Dr. Bongani</option>
        <option value="12" <?php if ($vetId == "12") echo "selected"; ?>>12 - Dr. Lindiwe</option>
        <option value="21" <?php if ($vetId == "21") echo "selected"; ?>>21 - Dr. Thabo</option>
    
    </select>
</div>





                <!-- <div class="form-group">
                    <label for="medication">Medication Name</label>
                    <input type="text" id="medication" name="medication" placeholder="Enter medication name" value="<?php echo $medication; ?>">
                </div> -->


                <div class="form-group">
                    <label for="medication">Medication Name:</label>
                    <select id="medication" name="medication">
                        <option value="">-- Select medication --</option>
                        <option value="Pain Relief" <?php if ($medication == "Pain Relief")
                            echo "selected"; ?>>Pain Relief
                        </option>
                        <option value="Antibiotic" <?php if ($medication == "Antibiotic")
                            echo "selected"; ?>>Antibiotic
                        </option>
                        <option value="Anti-inflammatory" <?php if ($medication == "Anti-inflammatory")
                            echo "selected"; ?>>Anti-inflammatory</option>
                        <option value="Fever Reducer" <?php if ($medication == "Fever Reducer")
                            echo "selected"; ?>>Fever
                            Reducer</option>
                        <option value="Stomach Medicine" <?php if ($medication == "Stomach Medicine")
                            echo "selected"; ?>>
                            Stomach Medicine</option>
                        <option value="Skin Cream" <?php if ($medication == "Skin Cream")
                            echo "selected"; ?>>Skin Cream
                        </option>
                        <option value="Eye Drops" <?php if ($medication == "Eye Drops")
                            echo "selected"; ?>>Eye Drops
                        </option>
                        <option value="Worm Treatment" <?php if ($medication == "Worm Treatment")
                            echo "selected"; ?>>Worm
                            Treatment</option>
                        <option value="Flea Medicine" <?php if ($medication == "Flea Medicine")
                            echo "selected"; ?>>Flea
                            Medicine</option>
                        <option value="Vaccine" <?php if ($medication == "Vaccine")
                            echo "selected"; ?>>Vaccine</option>
                    </select>
                </div>


                <div class="medication-info">
                    <div class="form-group">
                        <label for="dosage">Dosage:</label>
                        <input type="text" id="dosage" name="dosage" value="<?php echo $dosage; ?>">
                    </div>

                    <div class="form-group">
                        <label for="frequency">Frequency:</label>
                        <input type="text" id="frequency" name="frequency" value="<?php echo $frequency; ?>">
                    </div>

                    <div class="form-group">
                        <label for="duration">Duration:</label>
                        <input type="text" id="duration" name="duration" value="<?php echo $duration; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="cost">Cost (Rand):</label>
                    <input type="number" id="cost" name="cost" value="<?php echo $cost; ?>">
                </div>

                <div class="form-group">
                    <label for="status">Status:*</label>
                    <select id="status" name="status" required>
                        <option value="">Select status</option>
                        <option value="Completed" <?php if ($status == "Completed")
                            echo "selected"; ?>>Completed</option>
                        <option value="Pending" <?php if ($status == "Pending")
                            echo "selected"; ?>>Pending</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="notes">Notes:</label>
                    <textarea id="notes" name="notes" rows="3"><?php echo $notes; ?></textarea>
                </div>

                <div class="btn-group">
                    <button type="reset" class="btn-delete"> Clear Form</button>
                    <button type="submit" class="btn-add"> Add Record</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <h3 class="footerHeader">Paw Prints - Where every paw matters</h3>
        <hr>
        <p>&copy; 2025 SPCA Makhanda</p>
    </footer>

    <script>
        // Hide alerts after 3 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 3000);

        // Hide database status after 5 seconds
        setTimeout(() => {
            document.querySelector('.db-status').style.display = 'none';
        }, 5000);

        // Add validation for procedure date on change
        document.getElementById('procedureDate').addEventListener('change', function () {
            const selectedDate = new Date(this.value);
            const now = new Date();
            const twoWeeksFromNow = new Date(now);
            twoWeeksFromNow.setDate(now.getDate() + 14);

            if (selectedDate > twoWeeksFromNow) {
                alert('Procedure date cannot be more than 2 weeks from today.');
                const localDateTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000)
                    .toISOString()
                    .slice(0, 16);
                this.value = localDateTime;
            }
        });

        // Add validation for next due date on change
        document.getElementById('nextDueDate').addEventListener('change', function () {
            const selectedDate = new Date(this.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                alert('Next due date cannot be in the past.');
                this.value = today.toISOString().split('T')[0];
            }
        });
    </script>
</body>

</html>
<?php
// Close connection
$conn->close();
?>