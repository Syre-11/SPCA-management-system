<?php
// update.php

$serverName = "localhost";
$user = "root";
$password = "";
$database = "mockdb";


// Create connection
$conn = new mysqli($serverName, $user, $password, $database);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Handle form submission (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_record'])) {
    $record_id = intval($_POST['id']);
    $animal_id = $_POST['Animal_ID'];
    $procedure_type = $_POST['procedure_type'];
    $procedure_date = $_POST['procedure_date'];
    $next_due_date = $_POST['next_due_date'];
    $vet_id = $_POST['vet_id'];
    $medication = $_POST['medication'];
    $dosage = $_POST['dosage'];
    $frequency = $_POST['frequency'];
    $duration = $_POST['duration'];
    $cost = floatval($_POST['cost']);
    $status = $_POST['status'];
    $notes = $_POST['notes'];

    // Date validation
    $today = date('Y-m-d');
    $twoWeeksAhead = date('Y-m-d', strtotime('+2 weeks'));
    if ($procedure_date > $twoWeeksAhead) {
        header("Location: update.php?edit_id=$record_id&error=Procedure+date+cannot+be+more+than+2+weeks+from+today");
        exit;
    }
    if ($next_due_date < $today) {
        header("Location: update.php?edit_id=$record_id&error=Next+Due+Date+cannot+be+in+the+past");
        exit;
    }

    $stmt = $conn->prepare("UPDATE medicalrecords SET 
            Animal_ID=?, procedure_type=?, procedure_date=?, next_due_date=?, vet_id=?, 
            medication=?, dosage=?, frequency=?, duration=?, cost=?, status=?, notes=? 
            WHERE id=?");
    $stmt->bind_param(
        "ssssisssssssi", 
        $animal_id, $procedure_type, $procedure_date, $next_due_date, 
        $vet_id, $medication, $dosage, $frequency, $duration, $cost, $status, $notes, $record_id
    );

    if ($stmt->execute()) {
        header("Location: display.php?message=Record+updated+successfully");
    } else {
        header("Location: display.php?error=Error+updating+record");
    }
    exit;
}

// Handle GET request to show the edit form
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $result = $conn->query("SELECT * FROM medicalrecords WHERE id = $edit_id");
    if ($result && $result->num_rows > 0) {
        $record = $result->fetch_assoc();
    } else {
        header("Location: display.php?error=Record+not+found");
        exit;
    }
} else {
    header("Location: display.php");
    exit;
}

