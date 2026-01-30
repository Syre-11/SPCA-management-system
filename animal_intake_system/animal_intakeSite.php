<?php
include 'DatabaseConnection.php';

// Initialize variables
$total_rescued = 0;
$next_id = 1;
$message = '';
$message_type = '';

// Get total animals count and next ID
try {
    $sql_count = "SELECT COUNT(*) as total FROM animal";
    $result_count = $conn->query($sql_count);
    if ($result_count) {
        $row_count = $result_count->fetch_assoc();
        $total_rescued = $row_count['total'];
    }

    $sql_next = "SELECT MAX(Animal_ID) + 1 as next_id FROM animal";
    $result_next = $conn->query($sql_next);
    if ($result_next) {
        $row_next = $result_next->fetch_assoc();
        $next_id = $row_next['next_id'] ?: 1;
    }
} catch (Exception $e) {
    // If there's an error, we'll use default values
    error_log("Error fetching initial data: " . $e->getMessage());
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Use the calculated next_id
        $Animal_ID = $next_id;

        // --- Collect animal data ---
        $Animal_Name = $_POST['Animal_Name'];
        $Animal_Species = $_POST['Animal_Species'];
        $Animal_Breed = $_POST['Animal_Breed'];
        $Animal_Age = (int) $_POST['Animal_Age'];
        $Animal_Gender = $_POST['Animal_Gender'];
        $Animal_Size = $_POST['Animal_Size'];
        $Animal_Health = $_POST['Animal_Health'];
        $Animal_Date = $_POST['Animal_Arrival_Date'];
        $Animal_Status = $_POST['Animal_AdoptionStatus'] ?? 'Available';

        // Validate size
        $valid_sizes = ['Small', 'Medium', 'Large'];
        if (!in_array($Animal_Size, $valid_sizes)) {
            throw new Exception("Invalid animal size. Please select Small, Medium, or Large.");
        }

        // --- Handle image upload ---
        $picture_path = '';
        if (!empty($_FILES['picture']['name'])) {
            $targetDir = __DIR__ . "/uploads/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            // Generate unique filename
            $fileExtension = pathinfo($_FILES["picture"]["name"], PATHINFO_EXTENSION);
            $filename = $Animal_ID . '_' . time() . '.' . $fileExtension;
            $targetFile = $targetDir . $filename;

            if (move_uploaded_file($_FILES["picture"]["tmp_name"], $targetFile)) {
                $picture_path = $filename;
                error_log("Image uploaded successfully: " . $targetFile);
            } else {
                throw new Exception("Image upload failed");
            }
        }

        // --- Insert animal without Kennel_ID (will be assigned later) ---
        $sqlAnimal = "INSERT INTO animal
            (Animal_ID, Animal_Name, Animal_Species, Animal_Breed, Animal_Age, Animal_Gender, Animal_Size, Animal_Health, Animal_Arrival_Date, Animal_AdoptionStatus, picture)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmtA = $conn->prepare($sqlAnimal);
        $stmtA->bind_param(
            "isssissssss",
            $Animal_ID,
            $Animal_Name,
            $Animal_Species,
            $Animal_Breed,
            $Animal_Age,
            $Animal_Gender,
            $Animal_Size,
            $Animal_Health,
            $Animal_Date,
            $Animal_Status,
            $picture_path
        );

        if (!$stmtA->execute()) {
            throw new Exception("Insert animal failed: " . $stmtA->error);
        }

        $message = "✅ Animal record created successfully with ID: $Animal_ID. You can now assign this animal to a kennel.";
        $message_type = "success";

        // Refresh the page to show success message and update counts
        header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&type=" . urlencode($message_type));
        exit();

    } catch (Exception $e) {
        $message = "❌ Error: " . $e->getMessage();
        $message_type = "error";
    }
}

