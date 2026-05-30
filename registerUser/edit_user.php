<?php
include 'DatabaseConnection.php';

// Handle form submission for editing kennel
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $Kennel_ID = (int)$_POST['Kennel_ID'];
    $action = $_POST['action'];

    try {
        $conn->begin_transaction();

        if ($action === 'edit') {
            // Get form values for edit action
            $Kennel_Name = $_POST['Kennel_Name'];
            $Capacity = (int)$_POST['Capacity'];
            $Size = $_POST['Size'];
            $Animal_Type = $_POST['Animal_Type'];
            $Availability = (int)$_POST['Availability'];

            // Validate capacity and availability
            if ($Capacity <= 0) {
                throw new Exception("Capacity must be at least 1.");
            }
            if ($Availability < 0) {
                throw new Exception("Availability cannot be negative.");
            }
            if ($Availability > $Capacity) {
                throw new Exception("Availability cannot exceed capacity.");
            }

            // Validate ENUM values
            $valid_sizes = ['Small', 'Medium', 'Large'];
            $valid_animal_types = ['Dog', 'Cat', 'Mixed'];
            
            if (!in_array($Size, $valid_sizes)) {
                throw new Exception("Invalid Size value. Must be one of: " . implode(', ', $valid_sizes));
            }
            
            if (!in_array($Animal_Type, $valid_animal_types)) {
                throw new Exception("Invalid Animal_Type value. Must be one of: " . implode(', ', $valid_animal_types));
            }

            // Update kennel record
            $sql = "UPDATE kennel SET 
                    Kennel_Name = ?, 
                    Capacity = ?, 
                    Size = ?, 
                    Animal_Type = ?, 
                    Availability = ? 
                    WHERE Kennel_ID = ?";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("SQL prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("sissii", $Kennel_Name, $Capacity, $Size, $Animal_Type, $Availability, $Kennel_ID);
            
            if (!$stmt->execute()) {
                throw new Exception("Update kennel failed: " . $stmt->error);
            }

            $message = " Kennel #$Kennel_ID updated successfully!";

        } elseif ($action === 'delete') {
            // Check if kennel has animals assigned
            $check_sql = "SELECT COUNT(*) as animal_count FROM animal WHERE Kennel_ID = ?";
            $check_stmt = $conn->prepare($check_sql);
            if (!$check_stmt) {
                throw new Exception("SQL prepare failed: " . $conn->error);
            }
            $check_stmt->bind_param("i", $Kennel_ID);
            $check_stmt->execute();
            $result = $check_stmt->get_result()->fetch_assoc();

            if ($result['animal_count'] > 0) {
                throw new Exception(" Cannot delete kennel #$Kennel_ID. It has animals assigned. Please deallocate animals first.");
            }

            // Delete kennel
            $delete_sql = "DELETE FROM kennel WHERE Kennel_ID = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            if (!$delete_stmt) {
                throw new Exception("SQL prepare failed: " . $conn->error);
            }
            $delete_stmt->bind_param("i", $Kennel_ID);
            
            if (!$delete_stmt->execute()) {
                throw new Exception("Delete kennel failed: " . $delete_stmt->error);
            }

            $message = " Kennel #$Kennel_ID deleted successfully!";
        }

        $conn->commit();
        echo "<p style='color: green; padding: 10px; background: #e6ffe6; border-radius: 4px;'>$message</p>";

    } catch (Exception $e) {
        $conn->rollback();
        echo "<p style='color: red; padding: 10px; background: #ffe6e6; border-radius: 4px;'> Error: " . $e->getMessage() . "</p>";
    }
}

