<?php
include 'DatabaseConnection.php';

// Handle form submission for editing or deleting kennel
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $Kennel_ID = (int) $_POST['Kennel_ID'];
    $action = $_POST['action'];

    try {
        $conn->begin_transaction();

        if ($action === 'edit') {
            // Get form values for edit action
            $Kennel_Name = $_POST['Kennel_Name'];
            $Capacity = (int) $_POST['Capacity'];
            $Size = $_POST['Size'];
            $Animal_Type = $_POST['Animal_Type'];
            $Availability = (int) $_POST['Availability'];

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

            $message = "✅ Kennel #$Kennel_ID updated successfully!";
            $message_type = "success";

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
                throw new Exception("❌ Cannot delete kennel #$Kennel_ID. It has animals assigned. Please deallocate animals first.");
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

            $message = "✅ Kennel #$Kennel_ID deleted successfully!";
            $message_type = "success";
        }

        $conn->commit();

    } catch (Exception $e) {
        $conn->rollback();
        $message = "❌ Error: " . $e->getMessage();
        $message_type = "error";
    }
}

// Get all kennels and stats
$kennels_sql = "SELECT * FROM kennel ORDER BY Kennel_ID";
$kennels_result = $conn->query($kennels_sql);

$total_kennels = 0;
$available_kennels = 0;

