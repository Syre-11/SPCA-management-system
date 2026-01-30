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
        $Animal_Health   = $_POST['Animal_Health'];
        $Animal_Date     = $_POST['Animal_Arrival_Date'];
        $Animal_Status   = $_POST['Animal_AdoptionStatus'] ?? 'Available';

        // --- Determine kennel assignment ---
        $kennel_option = $_POST['kennel_option'];
        
        if ($kennel_option === 'existing') {
            // Use existing kennel
            $Kennel_ID = (int)$_POST['Existing_Kennel_ID'];
            
            // Validate that the kennel exists and has availability
            $check_sql = "SELECT Availability FROM kennel WHERE Kennel_ID = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $Kennel_ID);
            $check_stmt->execute();
            $kennel = $check_stmt->get_result()->fetch_assoc();
            
            if (!$kennel) {
                throw new Exception("Selected kennel does not exist.");
            }
            
            if ($kennel['Availability'] <= 0) {
                throw new Exception("Selected kennel is full. Please choose another kennel or create a new one.");
            }
            
            // Update kennel availability
            $update_sql = "UPDATE kennel SET Availability = Availability - 1 WHERE Kennel_ID = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $Kennel_ID);
            $update_stmt->execute();
            
        } else {
            // Create new kennel
            $Kennel_ID    = (int)$_POST['Kennel_ID'];
            $Kennel_Name  = $_POST['Kennel_Name'];
            $Capacity     = (int)$_POST['Capacity'];
            $Size         = $_POST['Size'];
            $Animal_Type  = $_POST['Animal_Type'];
            $Availability = (int)$_POST['Availability'] - 1; // Subtract 1 for the current animal
            
            // Validate new kennel data
            if ($Capacity <= 0) {
                throw new Exception("Capacity must be at least 1.");
            }
            if ($Availability < 0) {
                throw new Exception("Availability cannot be negative.");
            }
            
            // Validate ENUM values
            $valid_sizes = ['Small', 'Medium', 'Large'];
            $valid_animal_types = ['Dog', 'Cat', 'Mixed'];
            
            if (!in_array($Size, $valid_sizes)) {
                throw new Exception("Invalid Size value.");
            }
            
            if (!in_array($Animal_Type, $valid_animal_types)) {
                throw new Exception("Invalid Animal_Type value.");
            }
            
            // Insert new kennel
            $kennel_sql = "INSERT INTO kennel 
                (Kennel_ID, Kennel_Name, Capacity, Size, Animal_Type, Availability)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    Kennel_Name = VALUES(Kennel_Name),
                    Capacity = VALUES(Capacity),
                    Size = VALUES(Size),
                    Animal_Type = VALUES(Animal_Type),
                    Availability = VALUES(Availability)";
            
            $kennel_stmt = $conn->prepare($kennel_sql);
            $kennel_stmt->bind_param("isissi", $Kennel_ID, $Kennel_Name, $Capacity, $Size, $Animal_Type, $Availability);
            
            if (!$kennel_stmt->execute()) {
                throw new Exception("Insert/Update kennel failed: " . $kennel_stmt->error);
            }
        }

        // --- Insert animal with Kennel_ID ---
        $sqlAnimal = "INSERT INTO animal
            (Animal_ID, Animal_Name, Animal_Species, Animal_Breed, Animal_Age, Animal_Gender, Animal_Health, Animal_Arrival_Date, Animal_AdoptionStatus, Kennel_ID)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmtA = $conn->prepare($sqlAnimal);
        $stmtA->bind_param(
            "isssissssi",
            $Animal_ID,
            $Animal_Name,
            $Animal_Species,
            $Animal_Breed,
            $Animal_Age,
            $Animal_Gender,
            $Animal_Health,
            $Animal_Date,
            $Animal_Status,
            $Kennel_ID
        );

        if (!$stmtA->execute()) {
            throw new Exception("Insert animal failed: " . $stmtA->error);
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