<?php
include 'DatabaseConnection.php';

// Add health and size columns if they don't exist
$alterTable = "ALTER TABLE animal 
               ADD COLUMN IF NOT EXISTS Animal_Health VARCHAR(255) DEFAULT 'Good' AFTER Animal_AdoptionStatus,
               ADD COLUMN IF NOT EXISTS Animal_Size ENUM('Small', 'Medium', 'Large') NULL AFTER Animal_Gender";
$conn->query($alterTable);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $Animal_ID = (int)$_POST['Animal_ID'];
    $Animal_Name = $_POST['Animal_Name'];
    $Animal_Species = $_POST['Animal_Species'];
    $Animal_Breed = $_POST['Animal_Breed'];
    $Animal_Age = (int)$_POST['Animal_Age'];
    $Animal_Gender = $_POST['Animal_Gender'];
    $Animal_Size = $_POST['Animal_Size'];
    $Animal_Arrival_Date = $_POST['Animal_Arrival_Date'];
    $Animal_AdoptionStatus = $_POST['Animal_AdoptionStatus'];
    $Animal_Health = $_POST['Animal_Health'];
    $Kennel_ID = isset($_POST['Kennel_ID']) && $_POST['Kennel_ID'] !== '' ? (int)$_POST['Kennel_ID'] : null;

    // Validate inputs
    if ($Animal_Age <= 0) {
        die("❌ Animal age must be at least 1 year.");
    }

    // Validate size
    $valid_sizes = ['Small', 'Medium', 'Large'];
    if (!in_array($Animal_Size, $valid_sizes)) {
        die("❌ Invalid animal size. Please select Small, Medium, or Large.");
    }

    // Check if Kennel_ID is NULL and handle accordingly
    if ($Kennel_ID === null) {
        $sql = "UPDATE animal SET 
                Animal_Name = ?, 
                Animal_Species = ?, 
                Animal_Breed = ?, 
                Animal_Age = ?, 
                Animal_Gender = ?, 
                Animal_Size = ?, 
                Animal_Arrival_Date = ?, 
                Animal_AdoptionStatus = ?, 
                Animal_Health = ?,
                Kennel_ID = NULL
                WHERE Animal_ID = ?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("❌ SQL prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sssisssssi", 
            $Animal_Name, 
            $Animal_Species, 
            $Animal_Breed, 
            $Animal_Age, 
            $Animal_Gender, 
            $Animal_Size,
            $Animal_Arrival_Date, 
            $Animal_AdoptionStatus, 
            $Animal_Health,
            $Animal_ID
        );
    } else {
        $sql = "UPDATE animal SET 
                Animal_Name = ?, 
                Animal_Species = ?, 
                Animal_Breed = ?, 
                Animal_Age = ?, 
                Animal_Gender = ?, 
                Animal_Size = ?, 
                Animal_Arrival_Date = ?, 
                Animal_AdoptionStatus = ?, 
                Animal_Health = ?,
                Kennel_ID = ?
                WHERE Animal_ID = ?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("❌ SQL prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sssisssssii", 
            $Animal_Name, 
            $Animal_Species, 
            $Animal_Breed, 
            $Animal_Age, 
            $Animal_Gender, 
            $Animal_Size,
            $Animal_Arrival_Date, 
            $Animal_AdoptionStatus, 
            $Animal_Health,
            $Kennel_ID,
            $Animal_ID
        );
    }
    
    if ($stmt->execute()) {
        echo "<p style='color: #2ecc71; text-align: center;'>✅ Animal updated successfully!</p>";
    } else {
        echo "<p style='color: #e74c3c; text-align: center;'>❌ Error updating animal: " . $stmt->error . "</p>";
    }
}

// Get animal data for editing
$animal = null;
if (isset($_GET['id'])) {
    $Animal_ID = (int)$_GET['id'];
    $sql = "SELECT * FROM animal WHERE Animal_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $Animal_ID);
    $stmt->execute();
    $result = $stmt->get_result();
    $animal = $result->fetch_assoc();
}

