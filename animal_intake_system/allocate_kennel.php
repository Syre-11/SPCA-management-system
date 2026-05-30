<?php
include 'DatabaseConnection.php';

// First, ensure the animal table has the Kennel_ID and Animal_Size columns
$alterAnimalTable = "ALTER TABLE animal 
               ADD COLUMN IF NOT EXISTS Kennel_ID INT NULL AFTER picture,
               ADD COLUMN IF NOT EXISTS Animal_Size ENUM('Small', 'Medium', 'Large') NULL AFTER Animal_Gender";
$conn->query($alterAnimalTable);

// Handle kennel creation
if (isset($_POST['create_kennel'])) {
    $Kennel_ID = (int) $_POST['Kennel_ID'];
    $Kennel_Name = $_POST['Kennel_Name'];
    $Capacity = (int) $_POST['Capacity'];
    $Size = $_POST['Size'];
    $Animal_Type = $_POST['Animal_Type'];

    // Validate ENUM values
    $valid_sizes = ['Small', 'Medium', 'Large'];
    $valid_animal_types = ['Dog', 'Cat', 'Mixed'];

    if (!in_array($Size, $valid_sizes)) {
        $kennel_message = "<p style='color: red; padding: 10px; background: #ffe6e6; border-radius: 4px;'>❌ Error: Invalid Size value.</p>";
    } elseif (!in_array($Animal_Type, $valid_animal_types)) {
        $kennel_message = "<p style='color: red; padding: 10px; background: #ffe6e6; border-radius: 4px;'>❌ Error: Invalid Animal_Type value.</p>";
    } else {
        // Insert new kennel
        $sql = "INSERT INTO kennel (Kennel_ID, Kennel_Name, Capacity, Size, Animal_Type, Availability) 
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isissi", $Kennel_ID, $Kennel_Name, $Capacity, $Size, $Animal_Type, $Capacity);

        if ($stmt->execute()) {
            $kennel_message = "<p style='color: green; padding: 10px; background: #e6ffe6; border-radius: 4px;'>✅ Kennel created successfully!</p>";
        } else {
            $kennel_message = "<p style='color: red; padding: 10px; background: #ffe6e6; border-radius: 4px;'>❌ Error creating kennel: " . $stmt->error . "</p>";
        }
    }
}

