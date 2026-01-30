<?php
include 'DatabaseConnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start transaction
    $conn->begin_transaction();

    try {
        // --- Auto-generate Animal_ID ---
        $sql = "SELECT MAX(Animal_ID) + 1 as next_id FROM animal";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $Animal_ID = $row['next_id'] ?: 1;

        // --- Collect animal data ---
        $Animal_Name     = $_POST['Animal_Name'];
        $Animal_Species  = $_POST['Animal_Species'];
        $Animal_Breed    = $_POST['Animal_Breed'];
        $Animal_Age      = (int)$_POST['Animal_Age'];
        $Animal_Gender   = $_POST['Animal_Gender'];
        $Animal_Health   = $_POST['Animal_Health']; // NEW: Health status
        $Animal_Date     = $_POST['Animal_Arrival_Date'];
        $Animal_Status   = $_POST['Animal_AdoptionStatus'] ?? 'Available';

        // --- Collect kennel data ---
        $Kennel_ID    = isset($_POST['Kennel_ID']) ? (int)$_POST['Kennel_ID'] : null;
        $Kennel_Name  = $_POST['Kennel_Name'] ?? null;
        $Capacity     = isset($_POST['Capacity']) ? (int)$_POST['Capacity'] : null;
        
        // Fix Size to match ENUM values exactly
        $Size = $_POST['Size'] ?? null;
        $size_mapping = [
            'Small Kennel' => 'Small',
            'Medium Kennel' => 'Medium', 
            'Large Kennel' => 'Large'
        ];
        if (isset($size_mapping[$Size])) {
            $Size = $size_mapping[$Size];
        }
        
        // Animal Type should already match ENUM values
        $Animal_Type  = $_POST['Animal_Type'] ?? null;
        $Availability = isset($_POST['Availability']) ? (int)$_POST['Availability'] : null;

        // --- Validation ---
        if ($Animal_Age <= 0) {
            throw new Exception("Animal_Age must be at least 1.");
        }
        if ($Capacity !== null && $Capacity <= 0) {
            throw new Exception("Capacity must be at least 1.");
        }
        if ($Availability !== null && $Availability < 0) {
            throw new Exception("Availability cannot be negative.");
        }
        
        // Validate ENUM values
        $valid_sizes = ['Small', 'Medium', 'Large'];
        $valid_animal_types = ['Dog', 'Cat', 'Mixed'];
        $valid_health_statuses = ['Excellent', 'Good', 'Fair', 'Poor']; // NEW: Health validation
        
        if ($Size && !in_array($Size, $valid_sizes)) {
            throw new Exception("Invalid Size value. Must be one of: " . implode(', ', $valid_sizes));
        }
        
        if ($Animal_Type && !in_array($Animal_Type, $valid_animal_types)) {
            throw new Exception("Invalid Animal_Type value. Must be one of: " . implode(', ', $valid_animal_types));
        }
        
        // NEW: Validate health status
        if (!in_array($Animal_Health, $valid_health_statuses)) {
            throw new Exception("Invalid Animal_Health value. Must be one of: " . implode(', ', $valid_health_statuses));
        }

        // --- Insert animal ---
        $sqlAnimal = "INSERT INTO animal
            (Animal_ID, Animal_Name, Animal_Species, Animal_Breed, Animal_Age, Animal_Gender, Animal_Health, Animal_Arrival_Date, Animal_AdoptionStatus)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmtA = $conn->prepare($sqlAnimal);
        $stmtA->bind_param(
            "isssissss",
            $Animal_ID,
            $Animal_Name,
            $Animal_Species,
            $Animal_Breed,
            $Animal_Age,
            $Animal_Gender,
            $Animal_Health, // NEW: Health parameter
            $Animal_Date,
            $Animal_Status
        );

        if (!$stmtA->execute()) {
            throw new Exception("Insert animal failed: " . $stmtA->error);
        }

        // --- Insert/update kennel ---
        if ($Kennel_ID !== null) {
            $sqlKennel = "INSERT INTO kennel 
                (Kennel_ID, Kennel_Name, Capacity, Size, Animal_Type, Availability)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    Kennel_Name = VALUES(Kennel_Name),
                    Capacity = VALUES(Capacity),
                    Size = VALUES(Size),
                    Animal_Type = VALUES(Animal_Type),
                    Availability = VALUES(Availability)";
            
            $stmtK = $conn->prepare($sqlKennel);
            $stmtK->bind_param("isissi", $Kennel_ID, $Kennel_Name, $Capacity, $Size, $Animal_Type, $Availability);
            
            if (!$stmtK->execute()) {
                throw new Exception("Insert/Update kennel failed: " . $stmtK->error);
            }
        }

        // --- Handle image upload ---
        if (!empty($_FILES['picture']['name'])) {
            $targetDir = __DIR__ . "/uploads/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            $targetFile = $targetDir . basename($_FILES["picture"]["name"]);
            if (move_uploaded_file($_FILES["picture"]["tmp_name"], $targetFile)) {
                error_log("Image uploaded successfully: " . $targetFile);
            } else {
                error_log("Image upload failed");
            }
        }

        $conn->commit();
        echo "<p>✅ Animal record created successfully with ID: $Animal_ID</p>";
        echo "<a href='animal_intake.HTML'>➕ Add Another Animal</a>";

    } catch (Exception $e) {
        $conn->rollback();
        die("❌ Error: " . $e->getMessage());
    }
}
?>