// Get available kennels
$kennels_sql = "SELECT * FROM kennel WHERE Availability > 0 ORDER BY Kennel_ID";
$kennels_result = $conn->query($kennels_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Animal</title>
    <link rel="stylesheet" href="animal_records_theme.css">
    <style>
        /* Additional styles for the form */
        .container {
            width: 90%;
            max-width: 1000px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 2px 5px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin: 15px 0;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            margin: 10px 5px;
            display: inline-block;
        }
        
        .btn:hover {
            background-color: #45a049;
        }
        
        .action-buttons {
            text-align: center;
            margin: 20px 0;
        }
        
        .section-title {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            border-radius: 4px;
            margin: 20px 0 10px 0;
        }
        
        .message {
            text-align: center;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
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
        
        .current-image {
            text-align: center;
            margin: 15px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .current-image img {
            max-width: 250px;
            max-height: 250px;
            border-radius: 8px;
            border: 2px solid #dee2e6;
        }
        
        .image-info {
            font-size: 14px;
            color: #6c757d;
            margin-top: 10px;
        }
    </style>
</head>
<body class="animal-records-theme">
    <a href="display_animals.php" class="back-btn">← Back to Animals</a>
    
    <div class="container">
        <h2>✏️ Update Animal Details</h2>
        
        <?php if ($animal): ?>
        <form method="POST">
            <input type="hidden" name="Animal_ID" value="<?php echo $animal['Animal_ID']; ?>">

            <div class="section-title">🐾 Basic Information</div>
            
            <div class="form-group">
                <label for="Animal_Name">Animal Name:</label>
                <input type="text" id="Animal_Name" name="Animal_Name" value="<?php echo htmlspecialchars($animal['Animal_Name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="Animal_Species">Species:</label>
                <select id="Animal_Species" name="Animal_Species" required>
                    <option value="Dog" <?php echo $animal['Animal_Species'] == 'Dog' ? 'selected' : ''; ?>>Dog</option>
                    <option value="Cat" <?php echo $animal['Animal_Species'] == 'Cat' ? 'selected' : ''; ?>>Cat</option>
                </select>
            </div>

            <div class="form-group">
                <label for="Animal_Breed">Breed:</label>
                <input type="text" id="Animal_Breed" name="Animal_Breed" value="<?php echo htmlspecialchars($animal['Animal_Breed']); ?>" required>
            </div>

            <div class="form-group">
                <label for="Animal_Age">Age (Years):</label>
                <input type="number" id="Animal_Age" name="Animal_Age" value="<?php echo $animal['Animal_Age']; ?>" min="1" required>
            </div>

            <div class="form-group">
                <label for="Animal_Gender">Gender:</label>
                <select id="Animal_Gender" name="Animal_Gender" required>
                    <option value="Female" <?php echo $animal['Animal_Gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                    <option value="Male" <?php echo $animal['Animal_Gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Unknown" <?php echo $animal['Animal_Gender'] == 'Unknown' ? 'selected' : ''; ?>>Unknown</option>
                </select>
            </div>

            <div class="form-group">
                <label for="Animal_Size">Size:</label>
                <select id="Animal_Size" name="Animal_Size" required>
                    <option value="Small" <?php echo ($animal['Animal_Size'] ?? '') == 'Small' ? 'selected' : ''; ?>>Small</option>
                    <option value="Medium" <?php echo ($animal['Animal_Size'] ?? '') == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="Large" <?php echo ($animal['Animal_Size'] ?? '') == 'Large' ? 'selected' : ''; ?>>Large</option>
                </select>
            </div>

            <div class="form-group">
                <label for="Animal_Arrival_Date">Arrival Date:</label>
                <input type="date" id="Animal_Arrival_Date" name="Animal_Arrival_Date" value="<?php echo $animal['Animal_Arrival_Date']; ?>" required>
            </div>

            <div class="section-title">🖼️ Current Animal Image</div>

            <div class="current-image">
                <?php if (!empty($animal['picture'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($animal['picture']); ?>" 
                         alt="Current image of <?php echo htmlspecialchars($animal['Animal_Name']); ?>"
                         onerror="this.style.display='none'">
                    <p class="image-info">Current animal image (cannot be modified here)</p>
                <?php else: ?>
                    <p>No image available for this animal</p>
                    <p class="image-info">Images can only be added during animal intake</p>
                <?php endif; ?>
            </div>

            <div class="section-title">🏥 Health & Status</div>

            <div class="form-group">
                <label for="Animal_AdoptionStatus">Adoption Status:</label>
                <select id="Animal_AdoptionStatus" name="Animal_AdoptionStatus" required>
                    <option value="Available" <?php echo $animal['Animal_AdoptionStatus'] == 'Available' ? 'selected' : ''; ?>>Available</option>
                    <option value="Adopted" <?php echo $animal['Animal_AdoptionStatus'] == 'Adopted' ? 'selected' : ''; ?>>Adopted</option>
                    <option value="Pending" <?php echo $animal['Animal_AdoptionStatus'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Deceased" <?php echo $animal['Animal_AdoptionStatus'] == 'Deceased' ? 'selected' : ''; ?>>Deceased</option>
                </select>
            </div>

            <div class="form-group">
                <label for="Animal_Health">Health Condition:</label>
                <select id="Animal_Health" name="Animal_Health" required>
                    <option value="Excellent" <?php echo ($animal['Animal_Health'] ?? 'Good') == 'Excellent' ? 'selected' : ''; ?>>Excellent</option>
                    <option value="Good" <?php echo ($animal['Animal_Health'] ?? 'Good') == 'Good' ? 'selected' : ''; ?>>Good</option>
                    <option value="Fair" <?php echo ($animal['Animal_Health'] ?? 'Good') == 'Fair' ? 'selected' : ''; ?>>Fair</option>
                    <option value="Poor" <?php echo ($animal['Animal_Health'] ?? 'Good') == 'Poor' ? 'selected' : ''; ?>>Poor</option>
                    <option value="Critical" <?php echo ($animal['Animal_Health'] ?? 'Good') == 'Critical' ? 'selected' : ''; ?>>Critical</option>
                    <option value="Under Treatment" <?php echo ($animal['Animal_Health'] ?? 'Good') == 'Under Treatment' ? 'selected' : ''; ?>>Under Treatment</option>
                </select>
            </div>

            <div class="section-title">🏠 Kennel Assignment</div>

            <div class="form-group">
                <label for="Kennel_ID">Assign to Kennel:</label>
                <select id="Kennel_ID" name="Kennel_ID">
                    <option value="">-- No Kennel --</option>
                    <?php 
                    if ($kennels_result && $kennels_result->num_rows > 0) {
                        $kennels_result->data_seek(0);
                        while ($kennel = $kennels_result->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $kennel['Kennel_ID']; ?>" 
                            <?php echo (isset($animal['Kennel_ID']) && $animal['Kennel_ID'] == $kennel['Kennel_ID']) ? 'selected' : ''; ?>>
                            Kennel #<?php echo $kennel['Kennel_ID']; ?> - <?php echo $kennel['Kennel_Name']; ?> 
                            (<?php echo $kennel['Availability']; ?> available of <?php echo $kennel['Capacity']; ?>)
                        </option>
                    <?php 
                        endwhile;
                    }
                    ?>
                </select>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn">💾 Update Animal</button>
                <a href="display_animals.php" class="btn">← Back to Animals</a>
            </div>
        </form>
        <?php else: ?>
            <div class="message error">
                <p>❌ Animal not found. Please select an animal from the <a href="display_animals.php">animals list</a>.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>