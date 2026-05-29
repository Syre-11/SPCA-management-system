<?php
// Database configuration
$serverName = "localhost";
$user = "root";
$password = "";
$database = "mockdb";

// Create connection
$conn = new mysqli($serverName, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection to server and database failed: " . $conn->connect_error);
}

// Handle search functionality by Animal ID
$search_query = '';
if (isset($_GET['search_animal_id']) && !empty($_GET['search_animal_id'])) {
    $search_animal_id = $conn->real_escape_string($_GET['search_animal_id']);
    $search_query = " AND Animal_ID = '$search_animal_id'";
}

// Handle soft delete (archive) action
if (isset($_GET['archive_id'])) {
    $archive_id = intval($_GET['archive_id']);
    $conn->query("UPDATE medicalrecords SET Hide = 1 WHERE id = $archive_id");
    header("Location: display.php?message=Record+archived+successfully");
    exit;
}

// Handle recover action
if (isset($_GET['recover_id'])) {
    $recover_id = intval($_GET['recover_id']);
    $conn->query("UPDATE medicalrecords SET Hide = 0 WHERE id = $recover_id");
    header("Location: display.php?message=Record+recovered+successfully");
    exit;
}

// Fetch all visible medical records
$sql = "SELECT * FROM medicalrecords WHERE Hide = 0 $search_query ORDER BY procedure_date DESC";
$result = $conn->query($sql);

// Fetch archived records
$archived_result = $conn->query("SELECT * FROM medicalrecords WHERE Hide = 1 ORDER BY procedure_date DESC");

// Fetch pending records for alerts
$pending_alerts = [];
$alert_query = "
    SELECT m.Animal_ID, m.Status, a.Animal_Name, a.Animal_Species
    FROM medicalrecords m
    JOIN animal a ON m.Animal_ID = a.Animal_ID
    WHERE m.status = 'Pending'
    ORDER BY m.Due_Date ASC";