// Format dates for HTML inputs
$procedureDate = date('Y-m-d\TH:i', strtotime($record['procedure_date']));
$nextDueDate = $record['next_due_date'];
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Medical Record</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Copy of medicalrecords.php CSS for form styling */
        * {margin:0; padding:0; box-sizing:border-box;}
        body {font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f9; padding: 50px 0;}
        .container {width: 90%; max-width: 900px; margin: auto; background:white; border-radius:15px; padding:30px; box-shadow:0 5px 15px rgba(0,0,0,0.1);}
        header {text-align:center; margin-bottom:30px; padding-bottom:20px; border-bottom:2px solid #e5e7eb;}
        header h1 {color: #6b7280; font-size:2rem; display:flex; align-items:center; justify-content:center; gap:10px;}
        header h1 i {color:#13cebb;}
        form {display:grid; grid-template-columns:1fr 1fr; gap:20px;}
        .form-group {margin-bottom:20px;}
        .form-group label {display:block; margin-bottom:8px; font-weight:bold; color: #6b7280;}
        .form-group input, .form-group select, .form-group textarea {width:100%; padding:12px; border:2px solid #ddd; border-radius:8px; font-size:14px; transition:border-color 0.3s;}
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {border-color:#4caf50; outline:none;}
        .form-group textarea {resize:vertical; min-height:100px;}
        .btn-group {grid-column:1/-1; display:flex; gap:15px; justify-content:flex-end; margin-top:20px;}
        .btn-add, .btn-delete {padding:12px 25px; border:none; border-radius:8px; font-size:16px; font-weight:bold; cursor:pointer; display:flex; align-items:center; gap:8px; transition:all 0.3s;}
        .btn-add {background:rgba(5, 111, 101, 0.7); color:white;}
        .btn-add:hover {background:linear-gradient(45deg,#3d8b40,#1976d2); transform:translateY(-2px); box-shadow:0 5px 15px rgba(0,0,0,0.2);}
        .btn-delete {background:#f3f4f6; color:rgba(5, 111, 101, 0.7);}
        .btn-delete:hover {background:#e5e7eb; color:#374151; transform:translateY(-2px);}
        @media (max-width:768px) {form {grid-template-columns:1fr;}}
        .alert {margin-bottom:20px; padding:12px; border-radius:8px;}
        .alert-success {background:#d1fae5; color:#065f46;}
        .alert-error {background:#fee2e2; color:#991b1b;}
        .back-btn {
            display: inline-block;
            margin: 20px;
            padding: 10px 15px;
            background: rgba(5, 111, 101, 0.7);
            color: white;
            border-radius: 4px;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <a href="medicalrecords.php" class="back-btn">← Back to Medical Records</a>
    <div class="container">
        <header>
            <h1> Edit Medical Record</h1>
        </header>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <form id="updateForm" method="POST" action="update.php">
            <input type="hidden" name="id" value="<?php echo $record['id']; ?>">

            <!-- Animal ID -->
            <div class="form-group">
                <label for="animalId">Animal ID:*</label>
                <select id="animalId" name="Animal_ID" required>
                    <option value="">Select Animal</option>
                    <?php
                    $conn = new mysqli($serverName, $user, $password, $database);
                    $sql = "SELECT Animal_ID, Animal_Name FROM animal";
                    $result = $conn->query($sql);
                    while ($row = $result->fetch_assoc()) {
                        $selected = ($record['Animal_ID'] == $row['Animal_ID']) ? 'selected' : '';
                        echo "<option value='{$row['Animal_ID']}' $selected>{$row['Animal_ID']} - {$row['Animal_Name']}</option>";
                    }
                    $conn->close();
                    ?>
                </select>
            </div>

            <!-- Procedure Type -->
            <div class="form-group">
                <label for="procedureType">Procedure Type:*</label>
                <select id="procedureType" name="procedure_type" required>
                    <option value="">Select procedure type</option>
                    <?php
                    $types = ["Vaccination","Check-up","Surgery","Dental","Grooming","Other"];
                    foreach ($types as $type) {
                        $selected = ($record['procedure_type'] == $type) ? 'selected' : '';
                        echo "<option value='$type' $selected>$type</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Procedure Date -->
            <div class="form-group">
                <label for="procedureDate">Procedure Date and Time: *</label>
                <input type="datetime-local" id="procedureDate" name="procedure_date" value="<?php echo $procedureDate; ?>" required>
            </div>

            <!-- Next Due Date -->
            <div class="form-group">
                <label for="nextDueDate">Next Due Date:*</label>
                <input type="date" id="nextDueDate" name="next_due_date" value="<?php echo $nextDueDate; ?>" required>
            </div>

            <!-- Vet ID -->
            <div class="form-group">
                <label for="vetId">Vet ID:*</label>
                <select id="vetId" name="vet_id" required>
                    <option value="">Select Vet ID</option>
                    <option value="11" <?php if ($record['vet_id'] == "11") echo "selected"; ?>>11 - Dr. Bongani</option>
                    <option value="12" <?php if ($record['vet_id'] == "12") echo "selected"; ?>>12 - Dr. Lindiwe</option>
                    <option value="21" <?php if ($record['vet_id'] == "21") echo "selected"; ?>>21 - Dr. Thabo</option>
                </select>
            </div>

            <!-- Medication -->
            <div class="form-group">
                <label for="medication">Medication</label>
                <select id="medication" name="medication">
                    <?php
                        $medication_options = [
                            "Pain Relief","Antibiotic","Anti-inflammatory","Fever Reducer","Stomach Medicine",
                            "Skin Cream","Eye Drops","Worm Treatment","Flea Medicine","Vaccine"
                        ];
                        echo '<option value="">-- Select Medication --</option>';
                        foreach ($medication_options as $option) {
                            $selected = ($record['medication'] == $option) ? 'selected' : '';
                            echo "<option value=\"$option\" $selected>$option</option>";
                        }
                    ?>
                </select>
            </div>

            <!-- Dosage -->
            <div class="form-group">
                <label for="dosage">Dosage</label>
                <input type="text" id="dosage" name="dosage" value="<?php echo $record['dosage']; ?>">
            </div>

            <!-- Frequency -->
            <div class="form-group">
                <label for="frequency">Frequency</label>
                <input type="text" id="frequency" name="frequency" value="<?php echo $record['frequency']; ?>">
            </div>

            <!-- Duration -->
            <div class="form-group">
                <label for="duration">Duration</label>
                <input type="text" id="duration" name="duration" value="<?php echo $record['duration']; ?>">
            </div>

            <!-- Cost -->
            <div class="form-group">
                <label for="cost">Cost (Rand)</label>
                <input type="number" step="0.01" id="cost" name="cost" value="<?php echo $record['cost']; ?>">
            </div>

            <!-- Status -->
            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status" required>
                    <option value="Completed" <?php if($record['status']=='Completed') echo 'selected'; ?>>Completed</option>
                    <option value="Pending" <?php if($record['status']=='Pending') echo 'selected'; ?>>Pending</option>
                </select>
            </div>

            <!-- Notes -->
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes"><?php echo $record['notes']; ?></textarea>
            </div>

            <!-- Buttons -->
            <div class="btn-group">
                <button type="button" class="btn-delete" onclick="clearForm()"><i class="fas fa-times"></i> Clear Form</button>
                <button type="submit" name="update_record" class="btn-add"><i class="fas fa-edit"></i> Update Record</button>
            </div>
        </form>
    </div>

    <script>
        function clearForm() {
            const form = document.getElementById('updateForm');
            form.querySelectorAll('input, select, textarea').forEach(field => {
                if(field.type !== 'hidden' && field.type !== 'submit' && field.type !== 'button') {
                    field.value = '';
                }
            });
        }
    </script>
</body>
</html>
