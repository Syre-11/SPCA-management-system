<?php
include 'DatabaseConnection.php';

// First, ensure the animal table has the Kennel_ID column
$alterAnimalTable = "ALTER TABLE animal 
               ADD COLUMN IF NOT EXISTS Kennel_ID INT NULL AFTER Animal_Image";
$conn->query($alterAnimalTable);

// Handle kennel allocation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $Animal_ID = (int)$_POST['Animal_ID'];
    $Kennel_ID = (int)$_POST['Kennel_ID'];
    $action = $_POST['action']; // 'allocate' or 'deallocate'

    try {
        $conn->begin_transaction();

        if ($action === 'allocate') {
            // Check if kennel has available space
            $check_sql = "SELECT Availability, Capacity FROM kennel WHERE Kennel_ID = ?";
            $check_stmt = $conn->prepare($check_sql);
            if (!$check_stmt) {
                throw new Exception("SQL prepare failed: " . $conn->error);
            }
            $check_stmt->bind_param("i", $Kennel_ID);
            $check_stmt->execute();
            $kennel = $check_stmt->get_result()->fetch_assoc();

            if (!$kennel) {
                throw new Exception("❌ Kennel #$Kennel_ID not found.");
            }

            if ($kennel['Availability'] <= 0) {
                throw new Exception("❌ Kennel #$Kennel_ID is full. Cannot allocate.");
            }

            // Get current kennel of animal (if any)
            $current_sql = "SELECT Kennel_ID FROM animal WHERE Animal_ID = ?";
            $current_stmt = $conn->prepare($current_sql);
            if (!$current_stmt) {
                throw new Exception("SQL prepare failed: " . $conn->error);
            }
            $current_stmt->bind_param("i", $Animal_ID);
            $current_stmt->execute();
            $current = $current_stmt->get_result()->fetch_assoc();

            // Deallocate from current kennel first
            if ($current && $current['Kennel_ID']) {
                $dealloc_sql = "UPDATE kennel SET Availability = Availability + 1 WHERE Kennel_ID = ?";
                $dealloc_stmt = $conn->prepare($dealloc_sql);
                if (!$dealloc_stmt) {
                    throw new Exception("SQL prepare failed: " . $conn->error);
                }
                $dealloc_stmt->bind_param("i", $current['Kennel_ID']);
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

            $message = "✅ Animal #$Animal_ID allocated to Kennel #$Kennel_ID successfully!";

        } elseif ($action === 'deallocate') {
            // Get current kennel
            $current_sql = "SELECT Kennel_ID FROM animal WHERE Animal_ID = ?";
            $current_stmt = $conn->prepare($current_sql);
            if (!$current_stmt) {
                throw new Exception("SQL prepare failed: " . $conn->error);
            }
            $current_stmt->bind_param("i", $Animal_ID);
            $current_stmt->execute();
            $current = $current_stmt->get_result()->fetch_assoc();

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
        echo "<p style='color: green; padding: 10px; background: #e6ffe6; border-radius: 4px;'>$message</p>";

    } catch (Exception $e) {
        $conn->rollback();
        echo "<p style='color: red; padding: 10px; background: #ffe6e6; border-radius: 4px;'>❌ Error: " . $e->getMessage() . "</p>";
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
    <title>Allocate Kennel</title>
    <style>
        body {
            background-color: #e6eff7;
            margin: 0;
            font-family: Nunito, sans-serif;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #4a90e2;
            text-align: center;
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
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            background-color: #4a90e2;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            margin: 10px 5px;
        }
        .btn-danger {
            background-color: #e74c3c;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .action-buttons {
            text-align: center;
            margin: 20px 0;
        }
        .section {
            margin: 30px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .kennel-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .kennel-card {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            background: #f9f9f9;
        }
        .kennel-card.full {
            background: #ffe6e6;
        }
        .kennel-card.available {
            background: #e6ffe6;
        }
        .status-indicator {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 10px;
        }
        .status-available {
            background: #27ae60;
            color: white;
        }
        .status-full {
            background: #e74c3c;
            color: white;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            text-align: center;
        }
        .success {
            background: #e6ffe6;
            color: #27ae60;
            border: 1px solid #27ae60;
        }
        .error {
            background: #ffe6e6;
            color: #e74c3c;
            border: 1px solid #e74c3c;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏠 Allocate/Deallocate Kennels</h1>
        
        <div class="action-buttons">
            <a href="display_animals.php" class="btn">👀 View Animals</a>
            <a href="edit_kennels.php" class="btn">✏️ Edit Kennels</a>
            <a href="animal_intake.HTML" class="btn">➕ Add Animal</a>
        </div>

        <div class="section">
            <h2>🔧 Kennel Allocation</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="Animal_ID">Select Animal:</label>
                    <select id="Animal_ID" name="Animal_ID" required>
                        <option value="">-- Select Animal --</option>
                        <?php 
                        if ($animals_result && $animals_result->num_rows > 0) {
                            while ($animal = $animals_result->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $animal['Animal_ID']; ?>">
                                #<?php echo $animal['Animal_ID']; ?> - <?php echo $animal['Animal_Name']; ?> 
                                (<?php echo $animal['Animal_Species']; ?>)
                                <?php if ($animal['Kennel_ID']): ?>
                                    - Currently in Kennel #<?php echo $animal['Kennel_ID']; ?>
                                <?php endif; ?>
                            </option>
                        <?php 
                            endwhile;
                        } else {
                            echo "<option value=''>No animals found</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="Kennel_ID">Select Kennel (for allocation):</label>
                    <select id="Kennel_ID" name="Kennel_ID">
                        <option value="">-- Select Kennel --</option>
                        <?php 
                        if ($kennels_result && $kennels_result->num_rows > 0) {
                            $kennels_result->data_seek(0);
                            while ($kennel = $kennels_result->fetch_assoc()): 
                                $status_class = $kennel['Availability'] > 0 ? 'available' : 'full';
                        ?>
                            <option value="<?php echo $kennel['Kennel_ID']; ?>" 
                                <?php echo $kennel['Availability'] <= 0 ? 'disabled' : ''; ?>>
                                Kennel #<?php echo $kennel['Kennel_ID']; ?>: <?php echo $kennel['Kennel_Name']; ?>
                                - <?php echo $kennel['Availability']; ?>/<?php echo $kennel['Capacity']; ?> available
                                (<?php echo $kennel['Size']; ?> - <?php echo $kennel['Animal_Type']; ?>)
                            </option>
                        <?php 
                            endwhile;
                        } else {
                            echo "<option value=''>No kennels found</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Action:</label>
                    <div>
                        <input type="radio" id="action_allocate" name="action" value="allocate" checked>
                        <label for="action_allocate" style="display: inline;">Allocate to Kennel</label>
                        
                        <input type="radio" id="action_deallocate" name="action" value="deallocate" style="margin-left: 20px;">
                        <label for="action_deallocate" style="display: inline;">Deallocate from Current Kennel</label>
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn">🚀 Execute Action</button>
                </div>
            </form>
        </div>

        <div class="section">
            <h2>📊 Kennel Occupancy Overview</h2>
            <div class="kennel-grid">
                <?php 
                if ($kennels_result && $kennels_result->num_rows > 0) {
                    $kennels_result->data_seek(0);
                    while ($kennel = $kennels_result->fetch_assoc()): 
                        $occupancy = $kennel['Capacity'] - $kennel['Availability'];
                        $percentage = $kennel['Capacity'] > 0 ? ($occupancy / $kennel['Capacity']) * 100 : 0;
                        $status_class = $kennel['Availability'] > 0 ? 'available' : 'full';
                ?>
                    <div class="kennel-card <?php echo $status_class; ?>">
                        <h3>Kennel #<?php echo $kennel['Kennel_ID']; ?></h3>
                        <p><strong>Name:</strong> <?php echo $kennel['Kennel_Name']; ?></p>
                        <p><strong>Size:</strong> <?php echo $kennel['Size']; ?></p>
                        <p><strong>Type:</strong> <?php echo $kennel['Animal_Type']; ?></p>
                        <p><strong>Capacity:</strong> <?php echo $kennel['Capacity']; ?></p>
                        <p><strong>Available:</strong> <?php echo $kennel['Availability']; ?></p>
                        <p><strong>Occupied:</strong> <?php echo $occupancy; ?></p>
                        <div style="background: #ddd; height: 20px; border-radius: 10px; margin: 10px 0;">
                            <div style="background: #4a90e2; height: 100%; width: <?php echo $percentage; ?>%; border-radius: 10px;"></div>
                        </div>
                        <span class="status-indicator <?php echo $kennel['Availability'] > 0 ? 'status-available' : 'status-full'; ?>">
                            <?php echo $kennel['Availability'] > 0 ? 'AVAILABLE' : 'FULL'; ?>
                        </span>
                    </div>
                <?php 
                    endwhile;
                } else {
                    echo "<p>No kennels found in the database.</p>";
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>