// Get all kennels
$kennels_sql = "SELECT * FROM kennel ORDER BY Kennel_ID";
$kennels_result = $conn->query($kennels_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kennels</title>
    <link rel="stylesheet" href="animal_records_theme.css">
    <style>
        /* Additional styles specific to this page */
        body {
            background-color: #e6eff7;
            margin: 0;
            font-family: Arial, sans-serif;
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
        .btn-danger {
            background-color: #f44336;
        }
        .btn-success {
            background-color: #2ecc71;
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
            background: #2ecc71;
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
            color: #2ecc71;
            border: 1px solid #2ecc71;
        }
        .error {
            background: #ffe6e6;
            color: #e74c3c;
            border: 1px solid #e74c3c;
        }
        .edit-form {
            background: #f0f8ff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .radio-group {
            margin: 15px 0;
        }
        .radio-group label {
            display: inline;
            margin-right: 20px;
        }
        .radio-group input[type="radio"] {
            width: auto;
            margin-right: 5px;
        }
        .delete-confirm {
            display: none;
            background: #ffe6e6;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border: 1px solid #f44336;
        }
    </style>
</head>
<body class="animal-records-theme">
    <div class="container">
        <h1> Edit Kennels</h1>
        
        <div class="action-buttons">
            <a href="../animal_intake_system/allocate_kennel.php" class="btn">Allocate Kennels</a>
            <a href="../animal_intake_system/display_animals.php" class="btn">View Animals</a>
            <a href="../animal_intake_system/animal_intakeSite.php" class="btn">Add Animal</a>
        </div>

        <div class="section">
            <h2>🔧 Edit Kennel Information</h2>
            <form method="POST" id="kennelForm">
                <div class="form-group">
                    <label for="Kennel_ID">Select Kennel:</label>
                    <select id="Kennel_ID" name="Kennel_ID" required>
                        <option value="">-- Select Kennel --</option>
                        <?php 
                        if ($kennels_result && $kennels_result->num_rows > 0) {
                            while ($kennel = $kennels_result->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $kennel['Kennel_ID']; ?>">
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

                <div class="radio-group">
                    <label>Action:</label>
                    <input type="radio" id="action_edit" name="action" value="edit" checked>
                    <label for="action_edit" style="display: inline;">Update Kennel</label>
                    
                    <input type="radio" id="action_delete" name="action" value="delete" style="margin-left: 20px;">
                    <label for="action_delete" style="display: inline;">Delete Kennel</label>
                </div>

                <div class="edit-form" id="kennelEditForm">
                    <div class="form-group">
                        <label for="Kennel_Name">Kennel Name:</label>
                        <input type="text" id="Kennel_Name" name="Kennel_Name" required>
                    </div>

                    <div class="form-group">
                        <label for="Capacity">Capacity:</label>
                        <input type="number" id="Capacity" name="Capacity" min="1" required>
                    </div>

                    <div class="form-group">
                        <label for="Size">Size:</label>
                        <select id="Size" name="Size" required>
                            <option value="Small">Small</option>
                            <option value="Medium">Medium</option>
                            <option value="Large">Large</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="Animal_Type">Animal Type:</label>
                        <select id="Animal_Type" name="Animal_Type" required>
                            <option value="Dog">Dog</option>
                            <option value="Cat">Cat</option>
                            <option value="Mixed">Mixed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="Availability">Availability:</label>
                        <input type="number" id="Availability" name="Availability" min="0" required>
                    </div>
                </div>

                <div class="delete-confirm" id="deleteConfirm">
                    <p><strong>⚠️ Warning:</strong> You are about to delete this kennel. This action cannot be undone.</p>
                    <p>Are you sure you want to delete <span id="kennelToDelete"></span>?</p>
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn" id="submitButton">🚀 Update Kennel</button>
                </div>
            </form>
        </div>

        <div class="section">
            <h2>📊 All Kennels</h2>
            <div class="kennel-grid">
                <?php 
                // Re-fetch kennels to display current data
                $kennels_result = $conn->query("SELECT * FROM kennel ORDER BY Kennel_ID");
                
                if ($kennels_result && $kennels_result->num_rows > 0) {
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
                            <div style="background: #4CAF50; height: 100%; width: <?php echo $percentage; ?>%; border-radius: 10px;"></div>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const kennelSelect = document.getElementById('Kennel_ID');
            const editForm = document.getElementById('kennelEditForm');
            const deleteConfirm = document.getElementById('deleteConfirm');
            const kennelNameInput = document.getElementById('Kennel_Name');
            const capacityInput = document.getElementById('Capacity');
            const sizeSelect = document.getElementById('Size');
            const animalTypeSelect = document.getElementById('Animal_Type');
            const availabilityInput = document.getElementById('Availability');
            const actionEdit = document.getElementById('action_edit');
            const actionDelete = document.getElementById('action_delete');
            const submitButton = document.getElementById('submitButton');
            const kennelToDelete = document.getElementById('kennelToDelete');
            const form = document.getElementById('kennelForm');

            // Show/hide forms based on action selection
            function toggleForms() {
                if (actionEdit.checked) {
                    editForm.style.display = 'block';
                    deleteConfirm.style.display = 'none';
                    submitButton.textContent = ' Update Kennel';
                    submitButton.className = 'btn';
                } else {
                    editForm.style.display = 'none';
                    deleteConfirm.style.display = 'block';
                    submitButton.textContent = ' Delete Kennel';
                    submitButton.className = 'btn btn-danger';
                    
                    // Update delete confirmation message
                    const selectedOption = kennelSelect.options[kennelSelect.selectedIndex];
                    kennelToDelete.textContent = selectedOption.text;
                }
            }

            // Initialize form state
            toggleForms();

            // Listen for action changes
            actionEdit.addEventListener('change', toggleForms);
            actionDelete.addEventListener('change', toggleForms);

            // Show/hide edit form based on selection
            kennelSelect.addEventListener('change', function() {
                if (this.value) {
                    // Fetch kennel data and populate form
                    fetchKennelData(this.value);
                    toggleForms();
                }
            });

            function fetchKennelData(kennelId) {
                // Create a FormData object to send the kennel ID
                const formData = new FormData();
                formData.append('kennel_id', kennelId);
                
                // Send AJAX request to fetch kennel data
                fetch('get_kennel_data.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.statusText);
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Failed to parse JSON:', text);
                            throw new Error('Invalid JSON response from server');
                        }
                    });
                })
                .then(data => {
                    if (data.success) {
                        // Populate the form fields with the retrieved data
                        kennelNameInput.value = data.kennel.Kennel_Name || '';
                        capacityInput.value = data.kennel.Capacity || '';
                        sizeSelect.value = data.kennel.Size || 'Small';
                        animalTypeSelect.value = data.kennel.Animal_Type || '';
                        availabilityInput.value = data.kennel.Availability || '';
                    } else {
                        alert('Kennel data not found. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Error fetching kennel data: ' + error.message);
                });
            }

            // Handle form submission
            form.addEventListener('submit', function(e) {
                if (actionDelete.checked) {
                    const confirmation = confirm('Are you sure you want to delete this kennel? This action cannot be undone.');
                    if (!confirmation) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
</body>
</html>