try {
    $sql_count = "SELECT COUNT(*) as total FROM kennel";
    $result_count = $conn->query($sql_count);
    if ($result_count) {
        $row_count = $result_count->fetch_assoc();
        $total_kennels = $row_count['total'];
    }

    $sql_available = "SELECT COUNT(*) as available FROM kennel WHERE Availability > 0";
    $result_available = $conn->query($sql_available);
    if ($result_available) {
        $row_available = $result_available->fetch_assoc();
        $available_kennels = $row_available['available'];
    }
} catch (Exception $e) {
    error_log("Error fetching kennel stats: " . $e->getMessage());
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
    <title>Edit Kennels</title>
    <style>
        /* Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        nav img {
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

        /* Body */
        body {
            padding-top: 80px;
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            line-height: 1.6;
        }

        /* Kennel Edit Container */
        .kennel-edit-container {
            max-width: 800px;
            margin: 30px auto;
            text-align: left;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        /* Form Wrapper */
        .kennel-edit-form {
            width: 100%;
        }

        /* Headings inside the form */
        .kennel-edit-form h2,
        .kennel-edit-table th {
            text-align: center;
            color: #444;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        /* Labels inside table cells */
        .kennel-edit-table .form-label {
            display: block;
            margin: 10px 0 5px;
            font-weight: 500;
            color: #34495e;
            font-size: 16px;
        }

        /* Table Style */
        .kennel-edit-table {
            width: 100%;
            border-collapse: collapse;
        }

        .kennel-edit-table th {
            background: linear-gradient(45deg, rgba(5, 111, 101, 0.7));
            color: white;
            font-size: 1.2rem;
            padding: 18px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }

        .kennel-edit-table td {
            padding: 15px;
            vertical-align: middle;
        }

        /* Input fields */
        .kennel-edit-table input[type="text"],
        .kennel-edit-table input[type="number"],
        .kennel-edit-table select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .kennel-edit-table input:focus,
        .kennel-edit-table select:focus {
            border-color: #056f65b8;
            outline: none;
            box-shadow: 0 0 5px rgba(5, 111, 101, 0.3);
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

        /* Buttons */
        .kennel-edit-table input[type="submit"],
        .kennel-edit-table input[type="reset"],
        .kennel-edit-button {
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

        .kennel-edit-table input[type="submit"]:hover,
        .kennel-edit-table input[type="reset"]:hover,
        .kennel-edit-button:hover {
            background: #045950;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.12);
        }

        /* Delete Button */
        .kennel-edit-table input[type="submit"].btn-danger,
        .kennel-edit-button.btn-danger {
            background: #f44336;
        }

        .kennel-edit-table input[type="submit"].btn-danger:hover,
        .kennel-edit-button.btn-danger:hover {
            background: #d32f2f;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.12);
        }

        /* Button container */
        .button-container {
            display: flex;
            justify-content: flex-end;
            flex-wrap: nowrap;
            gap: 10px;
            margin-top: 25px;
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

        /* Kennel Grid */
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

        /* Delete Confirmation */
        .delete-confirm {
            display: none;
            background: #ffe6e6;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border: 1px solid #f44336;
        }

        /* Footer */
        footer {
            background-color: #056f65b8;
            color: white;
            padding: 40px 20px 20px;
            position: relative;
            margin-top: 60px;
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

        /* Responsive */
        @media (max-width: 768px) {
            .kennel-edit-container {
                padding: 20px;
            }

            .button-container {
                flex-direction: column;
            }

            .kennel-edit-button,
            .kennel-edit-table input[type="submit"],
            .kennel-edit-table input[type="reset"] {
                width: 100%;
            }

            .stats-container {
                flex-direction: column;
                gap: 15px;
            }

            .kennel-grid {
                grid-template-columns: 1fr;
            }

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

        .back-btn {
            display: inline-block;
            margin: 20px;
            padding: 10px 15px;
            background: rgba(5, 111, 101, 0.7);
            color: white;
            border-radius: 4px;
            text-decoration: none;
        }
        
        /* Hide edit form rows initially */
        .edit-form-row {
            display: none;
        }
    </style>
</head>

<body>
    <a href="allocate_kennel.php" class="back-btn">← Back to Kennel Management</a>

    <div class="kennel-edit-container">
        <div class="kennel-edit-form">
            <!-- Statistics Section -->
            <div class="stats-container">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $total_kennels; ?></span>
                    <span class="stat-label">Total Kennels</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $available_kennels; ?></span>
                    <span class="stat-label">Available Kennels</span>
                </div>
            </div>

            <!-- Display Messages -->
            <?php if (isset($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <h2>Edit or Delete Kennels</h2>
            
            <!-- Select Kennel -->
            <table class="kennel-edit-table">
                <tr>
                    <td>
                        <label for="kennel-select" class="form-label required-field">Select Kennel</label>
                        <select id="kennel-select" class="form-control" required>
                            <option value="">-- Select a Kennel --</option>
                            <?php while ($kennel = $kennels_result->fetch_assoc()): ?>
                                <option value="<?php echo $kennel['Kennel_ID']; ?>" 
                                        data-name="<?php echo htmlspecialchars($kennel['Kennel_Name']); ?>"
                                        data-capacity="<?php echo $kennel['Capacity']; ?>"
                                        data-size="<?php echo $kennel['Size']; ?>"
                                        data-type="<?php echo $kennel['Animal_Type']; ?>"
                                        data-availability="<?php echo $kennel['Availability']; ?>">
                                    #<?php echo $kennel['Kennel_ID']; ?> - <?php echo htmlspecialchars($kennel['Kennel_Name']); ?>
                                    (<?php echo $kennel['Animal_Type']; ?> - <?php echo $kennel['Size']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <!-- Edit Form (initially hidden) -->
            <form id="edit-form" method="post" action="edit_kennels.php" class="edit-form-row">
                <input type="hidden" name="Kennel_ID" id="edit-kennel-id">
                <input type="hidden" name="action" value="edit">
                
                <table class="kennel-edit-table">
                    <tr>
                        <td>
                            <label for="Kennel_Name" class="form-label required-field">Kennel Name</label>
                            <input type="text" id="Kennel_Name" name="Kennel_Name" required>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="Capacity" class="form-label required-field">Capacity</label>
                            <input type="number" id="Capacity" name="Capacity" min="1" required>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label class="form-label required-field">Size</label>
                            <div class="radio-group">
                                <label><input type="radio" name="Size" value="Small" required> Small</label>
                                <label><input type="radio" name="Size" value="Medium"> Medium</label>
                                <label><input type="radio" name="Size" value="Large"> Large</label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label class="form-label required-field">Animal Type</label>
                            <div class="radio-group">
                                <label><input type="radio" name="Animal_Type" value="Dog" required> Dog</label>
                                <label><input type="radio" name="Animal_Type" value="Cat"> Cat</label>
                                <label><input type="radio" name="Animal_Type" value="Mixed"> Mixed</label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="Availability" class="form-label required-field">Availability</label>
                            <input type="number" id="Availability" name="Availability" min="0" required>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="button-container">
                                <input type="submit" value="Update Kennel">
                                <input type="reset" value="Reset">
                            </div>
                        </td>
                    </tr>
                </table>
            </form>

            <!-- Delete Form (initially hidden) -->
            <form id="delete-form" method="post" action="edit_kennels.php" class="edit-form-row">
                <input type="hidden" name="Kennel_ID" id="delete-kennel-id">
                <input type="hidden" name="action" value="delete">
                
                <div id="delete-confirm" class="delete-confirm">
                    <p>Are you sure you want to delete this kennel? This action cannot be undone.</p>
                    <div class="button-container">
                        <input type="submit" value="Confirm Delete" class="btn-danger">
                        <button type="button" id="cancel-delete" class="kennel-edit-button">Cancel</button>
                    </div>
                </div>
            </form>

            <!-- Action Buttons (initially hidden) -->
            <div id="action-buttons" class="button-container edit-form-row">
                <button id="edit-btn" class="kennel-edit-button">Edit Kennel</button>
                <button id="delete-btn" class="kennel-edit-button btn-danger">Delete Kennel</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const kennelSelect = document.getElementById('kennel-select');
            const editForm = document.getElementById('edit-form');
            const deleteForm = document.getElementById('delete-form');
            const actionButtons = document.getElementById('action-buttons');
            const deleteConfirm = document.getElementById('delete-confirm');
            const editBtn = document.getElementById('edit-btn');
            const deleteBtn = document.getElementById('delete-btn');
            const cancelDeleteBtn = document.getElementById('cancel-delete');

            // Show/hide forms based on kennel selection
            kennelSelect.addEventListener('change', function() {
                if (this.value) {
                    // Populate edit form with selected kennel data
                    document.getElementById('edit-kennel-id').value = this.value;
                    document.getElementById('delete-kennel-id').value = this.value;
                    document.getElementById('Kennel_Name').value = this.options[this.selectedIndex].dataset.name;
                    document.getElementById('Capacity').value = this.options[this.selectedIndex].dataset.capacity;
                    document.getElementById('Availability').value = this.options[this.selectedIndex].dataset.availability;
                    
                    // Set radio buttons
                    const sizeValue = this.options[this.selectedIndex].dataset.size;
                    const typeValue = this.options[this.selectedIndex].dataset.type;
                    document.querySelector(`input[name="Size"][value="${sizeValue}"]`).checked = true;
                    document.querySelector(`input[name="Animal_Type"][value="${typeValue}"]`).checked = true;
                    
                    // Show action buttons
                    actionButtons.style.display = 'flex';
                    editForm.style.display = 'none';
                    deleteForm.style.display = 'none';
                    deleteConfirm.style.display = 'none';
                } else {
                    // Hide everything if no kennel selected
                    actionButtons.style.display = 'none';
                    editForm.style.display = 'none';
                    deleteForm.style.display = 'none';
                    deleteConfirm.style.display = 'none';
                }
            });
            
            // Edit button click handler
            editBtn.addEventListener('click', function(e) {
                e.preventDefault();
                editForm.style.display = 'block';
                deleteForm.style.display = 'none';
                deleteConfirm.style.display = 'none';
            });
            
            // Delete button click handler
            deleteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                deleteForm.style.display = 'block';
                deleteConfirm.style.display = 'block';
                editForm.style.display = 'none';
            });
            
            // Cancel delete button click handler
            cancelDeleteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                deleteConfirm.style.display = 'none';
                deleteForm.style.display = 'none';
            });

            // Ensure delete form submission
            deleteForm.addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to delete this kennel? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>