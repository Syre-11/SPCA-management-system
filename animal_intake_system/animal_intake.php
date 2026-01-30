<?php
include 'DatabaseConnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        $Animal_Size     = $_POST['Animal_Size']; // NEW: Animal size
        $Animal_Health   = $_POST['Animal_Health'];
        $Animal_Date     = $_POST['Animal_Arrival_Date'];
        $Animal_Status   = $_POST['Animal_AdoptionStatus'] ?? 'Available';

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
            $Animal_Size, // NEW: Animal size
            $Animal_Health,
            $Animal_Date,
            $Animal_Status,
            $picture_path
        );

        if (!$stmtA->execute()) {
            throw new Exception("Insert animal failed: " . $stmtA->error);
        }

        echo "<p>✅ Animal record created successfully with ID: $Animal_ID</p>";
        echo "<p>Animal Size: $Animal_Size</p>";
        echo "<p>You can now assign this animal to a kennel from the <a href='allocate_kennel.php'>kennel management page</a>.</p>";
        echo "<a href='animal_intakeSite.php'>➕ Add Another Animal</a>";

    } catch (Exception $e) {
        die("❌ Error: " . $e->getMessage());
    }
}
?>