// Handle kennel allocation/deallocation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $Animal_ID = (int) $_POST['Animal_ID'];
    $Kennel_ID = isset($_POST['Kennel_ID']) ? (int) $_POST['Kennel_ID'] : 0;
    $action = $_POST['action']; // 'allocate' or 'deallocate'

    try {
        $conn->begin_transaction();

        if ($action === 'allocate') {
            // Validate that a kennel was selected
            if ($Kennel_ID <= 0) {
                throw new Exception("❌ Please select a kennel to allocate to.");
            }

            // Get animal details (size and species)
            $animal_sql = "SELECT Animal_Size, Animal_Species, Kennel_ID FROM animal WHERE Animal_ID = ?";
            $animal_stmt = $conn->prepare($animal_sql);
            $animal_stmt->bind_param("i", $Animal_ID);
            $animal_stmt->execute();
            $animal_result = $animal_stmt->get_result();

            if ($animal_result->num_rows === 0) {
                throw new Exception("❌ Animal not found.");
            }

            $animal = $animal_result->fetch_assoc();
            $animal_size = $animal['Animal_Size'];
            $animal_species = $animal['Animal_Species'];
            $current_kennel_id = $animal['Kennel_ID'];

            // Check if kennel exists and has available space
            $check_sql = "SELECT Availability, Capacity, Size, Animal_Type FROM kennel WHERE Kennel_ID = ?";
            $check_stmt = $conn->prepare($check_sql);
            if (!$check_stmt) {
                throw new Exception("SQL prepare failed: " . $conn->error);
            }
            $check_stmt->bind_param("i", $Kennel_ID);
            $check_stmt->execute();
            $kennel_result = $check_stmt->get_result();
            
            if ($kennel_result->num_rows === 0) {
                throw new Exception("❌ Kennel #$Kennel_ID not found.");
            }
            
            $kennel = $kennel_result->fetch_assoc();

            // Check if kennel has available space
            if ($kennel['Availability'] <= 0) {
                throw new Exception("❌ Kennel #$Kennel_ID is full. Cannot allocate.");
            }

            // Check if kennel size matches animal size
            if ($kennel['Size'] !== $animal_size) {
                throw new Exception("❌ Size mismatch! Animal is $animal_size but kennel is {$kennel['Size']}.");
            }

            // Check if animal species is compatible with kennel type
            if ($kennel['Animal_Type'] !== 'Mixed' && $kennel['Animal_Type'] !== $animal_species) {
                throw new Exception("❌ Species mismatch! Animal is $animal_species but kennel only accepts {$kennel['Animal_Type']}.");
            }

            // Deallocate from current kennel first if animal is already in a kennel
            if ($current_kennel_id) {
                $dealloc_sql = "UPDATE kennel SET Availability = Availability + 1 WHERE Kennel_ID = ?";
                $dealloc_stmt = $conn->prepare($dealloc_sql);
                if (!$dealloc_stmt) {
                    throw new Exception("SQL prepare failed: " . $conn->error);
                }
                $dealloc_stmt->bind_param("i", $current_kennel_id);
                $dealloc_stmt->execute();
            }

            // Allocate to new kennel
            $alloc_sql = "UPDATE kennel SET Availability = Availability - 1 WHERE Kennel_ID = ?";
            $alloc_stmt = $conn->prepare($alloc_sql);
            if (!$alloc_stmt) {
                throw new Exception("SQL prepare failed: " . $conn->error);
            }
            $alloc_stmt->bind_param("i", $Kennel_ID);
            $alloc_stmt->execute();

            // Update animal record
            $animal_sql = "UPDATE animal SET Kennel_ID = ? WHERE Animal_ID = ?";
            $animal_stmt = $conn->prepare($animal_sql);
            if (!$animal_stmt) {
                throw new Exception("SQL prepare failed: " . $conn->error);
            }
            $animal_stmt->bind_param("ii", $Kennel_ID, $Animal_ID);
            $animal_stmt->execute();

            $message = "✅ Animal #$Animal_ID ($animal_size $animal_species) allocated to Kennel #$Kennel_ID ({$kennel['Size']} {$kennel['Animal_Type']}) successfully!";

        } elseif ($action === 'deallocate') {
            // Get current kennel
            $current_sql = "SELECT Kennel_ID FROM animal WHERE Animal_ID = ?";
            $current_stmt = $conn->prepare($current_sql);
            if (!$current_stmt) {
                throw new Exception("SQL prepare failed: " . $conn->error);
            }
            $current_stmt->bind_param("i", $Animal_ID);
            $current_stmt->execute();
            $current_result = $current_stmt->get_result();
            
            if ($current_result->num_rows === 0) {
                throw new Exception("❌ Animal not found.");
            }
            
            $current = $current_result->fetch_assoc();

            if ($current && $current['Kennel_ID']) {
                // Deallocate from current kennel
                $dealloc_sql = "UPDATE kennel SET Availability = Availability + 1 WHERE Kennel_ID = ?";
                $dealloc_stmt = $conn->prepare($dealloc_sql);
                if (!$dealloc_stmt) {
                    throw new Exception("SQL prepare failed: " . $conn->error);
                }
                $dealloc_stmt->bind_param("i", $current['Kennel_ID']);
                $dealloc_stmt->execute();

                // Remove kennel assignment from animal
                $animal_sql = "UPDATE animal SET Kennel_ID = NULL WHERE Animal_ID = ?";
                $animal_stmt = $conn->prepare($animal_sql);
                if (!$animal_stmt) {
                    throw new Exception("SQL prepare failed: " . $conn->error);
                }
                $animal_stmt->bind_param("i", $Animal_ID);
                $animal_stmt->execute();

                $message = "✅ Animal #$Animal_ID deallocated from Kennel #{$current['Kennel_ID']} successfully!";
            } else {
                $message = "ℹ️ Animal #$Animal_ID is not currently allocated to any kennel.";
            }
        }

        $conn->commit();
        $allocation_message = "<p style='color: green; padding: 10px; background: #e6ffe6; border-radius: 4px;'>$message</p>";

    } catch (Exception $e) {
        $conn->rollback();
        $allocation_message = "<p style='color: red; padding: 10px; background: #ffe6e6; border-radius: 4px;'>❌ Error: " . $e->getMessage() . "</p>";
    }
}