$alert_result = $conn->query($alert_query);
if ($alert_result && $alert_result->num_rows > 0) {
    while ($row = $alert_result->fetch_assoc()) {
        $pending_alerts[] = $row;
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animal Medical Records</title>
    <style>
        /* Keep your original CSS unchanged */
        /* ... your existing CSS here ... */
        /* Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        /* Body styling */
        body {
            font-family: Arial, sans-serif;
            background: #f9fafb;
            margin: 0;
            padding: 0;
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
            font-weight: 500;
            padding: 8px 12px;
            transition: all 0.3s ease;
            border-radius: 4px;
        }

        .nav-links ul li a:hover {
            background: rgba(5, 111, 101, 0.7);
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

        /* Container styling */
        .container {
            width: 95%;
            max-width: 1400px;
            margin: 20px auto;
            padding: 15px;
            background: white;
            border-radius: 4px;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Header styling */
        h1 {
            text-align: center;
            margin: 20px 0;
            color: #2c3e50;
        }

        /* Search form styling */
        .search-container {
            width: 90%;
            margin: 20px auto;
            padding: 15px;
            background: white;
            border-radius: 4px;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
        }

        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
        }

        .search-input-wrapper {
            display: flex;
            flex-direction: column;
            min-width: 150px;
        }

        .search-input-wrapper label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        .search-input-wrapper input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .search-btn,
        .clear-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .search-btn {
            background: #2980b9;
            color: white;
        }

        .clear-btn {
            background: #6b7280;
            color: white;
        }

        /* Notification styling */
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

        /* Table styling */
        .records-table {
            width: 95%;
            max-width: 1400px;
            margin: 20px auto;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            font-family: Arial, Helvetica, sans-serif;
        }

        .records-table th {
            background: rgba(5, 111, 101, 0.7);
            color: white;
            font-size: 15px;
            font-weight: 600;
            padding: 15px;
            text-align: left;
        }

        .records-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
            vertical-align: middle;
        }

        .records-table tr:hover {
            background-color: #f3f4f6;
        }

        /* Status badges */
        .status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #d97706;
        }

        .status-completed {
            background-color: #d1fae5;
            color: #059669;
        }

        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .edit-btn {
            background: linear-gradient(45deg, #2196F3, #21bef3);
            color: white;
        }

        .edit-btn:hover {
            background: white;
            color: #2196F3;
            border: 2px solid #2196F3;
        }

        .delete-btn {
            background: linear-gradient(45deg, #ef4444, #f87171);
            color: white;
        }

        .delete-btn:hover {
            background: white;
            color: #ef4444;
            border: 2px solid #ef4444;
        }

        /* Charts container */
        .charts-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin: 20px auto;
            width: 90%;
        }

        .charts-container canvas {
            max-width: 400px;
            width: 100%;
        }

        /* Footer */
        footer {
            background: rgba(5, 111, 101, 0.7);
            color: white;
            padding: 40px 20px;
            position: relative;
            margin-top: 60px;
            text-align: center;
        }

        footer p {
            margin: 0;
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

            .search-form {
                flex-direction: column;
                align-items: center;
            }

            .records-table {
                width: 100%;
            }

            .records-table th,
            .records-table td {
                font-size: 13px;
                padding: 10px;
            }

            .charts-container canvas {
                max-width: 100%;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 1.5rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }

            .action-btn {
                width: 100%;
                text-align: center;
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
    </style>
</head>
<body>
    <a href="vetdashboard.php" class="back-btn">← Back to Vet Dashboard</a>
    <div class="container">
        <h1><i class="fas fa-paw"></i> Animal Medical Records</h1>

        <!-- Search Form -->
        <div class="search-container">
            <form method="GET" action="" class="search-form">
                <div class="search-input-wrapper">
                    <label for="search_animal_id">Search by Animal ID</label>
                    <input type="text" id="search_animal_id" name="search_animal_id"
                        placeholder="Enter Animal ID"
                        value="<?php echo isset($_GET['search_animal_id']) ? htmlspecialchars($_GET['search_animal_id']) : ''; ?>">
                </div>
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
                <button type="button" id="clearSearch" class="clear-btn"><i class="fas fa-times-circle"></i> Clear</button>
            </form>
        </div>
        <script>
            document.getElementById('clearSearch').addEventListener('click', function () {
                document.getElementById('search_animal_id').value = '';
                document.querySelector('form').submit();
            });
        </script>

        <!-- Notifications -->
        <?php if (isset($_GET['message'])): ?>
            <div class="notification success"><?php echo htmlspecialchars($_GET['message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="notification error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <!-- Active Records Table -->
        <table class="records-table">
            <thead>
                <tr>
                    <th>Animal ID</th>
                    <th>Procedure Type</th>
                    <th>Procedure Date</th>
                    <th>Next Due Date</th>
                    <th>Vet ID</th>
                    <th>Medication</th>
                    <th>Duration</th>
                    <th>Frequency</th>
                    <th>Dosage</th>
                    <th>Notes</th>
                    <th>Cost (R)</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['Animal_ID']); ?></td>
                            <td><?php echo htmlspecialchars($row['procedure_type']); ?></td>
                            <td><?php echo htmlspecialchars($row['procedure_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['next_due_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['vet_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['medication']); ?></td>
                            <td><?php echo htmlspecialchars($row['duration']); ?></td>
                            <td><?php echo htmlspecialchars($row['frequency']); ?></td>
                            <td><?php echo htmlspecialchars($row['dosage']); ?></td>
                            <td><?php echo htmlspecialchars($row['notes']); ?></td>
                            <td><?php echo htmlspecialchars($row['cost']); ?></td>
                            <td>
                                <?php
                                $status = htmlspecialchars($row['status']);
                                $statusClass = $status === 'Completed' ? 'status-completed' : ($status === 'Pending' ? 'status-pending' : '');
                                ?>
                                <span class="status <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="update.php?edit_id=<?php echo $row['id']; ?>" class="action-btn edit-btn">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="?archive_id=<?php echo $row['id']; ?>" class="action-btn delete-btn"
                                        onclick="return confirm('Are you sure you want to archive this record?')">
                                        <i class="fas fa-archive"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="13" class="empty-state">No medical records found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Archived Records Table -->
        <?php if ($archived_result && $archived_result->num_rows > 0): ?>
            <h2 class="archived-table">Archived Records</h2>
            <table class="records-table">
                <thead>
                    <tr>
                        <th>Animal ID</th>
                        <th>Procedure Type</th>
                        <th>Procedure Date</th>
                        <th>Next Due Date</th>
                        <th>Vet ID</th>
                        <th>Medication</th>
                        <th>Duration</th>
                        <th>Frequency</th>
                        <th>Dosage</th>
                        <th>Notes</th>
                        <th>Cost (R)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $archived_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['Animal_ID']); ?></td>
                            <td><?php echo htmlspecialchars($row['procedure_type']); ?></td>
                            <td><?php echo htmlspecialchars($row['procedure_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['next_due_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['vet_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['medication']); ?></td>
                            <td><?php echo htmlspecialchars($row['duration']); ?></td>
                            <td><?php echo htmlspecialchars($row['frequency']); ?></td>
                            <td><?php echo htmlspecialchars($row['dosage']); ?></td>
                            <td><?php echo htmlspecialchars($row['notes']); ?></td>
                            <td><?php echo htmlspecialchars($row['cost']); ?></td>
                            <td>
                                <?php
                                $status = htmlspecialchars($row['status']);
                                $statusClass = $status === 'Completed' ? 'status-completed' : ($status === 'Pending' ? 'status-pending' : '');
                                ?>
                                <span class="status <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?recover_id=<?php echo $row['id']; ?>" class="action-btn recover-btn"
                                        onclick="return confirm('Are you sure you want to recover this record?')">
                                        <i class="fas fa-undo"></i> Recover
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>
</body>
</html>