// Check for message in URL parameters (after redirect)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = urldecode($_GET['message']);
    $message_type = urldecode($_GET['type']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animal Intake</title>
    <!--- <link rel="stylesheet" href="animal_records_theme.css">--->

    <style>
        /* Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        /* TOP SECTION 1: Main Navigation Bar */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 5%;
            background-color: #03574fb8;
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

        /* Responsive Design */
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
        }

        @media (max-width: 480px) {
            .nav-logo h2 {
                font-size: 14px;
            }

            nav img {
                width: 45px;
                height: 45px;
            }

            .nav-links ul li a {
                font-size: 13px;
            }
        }

        /* Body */
        body {
            padding-top: 80px;
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            line-height: 1.6;
        }

        /* Animal Intake Container */
        /* Animal Intake Container */
        .animal-intake-container {
            max-width: 800px;
            margin: 30px auto;
            text-align: left;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        /* Form Wrapper (optional refinement) */
        .animal-intake-form {
            width: 100%;
        }

        /* Headings inside the form */
        .animal-intake-form h2,
        .animal-intake-table th {
            text-align: center;
            color: #444;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        /* Labels inside table cells */
        .animal-intake-table .form-label {
            display: block;
            margin: 10px 0 5px;
            font-weight: 500;
            color: #34495e;
            font-size: 16px;
        }

        /* Table Style */
        .animal-intake-table {
            width: 100%;
            border-collapse: collapse;
        }

        .animal-intake-table th {
            background: linear-gradient(45deg, rgba(5, 111, 101, 0.7));
            color: white;
            font-size: 1.2rem;
            padding: 18px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }

        .animal-intake-table td {
            padding: 15px;
            vertical-align: middle;
        }



        /* Input fields */
        .animal-intake-table input[type="text"],
        .animal-intake-table input[type="number"],
        .animal-intake-table input[type="date"],
        .animal-intake-table input[type="file"],
        .animal-intake-table select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .animal-intake-table input:focus,
        .animal-intake-table select:focus {
            border-color: #056f65b8;
            outline: none;
            box-shadow: 0 0 5px rgba(5, 111, 101, 0.3);
        }

        /* Buttons */
        .animal-intake-table input[type="submit"],
        .animal-intake-table input[type="reset"],
        .animal-intake-button {
            display: inline-block;
            background: linear-gradient(rgba(5, 111, 101, 0.7));
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 8px 5px;
        }

        .animal-intake-table input[type="submit"]:hover,
        .animal-intake-table input[type="reset"]:hover,
        .animal-intake-button:hover {
            background: #045950;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.12);
        }

        /* Secondary Button */


        /* Button container */
        .button-container {
            display: flex;
            justify-content: flex-end;
            flex-wrap: nowrap;
            gap: 10px;
            margin-top: 25px;
        }

        /* Radio Buttons */
        .radio-group {
            display: flex;
            gap: 15px;
            margin: 12px 0;
        }

        .radio-group label {
            display: flex;
            align-items: center;
            font-size: 15px;
            color: #444;
        }

        .radio-group input[type="radio"] {
            margin-right: 8px;
        }

        /* Stats Section */
        .stats-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-around;
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #056f65b8;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #555;
        }

        /* Messages */
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
        }

        /* Labels */
        .form-label {
            font-weight: bold;
            color: #2c3e50;
        }

        .required-field::after {
            content: " *";
            color: #e22;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .button-container {
                flex-direction: column;
            }

            .animal-intake-button,
            .animal-intake-table input[type="submit"],
            .animal-intake-table input[type="reset"] {
                width: 100%;
            }

            .stats-container {
                flex-direction: column;
                gap: 15px;
            }
        }

        /* General Button Styling */
        button,
        .btn {
            background-color: #03574f;
            /* same as navbar tone */
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        /* Hover effect */
        button:hover,
        .btn:hover {
            background-color: #03574fb8;
            /* lighter teal on hover */
            transform: translateY(-2px);
            /* subtle lift */
        }

        /* Disabled state */
        button:disabled,
        .btn:disabled {
            background-color: #9ca3af;
            cursor: not-allowed;
            transform: none;
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
            padding: 0;
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

        /* Footer Section */
        footer {
            background-color: #056f65b8;
            color: white;
            padding: 40px 20px 20px;
            position: relative;
            margin-top: 460px;
            /* Adjusted for nav-container min-height */
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
    </style>

</head>

<body class="animal-records-theme">
    <!-- Navigation Bar -->
    <nav>
        <div class="nav-logo">
            <img src="images/Logo.png" alt="Makhanda SPCA Logo">
            <h2>Paw Prints</h2>
        </div>
        <div class="nav-links">
            <ul>
                <li><a href="../Cruelty Reports/admin_dashboard.php">Dashboard</a></li>
                <li><a href="display_animals.php">View Animals</a></li>
                <li><a href="allocate_kennel.php">Manage Kennels</a></li>
                <li><a href="edit_kennels.php">Edit Kennels</a></li>
                <li><a href="../registerUser/logout.php">Logout</a></li>

            </ul>
        </div>
    </nav>

    <div class="animal-intake-container">
        <div class="animal-intake-form">
            <!-- Statistics Section -->
            <div class="stats-container">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $total_rescued; ?></span>
                    <span class="stat-label">Animals Rescued</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $next_id; ?></span>
                    <span class="stat-label">Next Animal ID</span>
                </div>
            </div>

            <!-- Message Display -->
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                    <?php if ($message_type == 'success'): ?>
                        <br><a href="allocate_kennel.php" class="animal-intake-button"
                            style="margin-top: 10px; display: inline-block;">Assign Kennel</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form action="animal_intake.php" method="POST" enctype="multipart/form-data" id="animalForm">
                <table class="animal-intake-table">
                    <tr>
                        <th colspan="2">Animal Intake Form</th>
                    </tr>

                    <!-- Animal Table Inputs -->
                    <tr>
                        <td class="form-label">Pet ID Number:</td>
                        <td>
                            <input type="text" id="Animal_ID" name="Animal_ID" value="<?php echo $next_id; ?>" readonly>
                            <small style="display: block; color: #6c757d; margin-top: 5px;">Auto-generated ID</small>
                        </td>
                    </tr>

                    <tr>
                        <td class="form-label required-field">Type:</td>
                        <td>
                            <div class="radio-group">
                                <label for="species_dog">
                                    <input type="radio" id="species_dog" name="Animal_Species" value="Dog" required>
                                    Dog
                                </label>
                                <label for="species_cat">
                                    <input type="radio" id="species_cat" name="Animal_Species" value="Cat" required>
                                    Cat
                                </label>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td class="form-label required-field">Pet Name:</td>
                        <td><input type="text" id="Animal_Name" name="Animal_Name" required></td>
                    </tr>

                    <tr>
                        <td class="form-label required-field">Gender:</td>
                        <td>
                            <select name="Animal_Gender" id="Animal_Gender" required>
                                <option value="">Select Gender</option>
                                <option value="Female">Female</option>
                                <option value="Male">Male</option>
                                <option value="Unknown">Unknown</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td class="form-label required-field">Breed:</td>
                        <td>
                            <select name="Animal_Breed" id="Animal_Breed" required>
                                <option value="">Select Breed</option>
                                <optgroup label="Dog Breeds">
                                    <option value="Affenpinscher">Affenpinscher</option>
                                    <option value="Akita">Akita</option>
                                    <option value="Beagle">Beagle</option>
                                    <option value="German Sheperd">German Sheperd</option>
                                    <option value="Labrador Retriever">Labrador Retriever</option>
                                </optgroup>
                                <optgroup label="Cat Breeds">
                                    <option value="Abyssinian">Abyssinian</option>
                                    <option value="Bengal">Bengal</option>
                                    <option value="Maine Coon">Maine Coon</option>
                                    <option value="Persian">Persian</option>
                                    <option value="Siamese">Siamese</option>
                                </optgroup>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td class="form-label required-field">Age:</td>
                        <td>
                            <input type="number" id="Animal_Age" name="Animal_Age" min="0" max="30" required>
                            <span>Years</span>
                            <small style="display: block; color: #6c757d; margin-top: 5px;">Enter 0 for less than 1
                                year</small>
                        </td>
                    </tr>

                    <tr>
                        <td class="form-label required-field">Size:</td>
                        <td>
                            <select name="Animal_Size" id="Animal_Size" required>
                                <option value="">Select Size</option>
                                <option value="Small">Small</option>
                                <option value="Medium">Medium</option>
                                <option value="Large">Large</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td class="form-label required-field">Health Status:</td>
                        <td>
                            <select name="Animal_Health" required>
                                <option value="">Select Health Status</option>
                                <option value="Excellent">Excellent</option>
                                <option value="Good">Good</option>
                                <option value="Fair">Fair</option>
                                <option value="Poor">Poor</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td class="form-label required-field">Intake Date:</td>
                        <td><input type="date" id="Animal_Arrival_Date" name="Animal_Arrival_Date" required></td>
                    </tr>

                    <tr>
                        <td class="form-label">Adoption Status:</td>
                        <td>
                            <select name="Animal_AdoptionStatus">
                                <option value="Available">Available</option>
                                <option value="Adopted">Adopted</option>
                                <option value="Pending">Pending</option>
                                <option value="Deceased">Deceased</option>
                            </select>
                        </td>
                    </tr>

                    <!-- File Upload -->
                    <tr>
                        <td class="form-label required-field">Image:</td>
                        <td>
                            <input type="file" id="picture" name="picture" accept="image/*" required>
                            <small style="display: block; color: #6c757d; margin-top: 5px;">Upload a clear photo of the
                                animal</small>
                        </td>
                    </tr>

                    <!-- Buttons -->
                    <tr>
                        <td colspan="2">
                            <div class="button-container">
                                <input type="submit" value="Create Animal Record">
                                <input type="reset" value="Clear Form" class="button-secondary">
                            </div>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    </div>

    <footer>
        <div class="footerHeader">
            <h3>Paw Prints - Where every paw matters</h3>
        </div>
        <hr>
        <p>© <?php echo date('Y'); ?> SPCA Makhanda</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Set today's date as default for intake date
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('Animal_Arrival_Date').value = today;

            // Auto-select size based on breed
            document.getElementById('Animal_Breed').addEventListener('change', function () {
                const breed = this.value;
                const sizeSelect = document.getElementById('Animal_Size');

                // Reset to default
                sizeSelect.value = '';

                // Auto-select size based on breed (example logic)
                const largeBreeds = ['German Sheperd', 'Labrador Retriever', 'Akita', 'Maine Coon'];
                const mediumBreeds = ['Beagle', 'Abyssinian', 'Bengal'];
                const smallBreeds = ['Affenpinscher', 'Persian', 'Siamese'];

                if (largeBreeds.includes(breed)) {
                    sizeSelect.value = 'Large';
                } else if (mediumBreeds.includes(breed)) {
                    sizeSelect.value = 'Medium';
                } else if (smallBreeds.includes(breed)) {
                    sizeSelect.value = 'Small';
                }
            });

            // Form validation
            document.getElementById('animalForm').addEventListener('submit', function (e) {
                let valid = true;
                const requiredFields = this.querySelectorAll('[required]');

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        valid = false;
                        field.style.borderColor = '#e22';
                    } else {
                        field.style.borderColor = '';
                    }
                });

                if (!valid) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                }
            });
        });
    </script>
</body>

</html>