// Get all animals
$animals_sql = "SELECT * FROM animal ORDER BY Animal_Name";
$animals_result = $conn->query($animals_sql);

// Get all kennels with availability
$kennels_sql = "SELECT * FROM kennel ORDER BY Kennel_ID";
$kennels_result = $conn->query($kennels_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../Adopt and Volunteer/adoption.css">
    <title>Kennel Management - Makhanda SPCA</title>
    <style>
        /* Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: #f9f9f9;
            flex-direction: column;
            padding-top: 80px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 20px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 1.6rem;
            color: #444;
        }

        /* Notification */
        .notification {
            text-align: center;
            font-size: 1.1rem;
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 6px;
        }

        .success {
            color: green;
            background: #f0fdf4;
        }

        .error {
            color: red;
            background: #fef2f2;
        }

        /* Form styling */
        .scheduling-section {
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        .scheduling-section h3 {
            color: #444;
            margin-bottom: 15px;
        }

        .scheduling-section label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .scheduling-section input,
        .scheduling-section select {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .scheduling-section button {
            padding: 10px 20px;
            background: linear-gradient(45deg, #10b981, #34d399);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .scheduling-section button:hover {
            background: linear-gradient(45deg, #0d966b, #2aa67a);
        }

        .btn-deallocate {
            background: linear-gradient(45deg, #ef4444, #f87171);
        }

        .btn-deallocate:hover {
            background: white;
            color: #ef4444;
            border: 2px solid #ef4444;
        }

        /* Table styling */
        .kennel-table {
            width: 95%;
            max-width: 1400px;
            margin: 20px auto;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .kennel-table th {
            background: rgba(5, 111, 101, 0.7);
            color: white;
            font-size: 15px;
            font-weight: 600;
            padding: 15px;
            text-align: left;
        }

        .kennel-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
            vertical-align: middle;
        }

        .kennel-table tr:hover {
            background-color: #f3f4f6;
        }

        .status-available {
            background-color: #d1fae5;
            color: #059669;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-full {
            background-color: #fee2e2;
            color: #dc2626;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        /* Navigation Bar */
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

        /* Logo container */
        .nav-logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Logo image */
        .navbar .logo img {
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
            list-style: none;
            display: flex;
            gap: 35px;
            margin: 0;
            padding: 0;
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
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .nav-links ul li a:hover {
            background-color: transparent;
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

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 10px;
            }

            .nav-links ul {
                gap: 15px;
            }

            nav {
                flex-direction: column;
                align-items: flex-start;
                padding: 10px 20px;
            }

            .nav-logo {
                margin-bottom: 10px;
            }
        }

        .size-match {
            border: 2px solid #10b981;
        }

        .size-mismatch {
            border: 2px solid #ef4444;
        }
        
        .species-match {
            background-color: #e6ffe6;
        }
        
        .species-mismatch {
            background-color: #ffe6e6;
        }
    </style>
</head>

<body>
    <nav>
        <div class="nav-logo">
            <a href="../frontPage.html"><img src="../Adopt and Volunteer/Paw prints logo.png" alt="Makhanda SPCA Logo"></a>
            <h2>Paw Prints</h2>
        </div>
        <div class="nav-links">
            <ul>
                <li><a href="../Cruelty Reports/admin_dashboard.php">Dashboard</a></li>
                <li><a href="animal_intakeSite.php">Animal Intake</a></li>
                <li><a href="display_animals.php">Animal Records</a></li>
                <li><a href="edit_kennels.php">Edit Kennel</a></li>
                <li><a href="allocate_kennel.php">Allocate Kennel</a></li>
                <li><a href="../Adopt and Volunteer/adoption.php">Adoption</a></li>
                <li><a href="../Adopt and Volunteer/volunteer.php">Volunteer</a></li>
                <li><a href="../Cruelty Reports/Viewallreports.php">Cruelty Reports</a></li>
            </ul>
        </div>
    </nav>

    <div class="main-content">
        <div class="header">
            <h1>Kennel Management</h1>
        </div>

        <?php
        if (isset($allocation_message)) {
            echo $allocation_message;
        }
        if (isset($kennel_message)) {
            echo $kennel_message;
        }
        ?>

        <!-- Create Kennel Form -->
        <div class="scheduling-section">
            <h3>Create New Kennel</h3>
            <form method="post" action="">
                <label for="Kennel_ID">Kennel ID:</label>
                <input type="number" id="Kennel_ID" name="Kennel_ID" required>

                <label for="Kennel_Name">Kennel Name:</label>
                <input type="text" id="Kennel_Name" name="Kennel_Name" required>

                <label for="Capacity">Capacity:</label>
                <input type="number" id="Capacity" name="Capacity" required min="1">

                <label for="Size">Size:</label>
                <select id="Size" name="Size" required>
                    <option value="Small">Small</option>
                    <option value="Medium">Medium</option>
                    <option value="Large">Large</option>
                </select>

                <label for="Animal_Type">Animal Type:</label>
                <select id="Animal_Type" name="Animal_Type" required>
                    <option value="Dog">Dog</option>
                    <option value="Cat">Cat</option>
                    <option value="Mixed">Mixed</option>
                </select>

                <button type="submit" name="create_kennel">Create Kennel</button>
            </form>
        </div>

        <!-- Allocate/Deallocate Animal Form -->
        <div class="scheduling-section">
            <h3>Allocate/Deallocate Animal</h3>
            <form method="post" action="">
                <label for="Animal_ID">Select Animal:</label>
                <select id="Animal_ID" name="Animal_ID" required>
                    <option value="">-- Select Animal --</option>
                    <?php
                    if ($animals_result && $animals_result->num_rows > 0) {
                        while ($animal = $animals_result->fetch_assoc()) {
                            echo "<option value='{$animal['Animal_ID']}'>#{$animal['Animal_ID']} - {$animal['Animal_Name']} ({$animal['Animal_Species']}, {$animal['Animal_Size']})</option>";
                        }
                    } else {
                        echo "<option value=''>No animals available</option>";
                    }
                    ?>
                </select>

                <label for="Kennel_ID">Select Kennel:</label>
                <select id="Kennel_ID" name="Kennel_ID">
                    <option value="">-- Select Kennel --</option>
                    <?php
                    // Reset pointer for kennels result
                    if ($kennels_result) {
                        $kennels_result->data_seek(0);
                        if ($kennels_result->num_rows > 0) {
                            while ($kennel = $kennels_result->fetch_assoc()) {
                                $available = $kennel['Availability'];
                                $capacity = $kennel['Capacity'];
                                $status = $available > 0 ? "Available ($available/$capacity)" : "Full";
                                echo "<option value='{$kennel['Kennel_ID']}'>#{$kennel['Kennel_ID']} - {$kennel['Kennel_Name']} ({$kennel['Size']} {$kennel['Animal_Type']}) - $status</option>";
                            }
                        } else {
                            echo "<option value=''>No kennels available</option>";
                        }
                    }
                    ?>
                </select>

                <button type="submit" name="action" value="allocate">Allocate Animal</button>
                <button type="submit" name="action" value="deallocate" class="btn-deallocate">Deallocate Animal</button>
            </form>
        </div>

        <!-- Kennels Table -->
        <h2 style="margin: 20px 0 10px 2.5%;">Kennel Status</h2>
        <table class="kennel-table">
            <thead>
                <tr>
                    <th>Kennel ID</th>
                    <th>Kennel Name</th>
                    <th>Size</th>
                    <th>Animal Type</th>
                    <th>Capacity</th>
                    <th>Availability</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Reset pointer for kennels result
                if ($kennels_result) {
                    $kennels_result->data_seek(0);
                    if ($kennels_result->num_rows > 0) {
                        while ($kennel = $kennels_result->fetch_assoc()) {
                            $available = $kennel['Availability'];
                            $capacity = $kennel['Capacity'];
                            $status = $available > 0 ? "<span class='status-available'>Available</span>" : "<span class='status-full'>Full</span>";
                            echo "<tr>
                                <td>{$kennel['Kennel_ID']}</td>
                                <td>{$kennel['Kennel_Name']}</td>
                                <td>{$kennel['Size']}</td>
                                <td>{$kennel['Animal_Type']}</td>
                                <td>{$capacity}</td>
                                <td>{$available}</td>
                                <td>{$status}</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align: center;'>No kennels found</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' style='text-align: center;'>Error retrieving kennel data</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Animals Table -->
        <h2 style="margin: 20px 0 10px 2.5%;">Animal Status</h2>
        <table class="kennel-table">
            <thead>
                <tr>
                    <th>Animal ID</th>
                    <th>Name</th>
                    <th>Species</th>
                    <th>Size</th>
                    <th>Current Kennel</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Reset pointer for animals result
                if ($animals_result) {
                    $animals_result->data_seek(0);
                    if ($animals_result->num_rows > 0) {
                        while ($animal = $animals_result->fetch_assoc()) {
                            $kennel_id = $animal['Kennel_ID'] ? $animal['Kennel_ID'] : "Not allocated";
                            $status = $animal['Kennel_ID'] ? "<span class='status-available'>Allocated</span>" : "<span class='status-full'>Not allocated</span>";
                            echo "<tr>
                                <td>{$animal['Animal_ID']}</td>
                                <td>{$animal['Animal_Name']}</td>
                                <td>{$animal['Animal_Species']}</td>
                                <td>{$animal['Animal_Size']}</td>
                                <td>{$kennel_id}</td>
                                <td>{$status}</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align: center;'>No animals found</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align: center;'>Error retrieving animal data</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script>
        // Function to highlight compatible kennels when an animal is selected
        document.getElementById('Animal_ID').addEventListener('change', function() {
            const animalId = this.value;
            if (!animalId) return;

            // Fetch animal details via AJAX
            fetch('get_animal_details.php?animal_id=' + animalId)
                .then(response => response.json())
                .then(animal => {
                    const kennelSelect = document.getElementById('Kennel_ID');
                    for (let i = 0; i < kennelSelect.options.length; i++) {
                        const option = kennelSelect.options[i];
                        if (option.value === '') continue;

                        // Extract kennel details from option text
                        const kennelText = option.text;
                        const sizeMatch = kennelText.match(/\((Small|Medium|Large)/);
                        const speciesMatch = kennelText.match(/(Dog|Cat|Mixed)/);
                        const availabilityMatch = kennelText.match(/Available/);

                        const kennelSize = sizeMatch ? sizeMatch[1] : '';
                        const kennelSpecies = speciesMatch ? speciesMatch[1] : '';
                        const isAvailable = availabilityMatch !== null;

                        // Check if kennel is compatible
                        const sizeCompatible = kennelSize === animal.size;
                        const speciesCompatible = kennelSpecies === 'Mixed' || kennelSpecies === animal.species;

                        // Apply styling based on compatibility
                        if (sizeCompatible && speciesCompatible && isAvailable) {
                            option.className = 'size-match species-match';
                        } else if (!sizeCompatible) {
                            option.className = 'size-mismatch';
                        } else if (!speciesCompatible) {
                            option.className = 'species-mismatch';
                        } else {
                            option.className = '';
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    </script>
</body>

</html>